<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavingsGoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'user_id'                   => $this->user_id,
            'account_id'                => $this->account_id,
            'account_name'              => $this->account?->name,
            'name'                      => $this->name,
            'target_amount'             => (float) $this->target_amount,
            'current_amount'            => (float) $this->current_amount,
            'remaining_amount'          => (float) $this->remaining_amount,
            'progress_percentage'       => (float) $this->progress_percentage,
            'projected_completion_date' => $this->projected_completion_date,
            'currency_code'             => $this->currency_code,
            'deadline'                  => $this->deadline?->toDateString(),
            'icon'                      => $this->icon,
            'color'                     => $this->color,
            'is_completed'              => (bool) $this->is_completed,
            'created_at'                => $this->created_at?->toIso8601String(),
            'updated_at'                => $this->updated_at?->toIso8601String(),
        ];
    }
}
