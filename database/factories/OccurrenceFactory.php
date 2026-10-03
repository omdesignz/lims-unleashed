<?php

namespace Database\Factories;

use App\Models\Occurrence;
use App\Models\VAPLab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Occurrence>
 */
class OccurrenceFactory extends Factory
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
            'occurrence_year' => (string) now()->year,
            'date_reported' => now()->toDateString(),
            'issue_description' => fake()->sentence(),
        ];
    }
}
