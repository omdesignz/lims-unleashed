<?php

namespace Tests\Feature;

use App\Actions\CreateInventoryItem;
use App\Enums\InventoryCategoryType;
use App\Models\InventoryUnit;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryCatalogueImportTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('requiredTypes')]
    public function test_canonical_creation_enforces_the_import_kind_inside_its_transaction(string $categoryType, InventoryCategoryType $requiredType, bool $allowed): void
    {
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $user->givePermissionTo(Permission::findOrCreate('add_iitems', 'web'), Permission::findOrCreate('add_iequipments', 'web'));
        $category = ItemCategory::query()->create(['name' => fake()->uuid(), 'inventory_type' => $categoryType]);
        $unit = InventoryUnit::query()->create(['code' => fake()->uuid(), 'name' => fake()->uuid()]);
        $payload = ['name' => 'Imported item '.fake()->uuid(), 'category_id' => $category->id, 'unit_id' => $unit->id];
        $beforeCounters = $this->rows('sequence_counters', 'scope_hash');
        $beforeItems = $this->rows('i_items', 'id');
        try {
            $item = app(CreateInventoryItem::class)->execute($lab->id, $user->id, $payload, $requiredType);
            $this->assertTrue($allowed, 'A mismatched import category must be rejected, even with both creation permissions.');
            $this->assertSame($lab->id, $item->lab_id);
            $this->assertSame($category->id, $item->category_id);
            $this->assertSame($requiredType, $item->fresh()->category->inventory_type);
        } catch (ValidationException $exception) {
            $this->assertFalse($allowed);
            $this->assertArrayHasKey('category_id', $exception->errors());
            $this->assertSame($beforeCounters, $this->rows('sequence_counters', 'scope_hash'));
            $this->assertSame($beforeItems, $this->rows('i_items', 'id'));
            $this->assertFalse($category->fresh()->typeIsLocked());
        }
    }

    /** @return array<string,array{string,InventoryCategoryType,bool}> */
    public static function requiredTypes(): array
    {
        return ['equipment accepted' => ['equipment', InventoryCategoryType::EQUIPMENT, true],
            'material cannot enter equipment import' => ['material', InventoryCategoryType::EQUIPMENT, false],
            'material accepted' => ['material', InventoryCategoryType::MATERIAL, true],
            'equipment cannot enter material import' => ['equipment', InventoryCategoryType::MATERIAL, false]];
    }

    /** @return list<array<string,mixed>> */
    private function rows(string $table, string $key): array
    {
        return DB::table($table)->orderBy($key)->get()->map(fn (object $row): array => (array) $row)->all();
    }
}
