<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'province',
        'city',
        'district',
        'village',
        'latitude',
        'longitude',
        'phone',
        'capacity',
        'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // Relationships
    public function vaccineStocks(): HasMany
    {
        return $this->hasMany(VaccineStock::class);
    }

    public function vaccineSchedules(): HasMany
    {
        return $this->hasMany(VaccineSchedule::class);
    }
}
