<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'health_center_id',
        'vaccine_id',
        'date',
        'start_time',
        'end_time',
        'quota',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Compatibility: some parts of the codebase expect a healthCenter relation
    public function healthCenter(): BelongsTo
    {
        return $this->belongsTo(HealthCenter::class, 'health_center_id');
    }

    // Also expose vaccine relation
    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class, 'vaccine_id');
    }

    // Legacy alias: keep faskes() for older code that may still call it
    public function faskes(): BelongsTo
    {
        return $this->belongsTo(HealthCenter::class, 'health_center_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}