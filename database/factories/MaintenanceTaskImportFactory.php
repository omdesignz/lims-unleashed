<?php

namespace Database\Factories;

use App\Models\MaintenanceTaskImport;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceTaskImport>
 */
class MaintenanceTaskImportFactory extends Factory
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
            'user_id' => User::factory(),
            'file_hash' => hash('sha256', fake()->uuid()),
            'row_count' => 1,
            'task_ids' => [fake()->numberBetween(1, 1000)],
        ];
    }
}
