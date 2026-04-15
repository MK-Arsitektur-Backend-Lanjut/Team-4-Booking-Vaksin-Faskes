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
        'address',
        'phone',
    ];

    /**
     * Get the schedules for this health center.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
