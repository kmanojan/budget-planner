<?php

namespace App\Http\Requests\BillReminder;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => 'required|string|max:255',
            'amount'             => 'required|numeric|min:0.01',
            'currency_code'      => 'nullable|string|size:3',
            'due_date'           => 'required|date',
            'category_id'        => 'nullable|exists:categories,id',
            'frequency'          => 'required|in:once,monthly,yearly',
            'remind_days_before' => 'nullable|integer|min:0|max:30',
        ];
    }
}
