<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'type' => ['required', new Enum(TransactionType::class)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['required', 'date_format:H:i:s'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', new Enum(TransactionStatus::class)],
            'label_ids' => ['nullable', 'array'],
            'label_ids.*' => ['exists:labels,id'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpeg,png,jpg,gif,pdf', 'max:10240'], // 10MB max per file
        ];

        if ($this->input('type') === 'transfer') {
            $rules['transfer_to_account_id'] = ['required', 'exists:accounts,id', 'different:account_id'];
            $rules['exchange_rate'] = ['nullable', 'numeric', 'min:0.000001'];
        } else {
            $rules['category_id'] = ['required', 'exists:categories,id'];
        }

        return $rules;
    }
}
