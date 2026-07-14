<?php

namespace Database\Factories;

use App\Models\IntegrationConnector;
use App\Models\IntegrationDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IntegrationDelivery>
 */
class IntegrationDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'connector_id' => IntegrationConnector::factory([
                'direction' => 'outbound',
            ]),
            'event_type' => 'lims.result.validated',
            'idempotency_key' => (string) Str::uuid(),
            'payload' => ['type' => 'lims.result.validated'],
            'status' => 'pending',
            'attempts' => 0,
        ];
    }
}
