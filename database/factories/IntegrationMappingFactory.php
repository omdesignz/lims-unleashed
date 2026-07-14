<?php

namespace Database\Factories;

use App\Models\IntegrationConnector;
use App\Models\IntegrationMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrationMapping>
 */
class IntegrationMappingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'connector_id' => IntegrationConnector::factory(),
            'name' => 'Default result mapping',
            'version' => 1,
            'is_active' => true,
            'field_paths' => [
                'external_id' => 'message.id',
                'sample_code' => 'result.sample_code',
                'parameter_code' => 'result.parameter_code',
                'value' => 'result.value',
                'unit' => 'result.unit',
                'measured_at' => 'result.measured_at',
            ],
            'transformations' => [],
            'constants' => [],
        ];
    }
}
