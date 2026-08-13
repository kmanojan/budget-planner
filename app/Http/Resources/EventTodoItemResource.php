<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventTodoItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'event_attribute_id' => $this->event_attribute_id,
            'title'              => $this->title,
            'is_done'            => (bool) $this->is_done,
            'due_date'           => $this->due_date?->format('Y-m-d'),
            'sort_order'         => $this->sort_order,
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
