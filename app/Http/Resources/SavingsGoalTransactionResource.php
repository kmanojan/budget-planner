<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavingsGoalTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'savings_goal_id'  => $this->savings_goal_id,
            'type'             => $this->type,
            'amount'           => (float) $this->amount,
            'note'             => $this->note,
            'transaction_date' => $this->transaction_date ? $this->transaction_date->toDateTimeString() : null,
            'balance_after'    => (float) $this->balance_after,
            'created_at'       => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
