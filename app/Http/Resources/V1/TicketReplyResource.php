<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketReplyResource extends JsonResource
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
            'ticket_id' => $this->ticket_id,
            
            'replied_by' => $this->replied_by,
            'replier' => $this->whenLoaded('replier', function () {
                return [
                    'id' => $this->replier->id,
                    'name' => $this->replier->name,
                    'email' => $this->replier->email,
                    'role' => $this->replier->role,
                    'role_text' => $this->replier_role_text,
                ];
            }),
            
            // المحتوى
            'message' => $this->message,
            'formatted_message' => $this->formatted_message,
            'is_internal' => (bool) $this->is_internal,
            'is_public' => $this->is_public,
            
            // المرفقات
            'has_attachments' => (bool) $this->has_attachments,
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            
            // التتبع
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            
            // التواريخ
            'created_at' => $this->created_at->toIso8601String(),
            'created_at_formatted' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->toIso8601String() : null,
        ];
    }
}
