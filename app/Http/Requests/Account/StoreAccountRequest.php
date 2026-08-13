<?php

namespace App\Http\Requests\Account;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(AccountType::class)],
            'currency_code' => ['required', 'string', 'max:10'],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'color' => ['nullable', 'string', 'max:10'],
            'icon' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
