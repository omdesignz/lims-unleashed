<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'description' => fake()->sentence(),
            'email' => fake()->unique()->safeEmail(),
            'contact' => fake()->unique()->phoneNumber(),
            'extension' => fake()->unique()->randomNumber(5),
            'code' => strtoupper(Str::random(4)),
        ];
    }
}
