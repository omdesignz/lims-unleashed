<?php

namespace Tests\Feature;

use App\Actions\SaveInventoryCategory;
use App\Enums\InventoryCategoryType;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InventoryCategoryTypeTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('types')]
    public function test_categories_have_an_explicit_type_and_can_change_before_use(string $type): void
    {
        $user = $this->operator();
        $data = $this->payload($type);
        $this->postJson(route('itemcategories.store'), $data)->assertRedirect();
        $category = ItemCategory::query()->where('code', $data['code'])->sole();
        $this->assertSame($type, $category->inventory_type->value);
        $this->assertFalse($category->typeIsLocked());
        $data['inventory_type'] = $type === 'material' ? 'equipment' : 'material';
        $this->putJson(route('vap-inventory.master.categories.update', $category), $data)->assertRedirect();
        $this->assertSame($data['inventory_type'], $category->fresh()->inventory_type->value);
        $this->assertSame($user->id, auth()->id());
    }

    /** @return array<string,array{string}> */
    public static function types(): array
    {
        return ['material' => ['material'], 'equipment' => ['equipment']];
    }

    #[DataProvider('retentionStates')]
    public function test_first_item_use_permanently_freezes_type_across_labs_and_archive_states(string $state): void
    {
        $this->operator();
        $category = ItemCategory::query()->create($this->payload('equipment'));
        $peer = VAPLab::factory()->create();
        $item = InventoryItem::query()->create(['lab_id' => $peer->id, 'category_id' => $category->id, 'name' => 'Peer equipment']);
        if ($state === 'archived') {
            $item->delete();
        } elseif ($state === 'purged') {
            $item->forceDelete();
        } elseif ($state === 'reassigned') {
            DB::table('i_items')->where('id', $item->id)->update(['category_id' => ItemCategory::query()->create($this->payload('material'))->id]);
        }
        $this->assertTrue($category->typeIsLocked());
        $data = $category->getAttributes();
        $data['inventory_type'] = 'material';
        $this->putJson(route('itemcategories.update', $category), $data)->assertUnprocessable()->assertJsonValidationErrors('inventory_type');
        $data['inventory_type'] = 'equipment';
        $data['description'] = 'Metadata remains editable';
        $this->putJson(route('vap-inventory.master.categories.update', $category), $data)->assertRedirect();
        $this->assertSame('Metadata remains editable', $category->fresh()->description);
        $this->assertSame(InventoryCategoryType::EQUIPMENT, $category->fresh()->inventory_type);
    }

    /** @return array<string,array{string}> */
    public static function retentionStates(): array
    {
        return array_combine(['active', 'archived', 'purged', 'reassigned'], array_map(fn (string $state): array => [$state], ['active', 'archived', 'purged', 'reassigned']));
    }

    #[DataProvider('invalidTypes')]
    public function test_invalid_types_are_not_coerced(mixed $type): void
    {
        $this->operator();
        $data = $this->payload('material');
        $data['inventory_type'] = $type;
        $this->postJson(route('itemcategories.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('inventory_type');
        $this->assertDatabaseMissing('item_categories', ['code' => $data['code']]);
    }

    /** @return array<string,array{mixed}> */
    public static function invalidTypes(): array
    {
        return ['null' => [null], 'empty' => [''], 'boolean' => [true], 'integer' => [1], 'array' => [['equipment']], 'unknown' => ['reagent']];
    }

    public function test_raw_writers_cannot_change_used_type_or_erase_usage_evidence(): void
    {
        $lab = VAPLab::factory()->create();
        $category = ItemCategory::query()->create($this->payload('material'));
        DB::table('i_items')->insert(['name' => 'Raw item', 'lab_id' => $lab->id, 'category_id' => $category->id]);
        foreach ([
            fn () => DB::table('item_categories')->where('id', $category->id)->update(['inventory_type' => 'equipment']),
            fn () => DB::table('item_categories')->where('id', $category->id)->update(['inventory_type' => 'unknown']),
            fn () => DB::table('inventory_category_usage')->where('category_id', $category->id)->delete(),
            fn () => DB::table('inventory_category_usage')->where('category_id', $category->id)->update(['first_used_at' => now()->subYear()]),
            fn () => DB::statement('TRUNCATE inventory_category_usage'),
            fn () => DB::statement('TRUNCATE i_items CASCADE'),
        ] as $write) {
            $this->assertConstraintFails($write);
        }
        $this->assertSame(InventoryCategoryType::MATERIAL, $category->fresh()->inventory_type);
    }

    #[DataProvider('operations')]
    public function test_save_veto_and_hook_tampering_roll_back(bool $creating): void
    {
        $this->operator();
        $category = ItemCategory::query()->create($this->payload('material'));
        foreach (['veto', 'tamper', 'usage'] as $fault) {
            $before = DB::table('item_categories')->orderBy('id')->get()->toArray();
            $usageBefore = DB::table('inventory_category_usage')->orderBy('category_id')->get()->toArray();
            $event = $fault === 'usage' ? 'saved' : 'saving';
            ItemCategory::{$event}(function (ItemCategory $model) use ($fault, $category): ?bool {
                if ($fault === 'veto') {
                    return false;
                }
                if ($fault === 'usage') {
                    DB::table('inventory_category_usage')->insert(['category_id' => $category->id]);
                } else {
                    $model->name = 'Unexpected hook name';
                }

                return null;
            });
            try {
                $data = $this->payload('equipment');
                if ($creating) {
                    $this->postJson(route('itemcategories.store'), $data)->assertStatus(409);
                } else {
                    $this->putJson(route('itemcategories.update', $category), $data)->assertStatus(409);
                }
                $this->assertEquals($before, DB::table('item_categories')->orderBy('id')->get()->toArray());
                $this->assertEquals($usageBefore, DB::table('inventory_category_usage')->orderBy('category_id')->get()->toArray());
            } finally {
                ItemCategory::flushEventListeners();
                ItemCategory::clearBootedModels();
            }
        }
    }

    /** @return array<string,array{bool}> */
    public static function operations(): array
    {
        return ['create' => [true], 'update' => [false]];
    }

    public function test_type_is_not_inherited_from_parent_and_hierarchy_cycles_are_rejected(): void
    {
        $user = $this->operator();
        $parent = ItemCategory::query()->create($this->payload('equipment'));
        $data = $this->payload('material') + ['parent_id' => ['value' => $parent->id, 'label' => $parent->name]];
        $this->postJson(route('itemcategories.store'), $data)->assertRedirect();
        $child = ItemCategory::query()->where('code', $data['code'])->sole();
        $this->assertSame(InventoryCategoryType::MATERIAL, $child->inventory_type);
        $this->assertSame($parent->id, $child->parent_id);
        $data = $parent->getAttributes();
        $data['parent_id'] = $child->id;
        $this->putJson(route('itemcategories.update', $parent), $data)->assertUnprocessable()->assertJsonValidationErrors('parent_id');
        $this->assertNull($parent->fresh()->parent_id);
        $this->assertSame($user->id, auth()->id());
    }

    public function test_list_and_entry_routes_expose_type_and_lock_without_missing_components(): void
    {
        $this->operator();
        $category = ItemCategory::query()->create($this->payload('equipment'));
        $lab = VAPLab::factory()->create();
        InventoryItem::query()->create(['lab_id' => $lab->id, 'category_id' => $category->id, 'name' => 'Equipment']);
        $this->get(route('itemcategories.index', ['search' => $category->name, 'edit' => $category->id]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('ItemCategories/Index')
            ->where('record.data.0.inventory_type', 'equipment')->where('record.data.0.type_locked', true)
            ->where('initialRecord.data.id', $category->id));
        $this->get(route('itemcategories.create'))->assertRedirect(route('itemcategories.index', ['create' => 1]));
        $this->get(route('itemcategories.edit', $category))->assertRedirect(route('itemcategories.index', ['edit' => $category->id]));
    }

    public function test_direct_action_rechecks_permission_and_used_type(): void
    {
        $user = $this->operator();
        $category = ItemCategory::query()->create($this->payload('material'));
        $user->revokePermissionTo('edit_item_categories');
        try {
            app(SaveInventoryCategory::class)->execute($user->id, $this->payload('equipment'), $category->id);
            $this->fail('Expected current permission denial.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame(InventoryCategoryType::MATERIAL, $category->fresh()->inventory_type);
    }

    public function test_authoring_only_permissions_do_not_require_or_grant_full_catalogue_read_access(): void
    {
        $user = $this->operator();
        $category = ItemCategory::query()->create($this->payload('material'));
        $user->revokePermissionTo('view_item_categories');
        $this->get(route('itemcategories.index'))->assertForbidden();
        $this->get(route('itemcategories.index', ['create' => 1]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('openCreate', true)->has('record.data', 0)->where('initialRecord', null));
        $this->get(route('itemcategories.index', ['edit' => $category->id]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('record.data', 0)->where('initialRecord.data.id', $category->id));
    }

    public function test_late_impersonation_cannot_publish_a_successful_save(): void
    {
        $this->operator();
        $data = $this->payload('equipment');
        ItemCategory::saved(fn () => request()->session()->put('impersonate', 123));
        try {
            $this->postJson(route('itemcategories.store'), $data)->assertForbidden();
            $this->assertDatabaseMissing('item_categories', ['code' => $data['code']]);
        } finally {
            ItemCategory::flushEventListeners();
            ItemCategory::clearBootedModels();
        }
    }

    public function test_scalar_parent_transport_and_noop_preserve_metadata_and_timestamps(): void
    {
        $this->operator();
        $parent = ItemCategory::query()->create($this->payload('equipment'));
        $data = $this->payload('material') + ['parent_id' => (string) $parent->id];
        $this->postJson(route('itemcategories.store'), $data)->assertRedirect();
        $category = ItemCategory::query()->where('code', $data['code'])->sole();
        $before = $category->getAttributes();
        $this->travel(2)->days();
        $this->putJson(route('itemcategories.update', $category), $data)->assertRedirect();
        $this->assertSame($before, $category->fresh()->getAttributes());
    }

    #[DataProvider('nullableCreateFaults')]
    public function test_created_hooks_cannot_change_unsupplied_nullable_columns(string $field): void
    {
        $this->operator();
        $parent = ItemCategory::query()->create($this->payload('equipment'));
        $data = $this->payload('material');
        unset($data['description']);
        ItemCategory::created(function (ItemCategory $category) use ($field, $parent): void {
            DB::table('item_categories')->where('id', $category->id)->update([$field => match ($field) {
                'parent_id' => $parent->id, 'deleted_at' => now(), default => 'Injected description',
            }]);
        });
        try {
            $this->postJson(route('itemcategories.store'), $data)->assertStatus(409);
            $this->assertDatabaseMissing('item_categories', ['code' => $data['code']]);
        } finally {
            ItemCategory::flushEventListeners();
            ItemCategory::clearBootedModels();
        }
    }

    /** @return array<string,array{string}> */
    public static function nullableCreateFaults(): array
    {
        return ['parent' => ['parent_id'], 'archive' => ['deleted_at'], 'description' => ['description']];
    }

    #[DataProvider('hydrationFaults')]
    public function test_fresh_authority_hydration_cannot_mutate_retained_categories_or_actor(int $target, string $fault): void
    {
        $user = $this->operator();
        $category = ItemCategory::query()->create($this->payload('material'));
        $peer = ItemCategory::query()->create($this->payload('equipment'));
        $before = DB::table('item_categories')->orderBy('id')->get()->toArray();
        $usageBefore = DB::table('inventory_category_usage')->orderBy('category_id')->get()->toArray();
        $seen = 0;
        User::retrieved(function (User $retrieved) use ($user, $category, $peer, $target, $fault, &$seen): void {
            if ($retrieved->id !== $user->id || ++$seen !== $target) {
                return;
            }
            if ($fault === 'actor') {
                DB::table('users')->where('id', $user->id)->update(['name' => 'Mutated actor']);
            } elseif ($fault === 'usage') {
                DB::table('inventory_category_usage')->insert(['category_id' => $peer->id]);
            } else {
                DB::table('item_categories')->where('id', $fault === 'target' ? $category->id : $peer->id)
                    ->update(['description' => 'Unexpected retrieval mutation']);
            }
        });
        try {
            try {
                app(SaveInventoryCategory::class)->execute($user->id, $this->payload('material'), $category->id);
                $this->fail('Authority hydration must not publish unrelated mutations.');
            } catch (HttpException $exception) {
                $this->assertSame(409, $exception->getStatusCode());
            }
            $this->assertEquals($before, DB::table('item_categories')->orderBy('id')->get()->toArray());
            $this->assertSame($user->name, DB::table('users')->where('id', $user->id)->value('name'));
            $this->assertEquals($usageBefore, DB::table('inventory_category_usage')->orderBy('category_id')->get()->toArray());
        } finally {
            User::flushEventListeners();
            User::clearBootedModels();
        }
    }

    /** @return array<string,array{int,string}> */
    public static function hydrationFaults(): array
    {
        $cases = [];
        foreach ([1, 2] as $target) {
            foreach (['actor', 'target', 'peer', 'usage'] as $fault) {
                $cases[$target.'-'.$fault] = [$target, $fault];
            }
        }

        return $cases;
    }

    #[DataProvider('authorityBoundaries')]
    public function test_noop_cannot_add_false_category_usage_during_authority_hydration(int $target): void
    {
        $user = $this->operator();
        $category = ItemCategory::query()->create($this->payload('material'));
        $before = (array) DB::table('item_categories')->where('id', $category->id)->first();
        $usageBefore = DB::table('inventory_category_usage')->orderBy('category_id')->get()->toArray();
        $seen = 0;
        User::retrieved(function (User $retrieved) use ($user, $category, $target, &$seen): void {
            if ($retrieved->id === $user->id && ++$seen === $target) {
                DB::table('inventory_category_usage')->insert(['category_id' => $category->id]);
            }
        });
        try {
            try {
                app(SaveInventoryCategory::class)->execute($user->id, $before, $category->id);
                $this->fail('A no-op must not publish false category usage.');
            } catch (HttpException $exception) {
                $this->assertSame(409, $exception->getStatusCode());
            }
            $this->assertEquals($before, (array) DB::table('item_categories')->where('id', $category->id)->first());
            $this->assertEquals($usageBefore, DB::table('inventory_category_usage')->orderBy('category_id')->get()->toArray());
        } finally {
            User::flushEventListeners();
            User::clearBootedModels();
        }
    }

    /** @return array<string,array{int}> */
    public static function authorityBoundaries(): array
    {
        return ['entry' => [1], 'final' => [2]];
    }

    private function operator(): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        foreach (['view_item_categories', 'add_item_categories', 'edit_item_categories'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        return $user;
    }

    /** @return array<string,mixed> */
    private function payload(string $type): array
    {
        return ['name' => 'Category '.fake()->uuid(), 'code' => fake()->uuid(), 'inventory_type' => $type, 'description' => null];
    }

    private function assertConstraintFails(callable $write): void
    {
        try {
            DB::transaction($write);
            $this->fail('Expected the category constraint to reject this write.');
        } catch (QueryException $exception) {
            $this->assertSame('23514', $exception->getCode());
        }
    }
}
