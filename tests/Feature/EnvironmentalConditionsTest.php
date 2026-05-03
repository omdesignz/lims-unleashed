<?php

namespace Tests\Feature;

use App\Models\EnvironmentalCondition;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EnvironmentalConditionsTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->first();

        $this->assertNotNull($admin, 'Expected at least one verified admin user for environmental condition testing.');

        return $admin;
    }

    public function test_admin_can_open_environmental_conditions_page(): void
    {
        $this->actingAs($this->verifiedAdmin())
            ->get(route('environmental-conditions.index'))
            ->assertOk();
    }

    public function test_admin_can_store_environmental_condition_record(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->post(route('environmental-conditions.store'), [
                'area' => 'Sala de preparação',
                'location' => 'Laboratório A',
                'recorded_at' => now()->format('Y-m-d H:i:s'),
                'temperature_c' => 22.5,
                'humidity_percent' => 44.3,
                'temperature_min_c' => 18,
                'temperature_max_c' => 25,
                'humidity_min_percent' => 35,
                'humidity_max_percent' => 60,
                'notes' => 'Condição estável durante o turno da manhã.',
            ])
            ->assertRedirect(route('environmental-conditions.index'))
            ->assertSessionHasNoErrors();

        $record = EnvironmentalCondition::query()
            ->where('area', 'Sala de preparação')
            ->latest('id')
            ->first();

        $this->assertNotNull($record);
        $this->assertSame('within_limits', $record->status);
        $this->assertSame($user->id, $record->recorded_by_id);
    }

    public function test_inventory_item_reports_metrology_hold_when_controls_are_overdue(): void
    {
        $category = ItemCategory::query()->firstOrFail();

        $item = InventoryItem::query()->create([
            'name' => 'Equipamento metrológico ' . uniqid(),
            'category_id' => $category->id,
            'next_calibration_date' => now()->subDay()->toDateString(),
            'metrology_review_due_at' => now()->subDay()->toDateString(),
            'metrological_uncertainty_value' => 0.125,
            'metrological_uncertainty_unit' => 'mg/L',
            'metrological_traceability_reference' => 'CERT-ISO-17025-001',
        ]);

        $this->assertSame('hold', $item->fresh()->metrology_status);
        $this->assertFalse($item->fresh()->is_metrologically_ready);
    }
}
