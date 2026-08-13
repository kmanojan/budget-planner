<?php

namespace App\Http\Requests\Account;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', new Enum(AccountType::class)],
            'currency_code' => ['sometimes', 'string', 'max:10'],
            'balance' => ['sometimes', 'numeric'],
            'color' => ['sometimes', 'string', 'max:10'],
            'icon' => ['sometimes', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
