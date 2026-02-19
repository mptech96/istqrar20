<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketReply extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ticket_replies';

    protected $fillable = [
        'ticket_id',
        'replied_by',
        'message',
        'is_internal',
        'has_attachments',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'has_attachments' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function ($reply) {
            $reply->ticket->updateRepliesCount();
            
            // Record first response if this is the first reply
            if ($reply->ticket->replies_count == 1) {
                $reply->ticket->recordFirstResponse();
            }
        });

        static::deleted(function ($reply) {
            $reply->ticket->updateRepliesCount();
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function replier()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'entity');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('replied_by', $userId);
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getIsPublicAttribute()
    {
        return !$this->is_internal;
    }

    public function getReplierNameAttribute()
    {
        return $this->replier?->name ?? 'غير معروف';
    }

    public function getReplierRoleAttribute()
    {
        return $this->replier?->role ?? 'غير معروف';
    }

    public function getReplierRoleTextAttribute()
    {
        if (!$this->replier) {
            return 'غير معروف';
        }

        return match ($this->replier->role) {
            'super_admin' => 'مدير النظام',
            'company_admin' => 'مدير الشركة',
            'branch_manager' => 'مدير فرع',
            'auditor' => 'مراقب',
            default => $this->replier->role,
        };
    }

    public function getFormattedMessageAttribute()
    {
        return nl2br(e($this->message));
    }

    // =========================================================================
    // Methods
    // =========================================================================

    public function markAsInternal()
    {
        $this->update(['is_internal' => true]);
    }

    public function markAsPublic()
    {
        $this->update(['is_internal' => false]);
    }

    public function hasAttachments()
    {
        return $this->has_attachments;
    }

    public function updateAttachmentsStatus()
    {
        $this->update([
            'has_attachments' => $this->attachments()->exists(),
        ]);
    }
}
