<?php

namespace App\Http\Requests\Label;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'hex_color' => ['nullable', 'string', 'max:9'], // #AARRGGBB or #RRGGBB
            'is_pinned' => ['nullable', 'boolean'],
        ];
    }
}
