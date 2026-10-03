<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryItemType;
use App\Models\ItemCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CalibrationScheduleReportTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $admin->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        return $admin;
    }

    public function test_calibration_schedule_exposes_filters_stats_and_metrology_context(): void
    {
        $user = $this->verifiedAdmin();
        $code = 'CAL-FEFO-'.uniqid();
        $serial = 'SER-CAL-'.uniqid();
        $category = ItemCategory::query()->create([
            'name' => 'Calibration category',
            'code' => fake()->unique()->bothify('CAL-######'),
        ]);
        $type = InventoryItemType::query()->create(['name' => 'Calibration equipment']);
        $item = InventoryItem::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Balança analítica rastreada',
            'code' => $code,
            'category_id' => $category->id,
            'type_id' => $type->id,
            'brand' => 'Mettler',
            'model' => 'XPR',
            'serial_number' => $serial,
            'location' => 'Sala de massas',
            'last_calibration_date' => now()->subDays(340)->toDateString(),
            'next_calibration_date' => now()->addDays(20)->toDateString(),
            'metrological_uncertainty_value' => 0.125,
            'metrological_uncertainty_unit' => 'mg',
            'metrological_traceability_reference' => 'CERT-ISO-17025-'.uniqid(),
        ]);

        $this->actingAs($user)
            ->get(route('vap-inventory.items.calibration.schedule', [
                'status' => 'due_soon',
                'category_id' => $category->id,
                'type_id' => $type->id,
                'search' => $serial,
                'sort_by' => 'last_calibration_date',
                'sort_direction' => 'desc',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Calibration/Schedule')
                ->where('filters.status', 'due_soon')
                ->where('filters.category_id', (string) $category->id)
                ->where('filters.type_id', (string) $type->id)
                ->where('filters.search', $serial)
                ->where('filters.sort_by', 'last_calibration_date')
                ->where('filters.sort_direction', 'desc')
                ->has('categories')
                ->has('types')
                ->has('stats.total_due')
                ->has('stats.due_soon')
                ->has('stats.due_31_90')
                ->has('stats.total_scheduled')
                ->has('items.data', 1)
                ->has('items.data.0', fn (Assert $row) => $row
                    ->where('id', $item->id)
                    ->where('name', 'Balança analítica rastreada')
                    ->where('code', $code)
                    ->where('serial_number', $serial)
                    ->where('brand', 'Mettler')
                    ->where('model', 'XPR')
                    ->where('location', 'Sala de massas')
                    ->where('days_to_calibration', fn ($days): bool => (int) $days >= 19 && (int) $days <= 20)
                    ->where('needs_calibration', false)
                    ->where('metrology_status', 'review_due')
                    ->where('is_metrologically_ready', true)
                    ->where('category.id', $category->id)
                    ->where('type.id', $type->id)
                    ->etc()
                )
            );
    }
}
