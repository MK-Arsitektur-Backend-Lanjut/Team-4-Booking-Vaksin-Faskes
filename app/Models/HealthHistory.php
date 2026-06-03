<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'health_history_id',
        'patient_id',
        'condition_name',
        'diagnosed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'diagnosed_at' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
