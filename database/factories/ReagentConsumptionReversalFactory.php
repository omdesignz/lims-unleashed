<?php

namespace Database\Factories;

use App\Models\InventoryTransaction;
use App\Models\ReagentConsumption;
use App\Models\ReagentConsumptionReversal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReagentConsumptionReversal>
 */
class ReagentConsumptionReversalFactory extends Factory
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
            'reversed_at' => now(),
        ];
    }

    public function forConsumption(ReagentConsumption $consumption, InventoryTransaction $movement): static
    {
        return $this->state(fn (): array => ['lab_id' => $consumption->lab_id, 'consumption_id' => $consumption->id,
            'inventory_transaction_id' => $movement->id]);
    }
}
