<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_type' => $this->request_type,
            'request_type_text' => $this->request_type === 'package_request' ? 'طلب باقة' : 'رسالة تواصل',
            'package_name' => $this->package_name,
            'customer_name' => $this->customer_name,
            'company_name' => $this->company_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status,
            'status_text' => $this->getStatusText(),
            'admin_notes' => $this->admin_notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function getStatusText(): string
    {
        return match ($this->status) {
            'new'       => 'جديد',
            'in_review' => 'قيد المراجعة',
            'closed'    => 'مغلق',
            default     => $this->status,
        };
    }
}