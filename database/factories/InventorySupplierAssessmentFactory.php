<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\InventoryItemSupplier;
use App\Models\InventorySupplierAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventorySupplierAssessment>
 */
class InventorySupplierAssessmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_item_supplier_id' => InventoryItemSupplier::query()->value('id') ?? InventoryItemSupplier::query()->create([
                'name' => fake()->company(),
                'address' => fake()->address(),
                'currency' => 'AOA',
            ])->id,
            'department_id' => Department::query()->value('id'),
            'assessed_by_user_id' => User::query()->value('id'),
            'assessment_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'next_review_at' => fake()->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'status' => fake()->randomElement(['approved', 'conditional', 'suspended']),
            'risk_level' => fake()->randomElement(['low', 'medium', 'high']),
            'total_score' => fake()->numberBetween(60, 96),
            'delivery_score' => fake()->numberBetween(2, 5),
            'quality_score' => fake()->numberBetween(2, 5),
            'compliance_score' => fake()->numberBetween(2, 5),
            'responsiveness_score' => fake()->numberBetween(2, 5),
            'evidence_reference' => 'SUP-' . fake()->numerify('####'),
            'approved_supplier' => true,
            'is_active' => true,
            'strengths' => fake()->sentence(),
            'gaps' => fake()->sentence(),
            'corrective_actions' => fake()->sentence(),
            'follow_up_actions' => fake()->sentence(),
            'notes' => fake()->sentence(),
        ];
    }
}
