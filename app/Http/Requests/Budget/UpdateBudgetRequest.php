<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => 'sometimes|string|max:100',
            'amount'          => 'sometimes|numeric|min:0.01',
            'category_id'     => 'nullable|exists:categories,id',
            'account_id'      => 'nullable|exists:accounts,id',
            'period'          => 'sometimes|in:weekly,monthly,yearly',
            'currency_code'   => 'nullable|string|max:10',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'alert_threshold' => 'nullable|integer|min:1|max:100',
            'is_active'       => 'sometimes|boolean',
        ];
    }
}
