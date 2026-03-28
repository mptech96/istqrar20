<?php
// app/Models/V1/Notification.php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    public $timestamps = false; // using created_at manually, no updated_at

    protected $table = 'notifications';

    protected $fillable = [
        'user_id', 'notification_template_id', 'title', 'body', 
        'scheduled_date', 'read_at', 'entity_id', 'entity_type', 'url', 'created_at'
    ];

    protected $casts = [
        'data' => 'array',
        'scheduled_date' => 'datetime',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function entity()
    {
        return $this->morphTo();
    }
    
    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with template
     */
    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope for scheduled (future) notifications
     */
    public function scopeScheduled($query)
    {
        return $query->where('scheduled_date', '>', now());
    }

    /**
     * Scope for sent (not future) notifications
     */
    public function scopeSent($query)
    {
        return $query->where('scheduled_date', '<=', now());
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->read_at = now();
        $this->save();
        return $this;
    }

    /**
     * Check if notification is read
     */
    public function getIsReadAttribute()
    {
        return !is_null($this->read_at);
    }

    
}