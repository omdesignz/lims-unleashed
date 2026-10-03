<?php

namespace Database\Factories;

use App\Models\LabNetwork;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabNetwork>
 */
class LabNetworkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Labs',
            'primary_color' => '#0757b5',
        ];
    }
}
