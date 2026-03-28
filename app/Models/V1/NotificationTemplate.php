<?php
// app/Models/V1/NotificationTemplate.php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    public $timestamps = false; // because the table doesn't have created_at/updated_at

    protected $table = 'notification_templates';

    protected $fillable = [
        'type', 'locale', 'title_template', 'body_template', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationship with notifications
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

     public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

}