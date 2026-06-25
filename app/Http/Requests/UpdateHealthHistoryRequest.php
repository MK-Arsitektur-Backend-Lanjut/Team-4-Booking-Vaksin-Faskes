<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHealthHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'condition_name' => ['sometimes', 'required', 'string', 'max:255'],
            'diagnosed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
