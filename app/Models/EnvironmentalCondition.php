<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class EnvironmentalCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_id',
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
            'lab_id' => 'integer',
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

    protected static function booted(): void
    {
        static::updating(function (self $condition): void {
            if ($condition->isDirty('lab_id')) {
                throw new LogicException('An environmental condition cannot change its owning laboratory.');
            }
        });
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
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
