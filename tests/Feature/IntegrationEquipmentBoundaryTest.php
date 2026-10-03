<?php

namespace Tests\Feature;

use App\Models\IntegrationConnector;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IntegrationEquipmentBoundaryTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('managers')]
    public function test_selector_uses_explicit_equipment_type_not_category_one(string $manager): void
    {
        [, , $items] = $this->fixture($manager);
        $this->assertNotSame(1, $items['equipment']->category_id);
        $props = $this->get(route('integration-hub.index'))->assertOk()->viewData('page')['props'];
        $this->assertSame([$items['equipment']->id], array_column($props['equipmentOptions'], 'value'));
        $this->assertSame(['value', 'label', 'meta'], array_keys($props['equipmentOptions'][0]));
        $this->assertTrue($props['canManage']);
        $items['equipment']->category->delete();
        $props = $this->get(route('integration-hub.index'))->assertOk()->viewData('page')['props'];
        $this->assertSame([$items['equipment']->id], array_column($props['equipmentOptions'], 'value'));
    }

    #[DataProvider('managers')]
    public function test_managers_can_assign_active_owned_equipment_and_preserve_omitted_links(string $manager): void
    {
        [$lab, , $items] = $this->fixture($manager);
        $this->post(route('integration-hub.connectors.store'), $this->payload() + [
            'lab_id' => $items['peer']->lab_id, 'inventory_item_id' => (string) $items['equipment']->id,
        ])->assertRedirect(route('integration-hub.index'));
        $connector = IntegrationConnector::query()->where('lab_id', $lab->id)->sole();
        $this->assertSame($lab->id, $connector->lab_id);
        $this->assertSame($items['equipment']->id, $connector->inventory_item_id);
        $items['equipment']->category->delete();
        $this->put(route('integration-hub.connectors.update', $connector), $this->payload() + ['inventory_item_id' => $items['equipment']->id])->assertRedirect();
        $this->put(route('integration-hub.connectors.update', $connector), $this->payload())->assertRedirect();
        $this->assertSame($items['equipment']->id, $connector->fresh()->inventory_item_id);
        $this->put(route('integration-hub.connectors.update', $connector), $this->payload() + ['inventory_item_id' => null])->assertRedirect();
        $this->assertNull($connector->fresh()->inventory_item_id);
    }

    #[DataProvider('invalidChoices')]
    public function test_creation_and_update_reject_ineligible_equipment_without_writes(string $choice): void
    {
        [$lab, , $items] = $this->fixture('settings');
        $connector = IntegrationConnector::factory()->create(['lab_id' => $lab->id, 'inventory_item_id' => $items['equipment']->id]);
        $before = $connector->refresh()->getRawOriginal();
        $value = match ($choice) {
            'array' => [$items['equipment']->id],
            'missing' => 2147483647,
            default => $items[$choice]->id,
        };
        $count = IntegrationConnector::query()->count();
        $this->postJson(route('integration-hub.connectors.store'), $this->payload() + ['inventory_item_id' => $value])
            ->assertUnprocessable()->assertJsonValidationErrors('inventory_item_id');
        $this->putJson(route('integration-hub.connectors.update', $connector), $this->payload() + ['inventory_item_id' => $value])
            ->assertUnprocessable()->assertJsonValidationErrors('inventory_item_id');
        $this->assertSame($count, IntegrationConnector::query()->count());
        $this->assertSame($before, $connector->fresh()->getRawOriginal());
    }

    public function test_current_archived_equipment_is_retained_but_cannot_be_newly_assigned(): void
    {
        [$lab, , $items] = $this->fixture('equipment');
        $connector = IntegrationConnector::factory()->create(['lab_id' => $lab->id, 'inventory_item_id' => $items['equipment']->id]);
        $items['equipment']->delete();
        $props = $this->get(route('integration-hub.index'))->assertOk()->viewData('page')['props'];
        $this->assertSame([], $props['equipmentOptions']);
        $this->assertSame($items['equipment']->id, $props['connectors'][0]['equipment']['id']);
        $this->assertTrue($props['connectors'][0]['equipment']['is_archived']);
        $this->assertSame(['id', 'name', 'code', 'serial_number', 'is_archived'], array_keys($props['connectors'][0]['equipment']));
        $this->put(route('integration-hub.connectors.update', $connector), $this->payload() + ['inventory_item_id' => (string) $items['equipment']->id])->assertRedirect();
        $this->assertSame($items['equipment']->id, $connector->fresh()->inventory_item_id);
        $this->postJson(route('integration-hub.connectors.store'), $this->payload() + ['inventory_item_id' => $items['equipment']->id])
            ->assertUnprocessable()->assertJsonValidationErrors('inventory_item_id');
        $other = IntegrationConnector::factory()->create(['lab_id' => $lab->id]);
        $this->putJson(route('integration-hub.connectors.update', $other), $this->payload() + ['inventory_item_id' => $items['equipment']->id])
            ->assertUnprocessable()->assertJsonValidationErrors('inventory_item_id');
        $this->assertNull($other->fresh()->inventory_item_id);
    }

    public function test_hub_does_not_project_foreign_or_material_linked_equipment(): void
    {
        [$lab, , $items] = $this->fixture('equipment');
        $connectors = [];
        foreach (['peer', 'material', 'unclassified'] as $key) {
            $connectors[] = IntegrationConnector::factory()->create(['lab_id' => $lab->id, 'inventory_item_id' => $items[$key]->id]);
        }
        $props = $this->get(route('integration-hub.index'))->assertOk()->viewData('page')['props'];
        $this->assertCount(3, $props['connectors']);
        foreach ($props['connectors'] as $row) {
            $this->assertNull($row['equipment']);
            $this->assertTrue($row['equipment_link_unavailable']);
            $this->assertArrayNotHasKey('inventory_item_id', $row);
        }
        foreach ($connectors as $connector) {
            $itemId = $connector->inventory_item_id;
            $this->put(route('integration-hub.connectors.update', $connector), $this->payload())->assertRedirect();
            $this->assertSame($itemId, $connector->fresh()->inventory_item_id);
        }
        $this->assertSame([$items['equipment']->id], array_column($props['equipmentOptions'], 'value'));
    }

    #[DataProvider('deniedManagers')]
    public function test_manage_authorization_precedes_payload_validation(string $grant): void
    {
        [$lab] = $this->fixture($grant);
        $connector = IntegrationConnector::factory()->create(['lab_id' => $lab->id]);
        $connector->refresh();
        $this->postJson(route('integration-hub.connectors.store'), [])->assertForbidden();
        $this->putJson(route('integration-hub.connectors.update', $connector), [])->assertForbidden();
        $this->assertSame($connector->getRawOriginal(), $connector->fresh()->getRawOriginal());
    }

    public function test_update_target_and_choices_follow_the_active_direct_membership(): void
    {
        [$lab, $user, $items] = $this->fixture('settings');
        $connector = IntegrationConnector::factory()->create(['lab_id' => $lab->id, 'inventory_item_id' => $items['equipment']->id]);
        DB::table('lab_user')->insert(['lab_id' => $items['peer']->lab_id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $items['peer']->lab_id]);
        $this->putJson(route('integration-hub.connectors.update', $connector), [])->assertNotFound();
        $props = $this->get(route('integration-hub.index', ['lab_id' => $lab->id]))->assertOk()->viewData('page')['props'];
        $this->assertSame([$items['peer']->id], array_column($props['equipmentOptions'], 'value'));
        $this->assertSame($items['equipment']->id, $connector->fresh()->inventory_item_id);
    }

    /** @return array<string,array{string}> */
    public static function managers(): array
    {
        return ['equipment' => ['equipment'], 'settings' => ['settings']];
    }

    /** @return array<string,array{string}> */
    public static function deniedManagers(): array
    {
        return ['none' => ['none'], 'material' => ['material'], 'view_settings_only' => ['view_settings_only']];
    }

    /** @return array<string,array{string}> */
    public static function invalidChoices(): array
    {
        return array_combine(['material', 'unclassified', 'peer', 'archived', 'missing', 'array'], array_map(fn (string $value): array => [$value], ['material', 'unclassified', 'peer', 'archived', 'missing', 'array']));
    }

    /** @return array<string,mixed> */
    private function payload(): array
    {
        return ['name' => 'Connector '.fake()->uuid(), 'direction' => 'inbound', 'adapter' => 'rest_json', 'status' => 'draft'];
    }

    /** @return array{VAPLab,User,array<string,InventoryItem>} */
    private function fixture(string $grant): array
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach (match ($grant) {
            'equipment' => ['view_iequipments', 'edit_iequipments'],
            'settings' => ['view_settings', 'edit_settings'],
            'material' => ['view_iitems', 'edit_iitems'],
            'view_settings_only' => ['view_settings'],
            default => [],
        } as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $lab = VAPLab::factory()->create();
        $peerLab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $material = ItemCategory::query()->create(['name' => 'Material '.fake()->uuid(), 'inventory_type' => 'material']);
        $equipment = ItemCategory::query()->create(['name' => 'Renamed instruments '.fake()->uuid(), 'inventory_type' => 'equipment']);
        $items = [];
        foreach (['material', 'equipment', 'unclassified', 'peer', 'archived'] as $key) {
            $items[$key] = InventoryItem::query()->create([
                'lab_id' => $key === 'peer' ? $peerLab->id : $lab->id,
                'category_id' => $key === 'unclassified' ? null : ($key === 'material' ? $material->id : $equipment->id),
                'name' => 'Instrument '.$key, 'code' => 'CODE-'.$key, 'serial_number' => 'SERIAL-'.$key,
                'obs' => 'Private item notes', 'standard_cost' => 10,
            ]);
        }
        $items['archived']->delete();
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        return [$lab, $user, $items];
    }
}
