<?php

namespace App\Http\Requests\RecurringTransaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecurringTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id'   => 'sometimes|exists:accounts,id',
            'category_id'  => 'nullable|exists:categories,id',
            'type'         => 'sometimes|in:income,expense',
            'amount'       => 'sometimes|numeric|min:0.01',
            'notes'        => 'nullable|string|max:500',
            'frequency'    => 'sometimes|in:daily,weekly,monthly,yearly',
            'start_date'   => 'sometimes|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'is_active'    => 'sometimes|boolean',
        ];
    }
}
