<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tickets';

    protected $fillable = [
        'ticket_number',
        'created_by',
        'user_type',
        'company_id',
        'branch_id',
        'visit_id',
        'subject',
        'description',
        'ticket_type',
        'priority',
        'department',
        'assigned_to',
        'assigned_at',
        'status',
        'resolution',
        'resolved_by',
        'resolved_at',
        'sla_due_date',
        'first_response_at',
        'resolution_due_date',
        'replies_count',
        'customer_satisfaction',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
        'sla_due_date' => 'datetime',
        'first_response_at' => 'datetime',
        'resolution_due_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'customer_satisfaction' => 'integer',
        'replies_count' => 'integer',
    ];

    protected static function booted()
    {
        static::addGlobalScope('tickets', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                
                if ($user->role === 'company_admin') {
                    $builder->where('company_id', $user->company_id)
                        ->orWhere('created_by', $user->id);

                } elseif ($user->role === 'branch_manager') {
                    $builder->where('branch_id', $user->branch_id)
                       ->orWhere('created_by', $user->id);

                } elseif ($user->role === 'auditor') {
                    $builder->where('created_by', $user->id)
                       ->orWhere('assigned_to', $user->id);

                } elseif ($user->role === 'super_admin') {
                    $builder->whereRaw('1 = 1');
                } else {
                    $builder->whereRaw('1 = 0');
                }
            } else {
                $builder->whereRaw('1 = 0');
            }
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'entity');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress', 'pending_customer']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('ticket_type', $type);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeOverdue($query)
    {
        return $query->where('sla_due_date', '<', now())
                    ->whereNotIn('status', ['resolved', 'closed']);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('ticket_number', 'like', "%{$search}%")
              ->orWhere('subject', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // =========================================================================
    // Accessors & Mutators
    // =========================================================================

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            'open' => 'مفتوحة',
            'in_progress' => 'قيد المعالجة',
            'pending_customer' => 'في انتظار العميل',
            'resolved' => 'تم الحل',
            'closed' => 'مغلقة',
            'escalated' => 'مرفوعة',
            default => 'غير معروف',
        };
    }

    public function getPriorityTextAttribute()
    {
        return match ($this->priority) {
            'low' => 'منخفضة',
            'medium' => 'متوسطة',
            'high' => 'عالية',
            'critical' => 'حرجة',
            default => 'غير معروف',
        };
    }

    public function getTicketTypeTextAttribute()
    {
        return match ($this->ticket_type) {
            'dispute' => 'نزاع',
            'complaint' => 'شكوى',
            'request' => 'طلب',
            'inquiry' => 'استفسار',
            'technical' => 'دعم فني',
            'feedback' => 'اقتراح',
            default => 'غير معروف',
        };
    }

    public function getDepartmentTextAttribute()
    {
        return match ($this->department) {
            'support' => 'الدعم',
            'technical' => 'تقني',
            'quality' => 'الجودة',
            'billing' => 'الفوترة',
            'admin' => 'إداري',
            default => 'غير معروف',
        };
    }

    public function getUserTypeTextAttribute()
    {
        return match ($this->user_type) {
            'company' => 'شركة',
            'branch' => 'فرع',
            'auditor' => 'مراقب',
            'admin' => 'مسؤول',
            'quality' => 'جودة',
            default => 'غير معروف',
        };
    }

    public function getIsOverdueAttribute()
    {
        return $this->sla_due_date && 
               $this->sla_due_date < now() && 
               !in_array($this->status, ['resolved', 'closed']);
    }

    public function getDaysSinceCreatedAttribute()
    {
        return $this->created_at->diffInDays(now());
    }

    public function getResponseTimeAttribute()
    {
        if (!$this->first_response_at) {
            return null;
        }
        return $this->created_at->diffInHours($this->first_response_at);
    }

    public function getResolutionTimeAttribute()
    {
        if (!$this->resolved_at) {
            return null;
        }
        return $this->created_at->diffInHours($this->resolved_at);
    }

    // =========================================================================
    // Methods
    // =========================================================================

    public function assignTo($userId)
    {
        $this->update([
            'assigned_to' => $userId,
            'assigned_at' => now(),
            'status' => 'in_progress',
        ]);
    }

    public function resolve($resolution, $userId = null)
    {
        $this->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolved_by' => $userId ?? auth()->id(),
            'resolved_at' => now(),
        ]);
    }

    public function close()
    {
        $this->update([
            'status' => 'closed',
        ]);
    }

    public function reopen()
    {
        if ($this->status === 'closed') {
            $this->update([
                'status' => 'open',
                'resolution' => null,
                'resolved_by' => null,
                'resolved_at' => null,
            ]);
        }
    }

    public function escalate()
    {
        $this->update([
            'status' => 'escalated',
            'priority' => 'critical',
        ]);
    }

    public function isAssigned()
    {
        return !is_null($this->assigned_to);
    }

    public function isResolved()
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    public function updateRepliesCount()
    {
        $this->update([
            'replies_count' => $this->replies()->count(),
        ]);
    }

    public function recordFirstResponse()
    {
        if (!$this->first_response_at) {
            $this->update([
                'first_response_at' => now(),
            ]);
        }
    }

    public function setSlaDueDate($hours = 24)
    {
        $this->update([
            'sla_due_date' => now()->addHours($hours),
            'resolution_due_date' => now()->addHours($hours * 2),
        ]);
    }

    public static function generateTicketNumber()
    {
        $prefix = 'TKT';
        $year = now()->format('Y');
        $month = now()->format('m');
        
        $lastTicket = self::whereYear('created_at', now()->year)
                         ->whereMonth('created_at', now()->month)
                         ->orderBy('id', 'desc')
                         ->first();
        
        if ($lastTicket) {
            $lastNumber = intval(substr($lastTicket->ticket_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return "{$prefix}-{$year}{$month}-{$newNumber}";
    }

    public function getSlaStatusAttribute()
    {
        if ($this->isResolved()) {
            return 'completed';
        }
        
        if (!$this->sla_due_date) {
            return 'no_sla';
        }
        
        if ($this->sla_due_date < now()) {
            return 'overdue';
        }
        
        $hoursLeft = now()->diffInHours($this->sla_due_date);
        
        if ($hoursLeft < 4) {
            return 'critical';
        } elseif ($hoursLeft < 12) {
            return 'warning';
        } else {
            return 'ok';
        }
    }
}
