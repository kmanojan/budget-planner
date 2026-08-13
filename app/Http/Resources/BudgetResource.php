<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'amount'          => (float) $this->amount,
            'spent'           => (float) ($this->spent ?? 0),
            'remaining'       => (float) ($this->remaining ?? 0),
            'percentage'      => (float) ($this->percentage ?? 0),
            'is_exceeded'     => (bool) ($this->is_exceeded ?? false),
            'is_warning'      => (bool) ($this->is_warning ?? false),
            'period'          => $this->period,
            'currency_code'   => $this->currency_code,
            'alert_threshold' => $this->alert_threshold,
            'start_date'      => $this->start_date?->format('Y-m-d'),
            'end_date'        => $this->end_date?->format('Y-m-d'),
            'period_from'     => $this->period_from ?? null,
            'period_to'       => $this->period_to ?? null,
            'is_active'       => $this->is_active,
            'category'        => $this->whenLoaded('category', fn() => [
                'id'    => $this->category->id,
                'name'  => $this->category->name,
                'icon'  => $this->category->icon,
                'color' => $this->category->color,
                'type'  => $this->category->type,
            ]),
            'account'         => $this->whenLoaded('account', fn() => [
                'id'   => $this->account->id,
                'name' => $this->account->name,
                'icon' => $this->account->icon,
            ]),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
