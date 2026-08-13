<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillReminderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'user_id'            => $this->user_id,
            'category_id'        => $this->category_id,
            'category_name'      => $this->category?->name,
            'category_icon'      => $this->category?->icon,
            'category_color'     => $this->category?->color,
            'name'               => $this->name,
            'amount'             => (float) $this->amount,
            'currency_code'      => $this->currency_code,
            'due_date'           => $this->due_date?->toDateString(),
            'frequency'          => $this->frequency,
            'remind_days_before' => $this->remind_days_before,
            'is_paid'            => (bool) $this->is_paid,
            'is_overdue'         => (bool) $this->is_overdue,
            'days_until_due'     => $this->days_until_due,
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
