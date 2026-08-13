<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'account_id' => ['sometimes', 'exists:accounts,id'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'type' => ['sometimes', new Enum(TransactionType::class)],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'transaction_date' => ['sometimes', 'date'],
            'transaction_time' => ['sometimes', 'date_format:H:i:s'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', new Enum(TransactionStatus::class)],
            'label_ids' => ['nullable', 'array'],
            'label_ids.*' => ['exists:labels,id'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpeg,png,jpg,gif,pdf', 'max:10240'],
            'remove_attachment_ids' => ['nullable', 'array'],
            'remove_attachment_ids.*' => ['exists:transaction_attachments,id'],
        ];

        $type = $this->input('type', $this->route('transaction')?->type?->value ?? null);
        if ($type === 'transfer') {
            $rules['transfer_to_account_id'] = ['sometimes', 'exists:accounts,id', 'different:account_id'];
            $rules['exchange_rate'] = ['nullable', 'numeric', 'min:0.000001'];
        }

        return $rules;
    }
}
