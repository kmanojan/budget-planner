<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'type'            => $this->type,
            'amount'          => (float) $this->amount,
            'currency_code'   => $this->currency_code,
            'notes'           => $this->notes,
            'frequency'       => $this->frequency,
            'start_date'      => $this->start_date?->format('Y-m-d'),
            'end_date'        => $this->end_date?->format('Y-m-d'),
            'next_occurrence' => $this->next_occurrence?->format('Y-m-d'),
            'last_processed_at' => $this->last_processed_at?->format('Y-m-d'),
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
