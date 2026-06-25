<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVaccinationHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vaccine_name' => ['required', 'string', 'max:255'],
            'dose_number' => ['required', 'integer', 'min:1', 'max:20'],
            'vaccinated_at' => ['required', 'date'],
            'provider_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
