<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventAttributeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'event_group_id'  => $this->event_group_id,
            'type'            => $this->type,
            'name'            => $this->name,
            'expected_amount' => $this->expected_amount !== null ? (float) $this->expected_amount : null,
            'actual_amount'   => $this->actual_amount !== null ? (float) $this->actual_amount : null,
            'content'         => $this->content,
            'is_done'         => (bool) $this->is_done,
            'due_date'        => $this->due_date?->format('Y-m-d'),
            'sort_order'      => $this->sort_order,
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
