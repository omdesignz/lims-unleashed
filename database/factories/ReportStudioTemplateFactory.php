<?php

namespace Database\Factories;

use App\Models\ReportStudioTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportStudioTemplate>
 */
class ReportStudioTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'studio_type' => fake()->randomElement(['analysis', 'executive', 'proposal']),
            'renderer' => fake()->randomElement(['internal', 'canva']),
            'status' => fake()->randomElement(['draft', 'active']),
            'is_default' => false,
            'theme_preset' => fake()->randomElement(['corporate', 'executive', 'clinical']),
            'canva_design_url' => fake()->optional()->url(),
            'description' => fake()->paragraph(),
            'layout_schema' => [
                'header' => ['title' => fake()->sentence()],
                'sections' => [
                    ['key' => 'summary', 'visible' => true],
                    ['key' => 'results', 'visible' => true],
                ],
                'footer' => ['revisionBlock' => true],
            ],
            'export_settings' => [
                'format' => 'pdf',
                'orientation' => 'portrait',
            ],
        ];
    }
}
