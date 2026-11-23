<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subscription_status'
    ];

    protected $casts = [
        'subscription_start' => 'date',
        'subscription_end' => 'date',
        'status' => 'boolean',
        'is_verified' => 'boolean',
        'auto_renew' => 'boolean',
        'overall_score' => 'decimal:2'
    ];
    
    
    public function manager()
    {
        return $this->hasOne(User::class);
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

    public function scopeActiveSubscription($query)
    {
        return $query->where('subscription_status', 'active');
    }
    
    public function scopeTodaySubscriptionExpired($query)
    {
        return $query->activeSubscription()
                    ->whereDate('subscription_end', now()->toDateString());
    }

    public function scopeSubscriptionEndsInDays($query, int $days)
    {
        return $query->activeSubscription()
                    ->whereDate('subscription_end', now()->addDays($days)->toDateString());
    }

    public function scopeWithActiveSubscription($query)
    {
        return $query->activeSubscription()->where('subscription_end', '>', now());
    }

    public function getActiveSubscription()
    {
        $company = static::whereKey($this->getKey())->withActiveSubscription()->first();
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
