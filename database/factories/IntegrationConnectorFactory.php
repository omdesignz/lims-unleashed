<?php

namespace Database\Factories;

use App\Models\IntegrationConnector;
use App\Models\VAPLab;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IntegrationConnector>
 */
class IntegrationConnectorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lab_id' => VAPLab::factory(),
            'uuid' => (string) Str::uuid(),
            'name' => fake()->company().' analyzer',
            'key' => fake()->unique()->slug(2),
            'direction' => 'inbound',
            'adapter' => 'rest_json',
            'status' => 'active',
            'health_status' => 'unknown',
            'configuration' => [],
            'event_types' => [],
        ];
    }
}
