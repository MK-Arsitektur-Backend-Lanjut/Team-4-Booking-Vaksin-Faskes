<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vaccine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'doses_required',
        'days_between_doses',
        'manufacturer',
        'status',
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
