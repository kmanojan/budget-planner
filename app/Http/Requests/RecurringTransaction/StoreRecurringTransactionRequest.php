<?php

namespace App\Http\Requests\RecurringTransaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id'   => 'required|exists:accounts,id',
            'category_id'  => 'nullable|exists:categories,id',
            'type'         => 'required|in:income,expense',
            'amount'       => 'required|numeric|min:0.01',
            'notes'        => 'nullable|string|max:500',
            'frequency'    => 'required|in:daily,weekly,monthly,yearly',
            'start_date'   => 'required|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
        ];
    }
}
