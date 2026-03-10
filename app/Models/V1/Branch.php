<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'email', 'phone',
        'city', 'address', 'latitude', 'longitude',
        'manager_user_id', 'manager_name', 'manager_email', 'manager_phone',
        'compliance_score', 'category', 'total_visits',
        'last_visit_date', 'next_scheduled_visit', 'status'
    ];

    protected $casts = [
        'last_visit_date' => 'date',
        'next_scheduled_visit' => 'date',
        'status' => 'boolean',
        'compliance_score' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];
    
    protected static function booted()
    {
        static::addGlobalScope('branch', function (Builder $builder) {
            if (auth()->check()){
                
                $user = auth()->user();
                
                if($user->role === 'company_admin') {
                    $builder->where('company_id', $user->company_id);
                }
                elseif($user->role === 'branch_manager') {
                    $builder->where('manager_user_id', $user->id);
                }
                elseif($user->role === 'auditor') {
                    $builder->whereHas('visits');
                }
                elseif($user->role === 'super_admin') {
                    $builder->whereRaw('1 = 1');
                }
                else {
                    $builder->whereRaw('1 = 0');
                }
            }
            else {
                $builder->whereRaw('1 = 0');
            }
        });

    }
    
    /**
     * علاقة الفرع بالشركة
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * علاقة الفرع بالمدير
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    /**
     * علاقة الفرع بالزيارات
     */
    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
    
    public function statistics()
    {
        return $this->hasMany(Statistic::class, 'branch_id');
    }
    
    /**
     * علاقة الفرع بالمستخدمين
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Accessor للحالة النصية
     */
    public function getStatusTextAttribute()
    {
        return $this->status ? 'نشط' : 'غير نشط';
    }

    /**
     * Scope للفروع النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope للفروع بحاجة زيارة
     */
    public function scopeNeedsVisit($query)
    {
        return $query->where(function($q) {
            $q->whereNull('next_scheduled_visit')
              ->orWhere('next_scheduled_visit', '<=', now());
        });
    }

    /**
     * Scope للبحث في الفروع
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('manager_name', 'like', "%{$search}%");
        });
    }
}
