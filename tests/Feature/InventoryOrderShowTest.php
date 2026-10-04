<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\InventorySupplierAssessment;
use App\Models\InventoryUnit;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class InventoryOrderShowTest extends TestCase
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

    public function test_inventory_order_show_exposes_chart_payloads_and_available_quality_state(): void
    {
        $user = $this->verifiedAdmin();
        $inventoryItem = InventoryItem::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'unit_id' => InventoryUnit::query()->create(['description' => 'Order chart unit', 'code' => fake()->unique()->bothify('OC-######')])->id,
            'name' => 'Inventory order chart fixture',
            'code' => fake()->unique()->bothify('OC-######'),
        ]);
        $warehouse = InventoryItemWarehouse::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Inventory order warehouse',
        ]);
        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor visual',
            'address' => 'Luanda',
            'currency' => 'AOA',
        ]);

        InventorySupplierAssessment::query()->create([
            'lab_id' => $inventoryItem->lab_id,
            'inventory_item_supplier_id' => $supplier->id,
            'assessed_by_user_id' => $user->id,
            'assessment_date' => now()->subDays(3)->toDateString(),
            'next_review_at' => now()->addDays(20)->toDateString(),
            'status' => 'conditional',
            'risk_level' => 'high',
            'total_score' => 72,
            'delivery_score' => 4,
            'quality_score' => 4,
            'compliance_score' => 3,
            'responsiveness_score' => 4,
            'approved_supplier' => true,
            'is_active' => true,
        ]);

        $order = InventoryOrder::query()->create([
            'lab_id' => $inventoryItem->lab_id,
            'date' => now()->subDays(2)->toDateString(),
            'user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'order_year' => now()->format('Y'),
            'status' => 'ORDERED',
            'currency' => 'AOA',
            'reference' => 'PO-CHART-001',
            'total_amount' => 1000,
        ]);

        $orderItem = InventoryOrderDetail::query()->create([
            'order_id' => $order->id,
            'item_id' => $inventoryItem->id,
            'qty' => 10,
            'received_qty' => 5,
            'unit_price' => 100,
            'warehouse_id' => $warehouse->id,
            'status' => 'PARTIALLY_RECEIVED',
            'currency' => 'AOA',
        ]);

        $this->actingAs($user)
            ->get(route('vap-inventory.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Orders/Show')
                ->where('order.id', $order->id)
                ->where('order.items.0.id', $orderItem->id)
                ->where('order.items.0.received_qty', '5.0000')
                ->where('charts.reception_progress.labels.0', 'Linhas pedidas')
                ->where('charts.reception_progress.series.0', 1)
                ->where('charts.reception_progress.series.1', 1)
                ->where('charts.item_status_mix.labels.0', 'Itens pendentes')
                ->where('charts.governance_summary.labels.0', 'Score fornecedor')
                ->where('charts.governance_summary.labels.1', 'NC abertas')
                ->where('nonConformitiesAvailable', true)
            );

        $this->actingAs($user)
            ->get(route('vap-inventory.orders.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Orders/Index')
                ->where('nonConformitiesAvailable', true)
            );

        $pdfResponse = $this->actingAs($user)->get(route('vap-inventory.orders.export-pdf', $order));
        $pdfResponse->assertOk();
        $this->assertStringStartsWith('%PDF-', (string) $pdfResponse->baseResponse->getContent());

        $this->actingAs($user)
            ->post(route('vap-inventory.orders.receive', $order), [
                'items' => [['id' => $orderItem->id, 'received_qty' => 1]],
                'request_id' => (string) Str::uuid(),
                'receive_date' => now()->toDateString(),
                'register_non_conformity' => true,
                'non_conformity_title' => 'Receiving deviation',
                'non_conformity_description' => 'Damaged packaging',
            ])
            ->assertRedirect(route('vap-inventory.orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame('6.0000', $orderItem->fresh()->received_qty);
        $this->assertDatabaseHas('v_non_conformities', [
            'lab_id' => $order->lab_id,
            'title' => 'Receiving deviation',
            'occurrence_area' => 'procurement_receipt',
        ]);
    }

    public function test_procurement_pricing_schema_keeps_computed_line_totals(): void
    {
        $this->assertTrue(Schema::hasColumns('i_suppliers', ['currency']));
        $this->assertTrue(Schema::hasColumns('i_orders', ['currency', 'total_amount']));
        $this->assertTrue(Schema::hasColumns('i_order_details', ['received_qty', 'currency', 'unit_price', 'total_price']));

        $user = $this->verifiedAdmin();
        $supplier = InventoryItemSupplier::query()->create(['name' => 'Pricing supplier', 'currency' => 'AOA']);
        $item = InventoryItem::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Pricing item',
            'code' => fake()->unique()->bothify('PI-######'),
        ]);
        $warehouse = InventoryItemWarehouse::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Pricing warehouse',
        ]);
        $order = InventoryOrder::query()->create([
            'lab_id' => $item->lab_id,
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'order_year' => now()->format('Y'),
            'currency' => 'AOA',
        ]);
        $detail = InventoryOrderDetail::query()->create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty' => 3,
            'unit_price' => '12.3456',
            'currency' => 'AOA',
        ]);

        $this->assertSame('37.0368', $detail->fresh()->total_price);
        $detail->update(['qty' => 4]);
        $this->assertSame('49.3824', $detail->fresh()->total_price);
        $this->assertSame('0.0000', $detail->fresh()->received_qty);
    }

    public function test_procurement_pricing_rollback_refuses_to_erase_retained_supplier_data(): void
    {
        InventoryItemSupplier::query()->create(['name' => 'Retained supplier', 'currency' => 'AOA']);
        $migration = require database_path('migrations/2026_09_29_191706_add_procurement_pricing_columns_to_inventory_tables.php');

        try {
            $migration->down();
            $this->fail('Expected rollback to preserve retained procurement data.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cannot remove procurement pricing from retained records.', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('i_suppliers', 'currency'));
    }
}
