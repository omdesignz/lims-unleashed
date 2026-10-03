<?php

namespace Database\Factories;

use App\Models\VAPLab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VAPLab>
 */
class VAPLabFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Laboratory',
            'code' => fake()->unique()->bothify('LAB-????-####'),
        ];
    }
}
