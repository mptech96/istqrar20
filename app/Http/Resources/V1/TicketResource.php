<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            
            // الجهة المرسلة
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                    'role' => $this->creator->role,
                    'role_text' => $this->creator->role_text ?? $this->creator->role,
                ];
            }),
            'user_type' => $this->user_type,
            'user_type_text' => $this->user_type_text,
            
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'email' => $this->company->email,
                    'phone' => $this->company->phone,
                ];
            }),
            
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                    'code' => $this->branch->code,
                ];
            }),
            
            'visit_id' => $this->visit_id,
            'visit' => $this->whenLoaded('visit', function () {
                return [
                    'id' => $this->visit->id,
                    'visit_date' => $this->visit->visit_date,
                    'status' => $this->visit->status,
                    'status_text' => $this->visit->status_text,
                ];
            }),
            
            // محتوى التذكرة
            'subject' => $this->subject,
            'description' => $this->description,
            'ticket_type' => $this->ticket_type,
            'ticket_type_text' => $this->ticket_type_text,
            
            // الأولوية والتتبع
            'priority' => $this->priority,
            'priority_text' => $this->priority_text,
            'department' => $this->department,
            'department_text' => $this->department_text,
            
            // التخصيص والمتابعة
            'assigned_to' => $this->assigned_to,
            'assigned_to_user' => $this->whenLoaded('assignedTo', function () {
                return [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                    'email' => $this->assignedTo->email,
                    'role' => $this->assignedTo->role,
                ];
            }),
            'assigned_at' => $this->assigned_at ? $this->assigned_at->toIso8601String() : null,
            
            // الحالة
            'status' => $this->status,
            'status_text' => $this->status_text,
            'resolution' => $this->resolution,
            'resolved_by' => $this->resolved_by,
            'resolver' => $this->whenLoaded('resolver', function () {
                return [
                    'id' => $this->resolver->id,
                    'name' => $this->resolver->name,
                ];
            }),
            'resolved_at' => $this->resolved_at ? $this->resolved_at->toIso8601String() : null,
            
            // SLA
            'sla_due_date' => $this->sla_due_date ? $this->sla_due_date->toIso8601String() : null,
            'first_response_at' => $this->first_response_at ? $this->first_response_at->toIso8601String() : null,
            'resolution_due_date' => $this->resolution_due_date ? $this->resolution_due_date->toIso8601String() : null,
            
            // إحصائيات
            'replies_count' => $this->replies_count,
            'customer_satisfaction' => $this->customer_satisfaction,
            
            // محسوب
            'is_overdue' => $this->is_overdue,
            'days_since_created' => $this->days_since_created,
            'response_time' => $this->response_time,
            'resolution_time' => $this->resolution_time,
            'sla_status' => $this->sla_status,
            
            // العلاقات
            'replies' => TicketReplyResource::collection($this->whenLoaded('replies')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            
            // التواريخ
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->toIso8601String() : null,
        ];
    }
}
