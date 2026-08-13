<?php

namespace App\Http\Requests\SavingsGoal;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavingsGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'target_amount'  => 'required|numeric|min:0.01',
            'current_amount' => 'nullable|numeric|min:0',
            'account_id'     => 'nullable|exists:accounts,id',
            'currency_code'  => 'nullable|string|size:3',
            'deadline'       => 'nullable|date',
            'icon'           => 'nullable|string|max:50',
            'color'          => 'nullable|string|max:20',
        ];
    }
}
