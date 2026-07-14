<?php

namespace App\Models;

use Database\Factories\IntegrationMappingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationMapping extends Model
{
    /** @use HasFactory<IntegrationMappingFactory> */
    use HasFactory;

    public const SUPPORTED_FIELDS = [
        'external_id',
        'sample_code',
        'parameter_code',
        'value',
        'unit',
        'measured_at',
        'instrument_serial',
        'operator',
    ];

    protected $fillable = [
        'connector_id',
        'created_by_id',
        'name',
        'version',
        'is_active',
        'field_paths',
        'transformations',
        'constants',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'field_paths' => 'array',
            'transformations' => 'array',
            'constants' => 'array',
        ];
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnector::class, 'connector_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function transmissions(): HasMany
    {
        return $this->hasMany(IntegrationTransmission::class, 'mapping_id');
    }
}
