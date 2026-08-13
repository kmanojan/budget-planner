<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventBudgetItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'event_attribute_id' => $this->event_attribute_id,
            'label'              => $this->label,
            'expected_amount'    => (float) $this->expected_amount,
            'actual_amount'      => $this->actual_amount !== null ? (float) $this->actual_amount : null,
            'notes'              => $this->notes,
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
