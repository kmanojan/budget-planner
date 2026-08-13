<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'user_id'               => $this->user_id,
            'budget_id'             => $this->budget_id,
            'category_id'           => $this->category_id,
            'month_id'              => $this->month_id,
            'title'                 => $this->title,
            'event_date'            => $this->event_date?->format('Y-m-d'),
            'total_expected_budget' => (float) $this->total_expected_budget,
            'total_actual_budget'   => (float) $this->total_actual_budget,
            'status'                => $this->status,
            'budget'                => $this->whenLoaded('budget', function () {
                return [
                    'id'   => $this->budget->id,
                    'name' => $this->budget->name,
                ];
            }),
            'category'              => $this->whenLoaded('category', function () {
                return [
                    'id'    => $this->category->id,
                    'name'  => $this->category->name,
                    'icon'  => $this->category->icon,
                    'color' => $this->category->color,
                ];
            }),
            'groups'                => EventGroupResource::collection($this->whenLoaded('groups')),
            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];
    }
}
