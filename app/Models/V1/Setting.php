<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    public $timestamps = true;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'data_type',
        'validation_rules',
        'options',
        'label',
        'description',
        'placeholder',
        'sort_order',
        'is_public',
        'is_encrypted',
        'is_required',
    ];

    protected $casts = [
        'options' => 'array',
        'is_public' => 'boolean',
        'is_encrypted' => 'boolean',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saved(function ($setting) {
            Cache::forget("setting_{$setting->group}_{$setting->key}");
            Cache::forget("settings_group_{$setting->group}");
            Cache::forget("all_settings");
        });

        static::deleted(function ($setting) {
            Cache::forget("setting_{$setting->group}_{$setting->key}");
            Cache::forget("settings_group_{$setting->group}");
            Cache::forget("all_settings");
        });
    }

    // =========================================================================
    // Accessors & Mutators
    // =========================================================================

    public function getValueAttribute($value)
    {
        // Handle encryption if needed
        if ($this->is_encrypted && $value) {
            try {
                $value = decrypt($value);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                return $value;
            }
        }

        // Cast based on type
        return match ($this->type) {
            'number' => is_numeric($value) ? (float) $value : 0,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true) ?? [],
            'string', 'text' => (string) $value,
            default => $value,
        };
    }

    public function setValueAttribute($value)
    {
        // Handle encryption
        if ($this->is_encrypted && !is_null($value)) {
            $value = encrypt($value);
        }

        // Handle array/json encoding
        if (in_array($this->type, ['json', 'array']) && is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $this->attributes['value'] = $value;
    }

    public function getOptionsArrayAttribute(): array
    {
        return $this->options ?? [];
    }

    public function getFormattedValueAttribute()
    {
        $value = $this->value;

        return match ($this->type) {
            'boolean' => $value ? 'نعم' : 'لا',
            'array', 'json' => is_array($value) ? implode('، ', $value) : $value,
            default => $value,
        };
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('key', $key);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('key');
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    public static function get($key, $default = null, $group = 'general')
    {
        $cacheKey = "setting_{$group}_{$key}";
        
        return Cache::remember($cacheKey, now()->addDay(), function () use ($group, $key, $default) {
            $setting = self::where('group', $group)
                          ->where('key', $key)
                          ->first();
            
            return $setting ? $setting->value : $default;
        });
    }

    public static function getGroup($group, $publicOnly = false)
    {
        $cacheKey = "settings_group_{$group}_" . ($publicOnly ? 'public' : 'all');
        
        return Cache::remember($cacheKey, now()->addDay(), function () use ($group, $publicOnly) {
            $query = self::where('group', $group);
            
            if ($publicOnly) {
                $query->where('is_public', true);
            }
            
            return $query->ordered()->get()->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->value];
            })->toArray();
        });
    }

    public static function getAll($publicOnly = false)
    {
        $cacheKey = 'all_settings_' . ($publicOnly ? 'public' : 'all');
        
        return Cache::remember($cacheKey, now()->addHour(), function () use ($publicOnly) {
            $query = self::query();
            
            if ($publicOnly) {
                $query->where('is_public', true);
            }
            
            return $query->ordered()->get()
                ->groupBy('group')
                ->map(function ($settings) {
                    return $settings->mapWithKeys(function ($setting) {
                        return [$setting->key => $setting->value];
                    })->toArray();
                })->toArray();
        });
    }

    public static function set($key, $value, $group = 'general')
    {
        $setting = self::firstOrNew(['group' => $group, 'key' => $key]);
        $setting->value = $value;
        $setting->save();
        
        return $setting;
    }

    public static function setMany(array $settings, $group = 'general')
    {
        foreach ($settings as $key => $value) {
            self::set($key, $value, $group);
        }
    }

    public static function has($key, $group = 'general')
    {
        return self::where('group', $group)->where('key', $key)->exists();
    }

    public static function remove($key, $group = 'general')
    {
        return self::where('group', $group)->where('key', $key)->delete();
    }

    public static function removeGroup($group)
    {
        return self::where('group', $group)->delete();
    }

    // =========================================================================
    // Validation
    // =========================================================================

    public function validateValue($value): bool
    {
        if (!$this->validation_rules) {
            return true;
        }

        $validator = validator(
            ['value' => $value],
            ['value' => $this->validation_rules]
        );

        return !$validator->fails();
    }

    public function validateAndGetValue($value)
    {
        if ($this->options && !in_array($value, array_keys($this->options))) {
            throw new \Exception("القيمة غير مسموحة لهذا الإعداد");
        }

        return match ($this->type) {
            'number' => is_numeric($value) ? (float) $value : 0,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }
}
