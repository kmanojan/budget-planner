<?php

namespace App\Http\Requests\SavingsGoal;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSavingsGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => 'sometimes|required|string|max:255',
            'target_amount'  => 'sometimes|required|numeric|min:0.01',
            'current_amount' => 'sometimes|nullable|numeric|min:0',
            'account_id'     => 'nullable|exists:accounts,id',
            'currency_code'  => 'sometimes|nullable|string|size:3',
            'deadline'       => 'nullable|date',
            'icon'           => 'sometimes|nullable|string|max:50',
            'color'          => 'sometimes|nullable|string|max:20',
            'is_completed'   => 'sometimes|boolean',
        ];
    }
}
