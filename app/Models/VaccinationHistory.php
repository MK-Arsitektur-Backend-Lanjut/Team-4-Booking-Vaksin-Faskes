<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaccinationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'vaccination_history_id',
        'patient_id',
        'vaccine_name',
        'dose_number',
        'vaccinated_at',
        'provider_name',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'vaccinated_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
