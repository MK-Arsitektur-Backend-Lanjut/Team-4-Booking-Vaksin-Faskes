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
    ];

    /**
     * Get the schedules using this vaccine.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
