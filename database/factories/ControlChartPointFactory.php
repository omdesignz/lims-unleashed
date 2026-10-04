<?php

namespace Database\Factories;

use App\Models\ControlChart;
use App\Models\ControlChartPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControlChartPoint>
 */
class ControlChartPointFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'control_chart_id' => ControlChart::factory(),
            'measured_at' => now()->subDays(fake()->numberBetween(1, 60)),
            'value' => fake()->randomFloat(3, 9.5, 10.5),
            'run_reference' => fake()->bothify('LOTE-###'),
            'excluded' => false,
        ];
    }
}
