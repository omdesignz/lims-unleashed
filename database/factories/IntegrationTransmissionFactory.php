<?php

namespace Database\Factories;

use App\Models\IntegrationConnector;
use App\Models\IntegrationTransmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrationTransmission>
 */
class IntegrationTransmissionFactory extends Factory
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
            'external_id' => fake()->unique()->uuid(),
            'direction' => 'inbound',
            'status' => 'quarantined',
            'checksum' => hash('sha256', fake()->uuid()),
            'content_type' => 'application/json',
            'raw_payload' => '{}',
            'received_at' => now(),
        ];
    }
}
