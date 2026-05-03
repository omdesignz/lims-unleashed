<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentalCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'area',
        'location',
        'recorded_at',
        'temperature_c',
        'humidity_percent',
        'pressure_kpa',
        'co2_ppm',
        'temperature_min_c',
        'temperature_max_c',
        'humidity_min_percent',
        'humidity_max_percent',
        'status',
        'notes',
        'recorded_by_id',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'temperature_c' => 'decimal:2',
            'humidity_percent' => 'decimal:2',
            'pressure_kpa' => 'decimal:2',
            'co2_ppm' => 'decimal:2',
            'temperature_min_c' => 'decimal:2',
            'temperature_max_c' => 'decimal:2',
            'humidity_min_percent' => 'decimal:2',
            'humidity_max_percent' => 'decimal:2',
        ];
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function evaluateStatus(): string
    {
        $temperatureOutOfRange = $this->temperature_c !== null && (
            ($this->temperature_min_c !== null && (float) $this->temperature_c < (float) $this->temperature_min_c)
            || ($this->temperature_max_c !== null && (float) $this->temperature_c > (float) $this->temperature_max_c)
        );

        $humidityOutOfRange = $this->humidity_percent !== null && (
            ($this->humidity_min_percent !== null && (float) $this->humidity_percent < (float) $this->humidity_min_percent)
            || ($this->humidity_max_percent !== null && (float) $this->humidity_percent > (float) $this->humidity_max_percent)
        );

        if ($temperatureOutOfRange || $humidityOutOfRange) {
            return 'critical';
        }

        if (
            $this->temperature_c === null
            && $this->humidity_percent === null
            && $this->pressure_kpa === null
            && $this->co2_ppm === null
        ) {
            return 'pending';
        }

        return 'within_limits';
    }
}
