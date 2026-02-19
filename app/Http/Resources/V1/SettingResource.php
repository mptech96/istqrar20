<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
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
            'group' => $this->group,
            'key' => $this->key,
            'value' => $this->value,
            'formatted_value' => $this->formatted_value,
            'type' => $this->type,
            'data_type' => $this->data_type,
            'validation_rules' => $this->validation_rules,
            'options' => $this->options_array,
            'label' => $this->label,
            'description' => $this->description,
            'placeholder' => $this->placeholder,
            'sort_order' => $this->sort_order,
            'is_public' => (bool) $this->is_public,
            'is_encrypted' => (bool) $this->is_encrypted,
            'is_required' => (bool) $this->is_required,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
