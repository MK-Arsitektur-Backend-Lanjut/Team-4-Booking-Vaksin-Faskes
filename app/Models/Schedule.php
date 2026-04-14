<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'faskes_id',
        'service_type',
        'vaccine_name',
        'starts_at',
        'ends_at',
        'capacity',
        'booked_count',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function faskes(): BelongsTo
    {
        return $this->belongsTo(Faskes::class);
    }
}
