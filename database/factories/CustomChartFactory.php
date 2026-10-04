<?php

namespace Database\Factories;

use App\Models\CustomChart;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomChart>
 */
class CustomChartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'lab_id' => VAPLab::factory(),
            'title' => fake()->sentence(3),
            'dataset' => 'samples',
            'measure' => 'count',
            'dimension' => 'status',
            'split' => null,
            'kind' => 'column',
            'period' => '90d',
            'colors' => null,
            'position' => 0,
        ];
    }
}
