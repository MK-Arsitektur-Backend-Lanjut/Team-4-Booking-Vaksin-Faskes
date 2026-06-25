<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVaccinationHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vaccine_name' => ['sometimes', 'required', 'string', 'max:255'],
            'dose_number' => ['sometimes', 'required', 'integer', 'min:1', 'max:20'],
            'vaccinated_at' => ['sometimes', 'required', 'date'],
            'provider_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
