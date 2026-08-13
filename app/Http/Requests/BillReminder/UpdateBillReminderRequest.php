<?php

namespace App\Http\Requests\BillReminder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBillReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => 'sometimes|required|string|max:255',
            'amount'             => 'sometimes|required|numeric|min:0.01',
            'currency_code'      => 'sometimes|nullable|string|size:3',
            'due_date'           => 'sometimes|required|date',
            'category_id'        => 'nullable|exists:categories,id',
            'frequency'          => 'sometimes|required|in:once,monthly,yearly',
            'remind_days_before' => 'sometimes|nullable|integer|min:0|max:30',
            'is_paid'            => 'sometimes|boolean',
        ];
    }
}
