<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $entity = null;

        if($this->template->type == 'service_request') {
            $entity = new ServiceRequestResource($this->entity);

        }elseif($this->template->type == 'reminder_visit_tomorrow') {
            $entity = new UserResource($this->entity);

        }elseif(n_array($this->template->type , ['visit_scheduled', 'visit_started', 'visit_completed'])) {
            $entity = new VisitResource($this->entity);
            
        }elseif(n_array($this->template->type , ['subscription_expiry_reminder', 'subscription_expired'])) {
            $entity = new CompanyResource($this->entity);
        }
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'body' => $this->body,
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d H:i:s'),
            'read_at' => $this->read_at?->format('Y-m-d H:i:s'),
            'entity_id' => $this->entity_id,
            'entity_type' => $this->entity_type,
            'url' => $this->url,
            'entity' => $entity,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
