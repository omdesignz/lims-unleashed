<?php

namespace Database\Factories;

use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VAPSampleEntry> */
class VAPSampleEntryFactory extends Factory
{
    protected $model = VAPSampleEntry::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'code' => fake()->unique()->bothify('AM-????-######'),
            'lab_id' => VAPLab::factory(),
            'status' => 'POR_INICIAR',
            'sample_type' => 'AGUA',
            'received_at' => now(),
            'retention_due_at' => now()->addDays(90)->toDateString(),
        ];
    }
}
