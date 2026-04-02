<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'commercial_registration',
        'city', 'address', 'contact_person', 'logo', 'max_allowed_users', 'max_allowed_branches',
        'subscription_start', 'subscription_end', 'subscription_status',
        'total_branches', 'overall_score', 'category',
        'status', 'is_verified', 'ai_support'
    ];

    protected $casts = [
        'subscription_start' => 'date',
        'subscription_end' => 'date',
        'status' => 'boolean',
        'is_verified' => 'boolean',
        'ai_support' => 'boolean',
        'overall_score' => 'decimal:2'
    ];
    
    protected static function booted()
    {
        static::addGlobalScope('companies', function (Builder $builder) {
            if (auth()->check()){
                
                $user = auth()->user();
                
                if($user->role === 'company_admin') {
                    $builder->whereHas('manager');
                }
                elseif($user->role === 'branch_manager') {
                    $builder->whereHas('branches');
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
     * علاقة الشركة بالفروع
     */
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * علاقة الشركة بالمستخدمين
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    public function manager()
    {
        return $this->hasOne(User::class);
    }

    /**
     * علاقة الشركة بالزيارات
     */
    public function visits()
    {
        return $this->hasManyThrough(Visit::class, Branch::class);
    }
    
        public function statistics()
    {
        return $this->hasMany(Statistic::class, 'company_id');
    }
    
    /**
     * Accessor لمتوسط الأداء
     */
    public function getAverageScoreAttribute()
    {
        return $this->branches->map(function($branch) {
            return $branch->visits()
                ->whereYear('created_at', $branch->visits()->latest()->value('created_at')?->year ?? now()->year)?->avg('percentage');
        })->filter()->avg();
    }

    /**
     * Accessor لحالة الاشتراك
     */
    public function getIsSubscriptionActiveAttribute()
    {
        if (!$this->subscription_end) return false;
        return $this->subscription_end->isFuture() && 
               $this->subscription_status === 'active';
    }

    /**
     * Scope للشركات النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope للشركات ذات الاشتراك النشط
     */
    public function scopeWithActiveSubscription($query)
    {
        return $query->where('subscription_status', 'active')
            ->where('subscription_end', '>', now());
    }

    public function getActiveSubscription()
    {
        $company = $this->withActiveSubscription()->first();
        if($company) {
            if($company->status == 1) return true;
        } else {
            $this->update([
                'subscription_status' => 'expired'
            ]);
        }
        return false;
    }

    public function getRemainingSubscriptionDays()
    {
        if ($this->subscription_status !== 'active') {
            return 0;
        }
        if (!$this->subscription_end || $this->subscription_end->isPast()) {
            return 0;
        }
        return (Integer) now()->diffInDays($this->subscription_end);
    }

    
}
