<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'account' => $this->whenLoaded('account', fn () => [
                'id' => $this->account->id,
                'name' => $this->account->name,
                'type' => $this->account->type->value,
                'currency_code' => $this->account->currency_code,
            ]),
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'transfer_to_account' => $this->whenLoaded('transferToAccount', fn () => $this->transferToAccount ? [
                'id' => $this->transferToAccount->id,
                'name' => $this->transferToAccount->name,
                'currency_code' => $this->transferToAccount->currency_code,
            ] : null),
            'type' => $this->type->value,
            'amount' => $this->amount,
            'exchange_rate' => $this->exchange_rate,
            'currency_code' => $this->currency_code,
            'notes' => $this->notes,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'transaction_time' => $this->transaction_time,
            'status' => $this->status->value,
            'labels' => $this->whenLoaded('labels', fn () => $this->labels->map(fn ($label) => [
                'id' => $label->id,
                'name' => $label->name,
            ])),
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'url' => \Illuminate\Support\Facades\Storage::url($attachment->file_path),
                'file_name' => $attachment->file_name,
                'file_type' => $attachment->file_type,
                'mime_type' => $attachment->mime_type,
                'file_size' => $attachment->file_size,
            ])),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
