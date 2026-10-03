<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryUnit;
use App\Models\ItemCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryValueReportTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    public function test_inventory_value_report_exposes_chart_payloads(): void
    {
        $user = $this->verifiedAdmin();
        $category = ItemCategory::query()->create(['name' => 'Consumíveis de ensaio']);
        $unit = InventoryUnit::query()->create(['code' => 'mL-'.fake()->numerify('######'), 'description' => 'Millilitres']);
        $item = InventoryItem::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Tracked report item',
            'category_id' => $category->id,
            'standard_cost' => 12.5,
            'unit_id' => $unit->id,
            'user_id' => $user->id,
        ]);
        $warehouse = InventoryItemWarehouse::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Report warehouse',
        ]);
        Inventory::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_available' => 10,
        ]);

        $response = $this->actingAs($user)->get(route('vap-inventory.reports.inventory-value'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Reports/InventoryValue')
                ->has('charts.category_value_breakdown.labels')
                ->has('charts.category_value_breakdown.series')
                ->has('charts.warehouse_value_breakdown.labels')
                ->has('charts.warehouse_value_breakdown.series')
                ->has('charts.top_item_value.labels')
                ->has('charts.top_item_value.series')
                ->where('inventory.data.0.item.unit.code', $unit->code)
                ->where('inventory.data.0.item.standard_cost', fn ($value): bool => (float) $value === 12.5)
                ->missing('summaryByCategory.0.total_quantity')
                ->where('charts.category_value_breakdown.labels.0', $category->name)
                ->where('charts.category_value_breakdown.series.0.data.0', fn ($value): bool => (float) $value === 125.0)
            );
    }

    public function test_inventory_value_csv_keeps_fractional_quantity_and_its_items_unit(): void
    {
        $user = $this->verifiedAdmin();
        $labId = DB::table('lab_user')->where('user_id', $user->id)->value('lab_id');
        $category = ItemCategory::query()->create(['name' => 'Measured materials']);
        $unit = InventoryUnit::query()->create(['code' => 'g-'.fake()->numerify('######'), 'description' => 'Grams']);
        $item = InventoryItem::query()->create([
            'lab_id' => $labId,
            'name' => 'Measured material',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'standard_cost' => '12.50',
        ]);
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $labId, 'name' => 'Measured warehouse']);
        Inventory::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_available' => '1.1250',
        ]);

        $response = $this->actingAs($user)->post(route('vap-inventory.reports.export'), [
            'report_type' => 'inventory_value',
            'format' => 'csv',
        ])->assertOk();

        $this->assertStringContainsString($unit->code, $response->getContent());
        $this->assertStringContainsString('1.1250', $response->getContent());
        $this->assertStringContainsString('12.5', $response->getContent());

        $filtered = $this->post(route('vap-inventory.reports.export'), [
            'report_type' => 'inventory_value',
            'format' => 'csv',
            'filters' => ['search' => 'not-this-material'],
        ])->assertOk();
        $this->assertStringNotContainsString($item->name, $filtered->getContent());
    }

    public function test_every_inventory_report_pdf_download_renders_without_a_missing_template(): void
    {
        $user = $this->verifiedAdmin();
        $this->actingAs($user);

        foreach (['stock_movement', 'consumption', 'inventory_value', 'low_stock'] as $reportType) {
            $response = $this->post(route('vap-inventory.reports.export'), [
                'report_type' => $reportType,
                'format' => 'pdf',
            ])->assertOk();

            $this->assertSame('%PDF', substr((string) $response->getContent(), 0, 4));
            $this->assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
        }
    }
}
