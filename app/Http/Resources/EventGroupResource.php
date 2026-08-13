<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'event_id'    => $this->event_id,
            'name'        => $this->name,
            'type'        => $this->type ?? 'budget',
            'icon'        => $this->icon,
            'sort_order'  => $this->sort_order,
            'attributes'  => EventAttributeResource::collection($this->whenLoaded('attributes')),
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
