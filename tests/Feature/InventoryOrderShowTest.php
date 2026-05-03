<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\InventorySupplierAssessment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryOrderShowTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        return Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
    }

    public function test_inventory_order_show_exposes_chart_payloads(): void
    {
        $user = $this->verifiedAdmin();
        $inventoryItem = InventoryItem::query()->firstOrFail();
        $warehouse = InventoryItemWarehouse::query()->firstOrFail();
        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor visual',
            'address' => 'Luanda',
            'currency' => 'AOA',
        ]);

        InventorySupplierAssessment::query()->create([
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
            'date' => now()->subDays(2)->toDateString(),
            'user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'order_year' => now()->format('Y'),
            'status' => 'ORDERED',
            'currency' => 'AOA',
            'reference' => 'PO-CHART-001',
            'total_amount' => 1000,
        ]);

        InventoryOrderDetail::query()->create([
            'order_id' => $order->id,
            'item_id' => $inventoryItem->id,
            'qty' => 10,
            'received_qty' => 4,
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
                ->where('charts.reception_progress.labels.0', 'Quantidade pedida')
                ->where('charts.item_status_mix.labels.0', 'Itens pendentes')
                ->where('charts.governance_summary.labels.0', 'Score fornecedor')
            );
    }
}
