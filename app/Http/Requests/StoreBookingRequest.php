<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Patient;
use App\Models\Schedule;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth will be handled by teammate later
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // accept numeric IDs or external codes (e.g. PAT-..., SCH-...)
            'schedule_id' => ['required'],
            'patient_id' => ['required'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'schedule_id.exists' => 'The selected schedule does not exist.',
            'patient_id.exists' => 'The selected patient does not exist.',
        ];
    }

    /**
     * Prepare the data for validation by resolving external codes to numeric IDs.
     */
    protected function prepareForValidation(): void
    {
        // Resolve patient_id (can be numeric id, NIK, or external patient_id like PAT-...)
        if ($this->has('patient_id') && !is_numeric($this->input('patient_id'))) {
            $val = $this->input('patient_id');

            // Route by format to use the correct unique index — avoids OR that skips indexes
            if (str_starts_with($val, 'PAT-')) {
                $patient = \App\Models\Patient::where('patient_id', $val)->first();
            } elseif (ctype_digit($val) && strlen($val) === 16) {
                $patient = \App\Models\Patient::where('nik', $val)->first();
            } else {
                // Fallback (e.g., partial NIK or name — unlikely but safe)
                $patient = \App\Models\Patient::where('patient_id', $val)
                    ->orWhere('nik', $val)
                    ->first();
            }

            if ($patient) {
                $this->merge(['patient_id' => $patient->id]);
            }
        }

        // Resolve schedule_id (can be numeric id or external schedule_id like SCH-...)
        if ($this->has('schedule_id') && !is_numeric($this->input('schedule_id'))) {
            $val = $this->input('schedule_id');
            $schedule = \App\Models\Schedule::where('schedule_id', $val)->first();

            if ($schedule) {
                $this->merge(['schedule_id' => $schedule->id]);
            }
        }
    }
}
