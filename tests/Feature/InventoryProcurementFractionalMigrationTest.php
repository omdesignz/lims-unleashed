<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryNeed;
use App\Models\InventoryNeedItem;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class InventoryProcurementFractionalMigrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_procurement_columns_replay_with_four_decimal_scale(): void
    {
        $migration = require database_path('migrations/2026_09_30_195703_support_fractional_procurement_quantities.php');
        $migration->down();

        try {
            $this->assertSame(0, $this->numericScale('inventory_need_items', 'quantity_requested'));
            $this->assertSame(0, $this->numericScale('i_order_details', 'qty'));
            $this->assertSame(0, $this->numericScale('i_delivery_details', 'qty'));
        } finally {
            $migration->up();
        }

        foreach (['quantity_requested', 'quantity_approved', 'quantity_received'] as $column) {
            $this->assertSame(4, $this->numericScale('inventory_need_items', $column));
        }

        foreach (['qty', 'received_qty'] as $column) {
            $this->assertSame(4, $this->numericScale('i_order_details', $column));
        }

        $this->assertSame(4, $this->numericScale('i_delivery_details', 'qty'));
    }

    public function test_procurement_columns_cannot_be_reversed_while_lines_are_retained(): void
    {
        $lab = VAPLab::factory()->create();
        $department = Department::query()->create(['name' => 'Fractional procurement', 'code' => 'FP-'.fake()->numerify('######')]);
        $user = User::factory()->create();
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Procurement quantity item']);
        $need = InventoryNeed::query()->create([
            'reference' => 'NEED-FRACTIONAL-'.fake()->numerify('######'),
            'department_id' => $department->id,
            'lab_id' => $lab->id,
            'requested_by_id' => $user->id,
        ]);
        InventoryNeedItem::query()->create([
            'inventory_need_id' => $need->id,
            'inventory_item_id' => $item->id,
            'quantity_requested' => '0.0001',
        ]);

        $migration = require database_path('migrations/2026_09_30_195703_support_fractional_procurement_quantities.php');
        $this->expectException(RuntimeException::class);
        $migration->down();
    }

    private function numericScale(string $table, string $column): ?int
    {
        $result = DB::selectOne('SELECT numeric_scale FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?', [$table, $column]);

        return $result?->numeric_scale === null ? null : (int) $result->numeric_scale;
    }
}
