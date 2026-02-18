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
        'city', 'address', 'contact_person', 'logo',
        'subscription_start', 'subscription_end', 'subscription_status',
        'total_branches', 'overall_score', 'category',
        'status', 'is_verified'
    ];

    protected $casts = [
        'subscription_start' => 'date',
        'subscription_end' => 'date',
        'status' => 'boolean',
        'is_verified' => 'boolean',
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
                    $builder->whereRaw('1 = 0');
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
        return $this->branches()->avg('compliance_score') ?? 0.00;
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
}
