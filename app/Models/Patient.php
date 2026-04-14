<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'nik',
        'full_name',
        'birth_date',
        'gender',
        'phone_number',
        'address',
        'identity_verification_status',
        'identity_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'identity_verified_at' => 'datetime',
        ];
    }

    public function healthHistories(): HasMany
    {
        return $this->hasMany(HealthHistory::class);
    }

    public function vaccinationHistories(): HasMany
    {
        return $this->hasMany(VaccinationHistory::class);
    }
}
