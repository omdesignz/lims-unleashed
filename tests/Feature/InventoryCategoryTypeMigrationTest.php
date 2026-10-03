<?php

namespace Tests\Feature;

use App\Models\VAPLab;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\IsolatedPostgresTestCase;

class InventoryCategoryTypeMigrationTest extends IsolatedPostgresTestCase
{
    public function test_backfill_preserves_id_based_classification_and_archived_usage_without_renumbering(): void
    {
        $schema = require database_path('migrations/2026_10_03_021553_add_explicit_type_to_item_categories.php');
        $backfill = require database_path('migrations/2026_10_03_021553_classify_existing_inventory_categories.php');
        $guards = require database_path('migrations/2026_10_03_021554_guard_used_inventory_category_types.php');
        $guards->down();
        $schema->down();
        $lab = VAPLab::factory()->create();
        DB::table('item_categories')->insert([
            ['id' => 1, 'name' => 'Renamed legacy equipment', 'code' => 'EQ', 'deleted_at' => now()],
            ['id' => 10, 'name' => 'equipamentos', 'code' => 'MT', 'deleted_at' => null],
        ]);
        DB::table('i_items')->insert([
            ['lab_id' => $lab->id, 'name' => 'Archived equipment', 'category_id' => 1, 'internal_code' => 'OLD-EQ-17', 'seq' => 17, 'deleted_at' => now()],
            ['lab_id' => $lab->id, 'name' => 'Material', 'category_id' => 10, 'internal_code' => 'OLD-MT-8', 'seq' => 8, 'deleted_at' => null],
        ]);
        $before = DB::table('i_items')->orderBy('id')->get()->toArray();
        $schema->up();
        $backfill->up();
        $guards->up();
        $this->assertSame('equipment', DB::table('item_categories')->where('id', 1)->value('inventory_type'));
        $this->assertSame('material', DB::table('item_categories')->where('id', 10)->value('inventory_type'));
        $this->assertSame([1, 10], DB::table('inventory_category_usage')->orderBy('category_id')->pluck('category_id')->all());
        $this->assertEquals($before, DB::table('i_items')->orderBy('id')->get()->toArray());
        foreach ([$guards, $schema] as $migration) {
            try {
                $migration->down();
                $this->fail('Retained first-use evidence must block rollback.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('retained', $exception->getMessage());
            }
        }
    }

    public function test_empty_schema_can_roll_back_and_replay_all_type_migrations(): void
    {
        $schema = require database_path('migrations/2026_10_03_021553_add_explicit_type_to_item_categories.php');
        $backfill = require database_path('migrations/2026_10_03_021553_classify_existing_inventory_categories.php');
        $guards = require database_path('migrations/2026_10_03_021554_guard_used_inventory_category_types.php');
        $guards->down();
        $schema->down();
        $schema->up();
        $backfill->up();
        $guards->up();
        $id = DB::table('item_categories')->insertGetId(['name' => 'First new category']);
        $this->assertSame('material', DB::table('item_categories')->where('id', $id)->value('inventory_type'));
        $this->assertSame(0, DB::table('inventory_category_usage')->count());
    }

    public function test_writes_between_schema_and_backfill_cannot_lose_prior_category_use(): void
    {
        $schema = require database_path('migrations/2026_10_03_021553_add_explicit_type_to_item_categories.php');
        $backfill = require database_path('migrations/2026_10_03_021553_classify_existing_inventory_categories.php');
        $guards = require database_path('migrations/2026_10_03_021554_guard_used_inventory_category_types.php');
        $guards->down();
        $schema->down();
        $lab = VAPLab::factory()->create();
        DB::table('item_categories')->insert([
            ['id' => 1, 'name' => 'Legacy equipment', 'code' => 'EQ'],
            ['id' => 10, 'name' => 'Legacy material', 'code' => 'MT'],
            ['id' => 11, 'name' => 'Legacy other material', 'code' => 'OM'],
        ]);
        $deleted = DB::table('i_items')->insertGetId(['lab_id' => $lab->id, 'name' => 'Purged during migration', 'category_id' => 1]);
        $reassigned = DB::table('i_items')->insertGetId(['lab_id' => $lab->id, 'name' => 'Reassigned during migration', 'category_id' => 10]);
        $schema->up();
        foreach ([1 => 'material', 10 => 'equipment'] as $id => $type) {
            try {
                DB::transaction(fn () => DB::table('item_categories')->where('id', $id)->update(['inventory_type' => $type]));
                $this->fail('Inter-migration writes must not change legacy classification.');
            } catch (QueryException $exception) {
                $this->assertSame('23514', $exception->getCode());
            }
        }
        DB::table('i_items')->where('id', $deleted)->delete();
        DB::table('i_items')->where('id', $reassigned)->update(['category_id' => 11]);
        $inserted = DB::table('item_categories')->insertGetId(['id' => 12, 'name' => 'New material during migration', 'code' => 'NEW']);
        $backfill->up();
        $guards->up();
        $this->assertSame([1, 10, 11], DB::table('inventory_category_usage')->orderBy('category_id')->pluck('category_id')->all());
        $this->assertSame('equipment', DB::table('item_categories')->where('id', 1)->value('inventory_type'));
        $this->assertSame('material', DB::table('item_categories')->where('id', $inserted)->value('inventory_type'));
        $this->assertDatabaseMissing('i_items', ['id' => $deleted]);
        $this->assertDatabaseHas('i_items', ['id' => $reassigned, 'category_id' => 11]);
    }
}
