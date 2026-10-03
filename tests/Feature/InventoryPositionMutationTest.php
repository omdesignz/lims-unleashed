<?php

namespace Tests\Feature;

use App\Actions\MutateInventoryPositions;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryUnit;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryPositionMutationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_archive_and_restore_are_non_get_idempotent_and_preserve_identity(): void
    {
        [$lab, $user, $position] = $this->fixture();
        $before = (array) DB::table('inventory')->where('id', $position->id)->first();
        $this->get(route('inventory.destroy', ['recordIds' => [$position->id]]))->assertMethodNotAllowed();
        $this->get(route('inventory.restore', ['recordIds' => [$position->id]]))->assertMethodNotAllowed();
        $this->delete(route('inventory.destroy'), ['recordIds' => [$position->id]])->assertRedirect();
        $archived = (array) DB::table('inventory')->where('id', $position->id)->first();
        $this->assertNotNull($archived['deleted_at']);
        $this->delete(route('inventory.destroy'), ['recordIds' => [$position->id]])->assertRedirect();
        $this->assertSame($archived, (array) DB::table('inventory')->where('id', $position->id)->first());
        $this->patch(route('inventory.restore'), ['recordIds' => [$position->id]])->assertRedirect();
        $restored = (array) DB::table('inventory')->where('id', $position->id)->first();
        $this->assertNull($restored['deleted_at']);
        $this->patch(route('inventory.restore'), ['recordIds' => [$position->id]])->assertRedirect();
        $this->assertSame($restored, (array) DB::table('inventory')->where('id', $position->id)->first());
        unset($before['deleted_at'], $before['updated_at'], $restored['deleted_at'], $restored['updated_at']);
        $this->assertSame($before, $restored);
    }

    #[DataProvider('writeFaults')]
    public function test_metadata_and_bulk_lifecycle_faults_roll_back_every_position(string $operation, string $fault): void
    {
        [$lab, $user, $first] = $this->fixture();
        $second = $this->position($lab);
        if ($operation === 'restore') {
            DB::table('inventory')->whereIn('id', [$first->id, $second->id])->update(['deleted_at' => now()]);
        }
        $before = $this->snapshot();
        $event = match ($operation) {
            'archive' => 'deleting', 'restore' => 'restoring', default => 'updating'
        };
        Inventory::$event(function (Inventory $position) use ($fault, $operation, $first, $second, $user, $lab): ?bool {
            $target = $operation === 'update' ? $first->id : $second->id;
            if ($position->id !== $target) {
                return null;
            }
            if ($fault === 'veto') {
                return false;
            }
            if ($fault === 'quantity') {
                DB::table('inventory')->where('id', $position->id)->update(['qty_available' => '99.0000']);
            } elseif ($fault === 'membership') {
                DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete();
            }

            return null;
        });
        $data = ['recordIds' => [$second->id, $first->id]];
        if ($operation === 'update') {
            $this->putJson(route('inventory.update', $first), $this->metadata($first))->assertStatus($fault === 'membership' ? 403 : 409);
        } elseif ($operation === 'archive') {
            $this->deleteJson(route('inventory.destroy'), $data)->assertStatus($fault === 'membership' ? 403 : 409);
        } else {
            $this->patchJson(route('inventory.restore'), $data)->assertStatus($fault === 'membership' ? 403 : 409);
        }
        $this->assertSame($before, $this->snapshot());
        $this->assertDatabaseHas('lab_user', ['lab_id' => $lab->id, 'user_id' => $user->id]);
    }

    public static function writeFaults(): array
    {
        $cases = [];
        foreach (['update', 'archive', 'restore'] as $operation) {
            foreach (['veto', 'quantity', 'membership'] as $fault) {
                $cases[$operation.' '.$fault] = [$operation, $fault];
            }
        }

        return $cases;
    }

    public function test_mixed_lab_bulk_selection_and_forged_identity_make_no_partial_changes(): void
    {
        [$lab, , $first] = $this->fixture();
        $peer = $this->position(VAPLab::factory()->create());
        $before = $this->snapshot();
        $this->deleteJson(route('inventory.destroy'), ['recordIds' => [$first->id, $peer->id]])->assertNotFound();
        $this->patchJson(route('inventory.restore'), ['recordIds' => [$first->id, $peer->id]])->assertNotFound();
        $this->putJson(route('inventory.update', $first), array_replace($this->metadata($first), ['item_id' => $peer->item_id]))
            ->assertUnprocessable();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_metadata_replay_keeps_timestamp_and_stock_and_revoked_actor_cannot_write(): void
    {
        [$lab, $user, $position] = $this->fixture();
        $data = $this->metadata($position);
        $this->putJson(route('inventory.update', $position), $data)->assertRedirect();
        $before = $this->snapshot();
        $this->putJson(route('inventory.update', $position), $data)->assertRedirect();
        $this->assertSame($before, $this->snapshot());
        DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete();
        $this->deleteJson(route('inventory.destroy'), ['recordIds' => [$position->id]])->assertForbidden();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_internal_action_cannot_bypass_operation_permissions(): void
    {
        [$lab, $user, $position] = $this->fixture();
        $user->revokePermissionTo('delete_inventory');
        $before = $this->snapshot();
        try {
            app(MutateInventoryPositions::class)->execute($lab->id, $user->id, [$position->id], 'archive');
            $this->fail('Archive requires its own current permission.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $this->snapshot());
        }
    }

    private function fixture(): array
    {
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        foreach (['edit_inventory', 'delete_inventory', 'restore_inventory'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        return [$lab, $user, $this->position($lab)];
    }

    private function position(VAPLab $lab): Inventory
    {
        $category = ItemCategory::query()->create(['name' => fake()->uuid(), 'inventory_type' => 'material']);
        $unit = InventoryUnit::query()->create(['code' => fake()->uuid(), 'description' => 'Units']);
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => fake()->uuid(), 'category_id' => $category->id, 'unit_id' => $unit->id]);
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => fake()->uuid()]);

        return Inventory::query()->create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id,
            'qty_available' => '0.0000', 'min_stock_level' => '0.0000', 'reorder_point' => '0.0000']);
    }

    private function metadata(Inventory $position): array
    {
        return ['item_id' => $position->item_id, 'warehouse_id' => $position->warehouse_id,
            'min_stock_level' => '0.1250', 'reorder_point' => '0.5000'];
    }

    private function snapshot(): array
    {
        return DB::table('inventory')->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
    }
}
