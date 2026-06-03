<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaccineStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'health_center_id',
        'vaccine_id',
        'total_stock',
        'available_stock',
        'used_stock',
        'expiration_date',
    ];

    protected $casts = [
        'expiration_date' => 'date',
    ];

    // Relationships
    public function healthCenter(): BelongsTo
    {
        return $this->belongsTo(HealthCenter::class);
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class);
    }
}
