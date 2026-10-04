<?php

namespace Database\Factories;

use App\Models\ControlChart;
use App\Models\VAPLab;
use App\Support\ControlChartEvaluation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControlChart>
 */
class ControlChartFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lab_id' => VAPLab::factory(),
            'name' => 'Carta '.fake()->unique()->bothify('CC-####'),
            'chart_type' => ControlChartEvaluation::MEAN,
            'method' => 'ISO '.fake()->numberBetween(1000, 30000),
            'control_material' => 'Material de controlo interno',
            'material_lot' => fake()->bothify('LT-####'),
            'unit' => 'mg/L',
            'centre_line' => 10,
            'standard_deviation' => 0.5,
            'limits_source' => 'entered',
            'limits_basis' => 'Valor certificado e desvio-padrão de reprodutibilidade intralaboratorial.',
            'limits_set_at' => now(),
            'status' => 'active',
        ];
    }

    /** A range chart of duplicates with a mean range of 0.4. */
    public function range(): static
    {
        return $this->state(fn (): array => [
            'chart_type' => ControlChartEvaluation::RANGE,
            'centre_line' => 0.4,
            'standard_deviation' => null,
        ]);
    }

    public function withoutLimits(): static
    {
        return $this->state(fn (): array => [
            'centre_line' => null,
            'standard_deviation' => null,
            'limits_source' => null,
            'limits_basis' => null,
            'limits_set_at' => null,
        ]);
    }
}
