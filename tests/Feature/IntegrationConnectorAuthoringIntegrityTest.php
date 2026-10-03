<?php

namespace Tests\Feature;

use App\Actions\SaveIntegrationConnector;
use App\Models\IntegrationConnector;
use App\Models\IntegrationMapping;
use App\Models\InventoryItem;
use App\Models\ISOActivityLog;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IntegrationConnectorAuthoringIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('writeFaults')]
    public function test_failed_root_reference_authority_or_evidence_write_rolls_back(string $operation, string $fault, int $status): void
    {
        [$lab, $user, $item] = $this->fixture();
        $connector = $operation === 'update' ? IntegrationConnector::factory()->create([
            'lab_id' => $lab->id, 'inventory_item_id' => $item->id,
        ]) : null;
        $before = $connector?->refresh()->getRawOriginal();
        $itemBefore = $item->refresh()->getRawOriginal();
        $actorBefore = $user->refresh()->getRawOriginal();
        $counts = $this->counts();
        $fired = false;
        $event = match ($fault) {
            'mapping_veto', 'mapping_altered' => 'eloquent.saving: '.IntegrationMapping::class,
            'audit_veto', 'audit_altered' => 'eloquent.saving: '.ISOActivityLog::class,
            default => 'eloquent.saving: '.IntegrationConnector::class,
        };
        Event::listen($event, function (object $model) use ($fault, $lab, $user, $item, &$fired): ?bool {
            $fired = true;
            switch ($fault) {
                case 'root_veto':
                case 'mapping_veto':
                case 'audit_veto':
                    return false;
                case 'root_altered':
                    $model->name = 'Unexpected persisted name';
                    break;
                case 'health_status_altered':
                    $model->health_status = 'healthy';
                    break;
                case 'root_archived':
                    $model->deleted_at = now();
                    break;
                case 'token_hash_altered':
                    $model->ingest_token_hash = str_repeat('a', 64);
                    break;
                case 'mapping_altered':
                    $model->field_paths = ['value' => 'wrong.value'];
                    break;
                case 'audit_altered':
                    $model->properties = ['lab_id' => -1];
                    break;
                case 'equipment_archived':
                    DB::table('i_items')->where('id', $item->id)->update(['deleted_at' => now()]);
                    break;
                case 'equipment_reassigned':
                    DB::table('i_items')->where('id', $item->id)->update(['lab_id' => VAPLab::factory()->create()->id]);
                    break;
                case 'permission_revoked':
                    DB::table('model_has_permissions')->where('model_id', $user->id)->where('model_type', $user->getMorphClass())->delete();
                    break;
                case 'membership_removed':
                    DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete();
                    break;
                case 'actor_inactive':
                    DB::table('users')->where('id', $user->id)->update(['is_active' => false]);
                    break;
                case 'actor_unverified':
                    DB::table('users')->where('id', $user->id)->update(['email_verified_at' => null]);
                    break;
                case 'lab_archived':
                    DB::table('labs')->where('id', $lab->id)->update(['deleted_at' => now()]);
                    break;
            }

            return null;
        });
        try {
            $payload = $this->payload() + ['inventory_item_id' => $item->id];
            $response = $connector
                ? $this->putJson(route('integration-hub.connectors.update', $connector), $payload)
                : $this->postJson(route('integration-hub.connectors.store'), $payload);
            $response->assertStatus($status);
            $this->assertTrue($fired);
            $this->assertSame($counts, $this->counts());
            if ($connector) {
                $this->assertSame($before, $connector->fresh()->getRawOriginal());
            }
            $this->assertSame($itemBefore, $item->fresh()->getRawOriginal());
            $this->assertSame($actorBefore, $user->fresh()->getRawOriginal());
            $this->assertNull(DB::table('labs')->where('id', $lab->id)->value('deleted_at'));
            $this->assertTrue(DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->exists());
            $this->assertTrue(DB::table('model_has_permissions')->where('model_id', $user->id)->where('model_type', $user->getMorphClass())->exists());
            $response->assertSessionMissing('integration_token');
        } finally {
            Event::forget($event);
        }
    }

    /** @return array<string,array{string,string,int}> */
    public static function writeFaults(): array
    {
        $cases = [];
        foreach (['create', 'update'] as $operation) {
            foreach (['root_veto', 'root_altered', 'health_status_altered', 'root_archived', 'token_hash_altered',
                'equipment_archived', 'equipment_reassigned', 'audit_veto', 'audit_altered',
                'permission_revoked', 'membership_removed', 'actor_inactive', 'actor_unverified', 'lab_archived'] as $fault) {
                $status = in_array($fault, ['permission_revoked', 'membership_removed', 'actor_inactive', 'actor_unverified', 'lab_archived'], true) ? 403 : 409;
                $cases[$operation.' '.$fault] = [$operation, $fault, $status];
            }
        }
        $cases['create mapping_veto'] = ['create', 'mapping_veto', 409];
        $cases['create mapping_altered'] = ['create', 'mapping_altered', 409];

        return $cases;
    }

    public function test_direct_authoring_preserves_trusted_identity_credentials_and_archived_links(): void
    {
        [$lab, $user, $item] = $this->fixture();
        $save = app(SaveIntegrationConnector::class);
        $saved = $save->execute($lab->id, $user->id, $this->payload() + [
            'inventory_item_id' => (string) $item->id, 'lab_id' => 2147483647, 'created_by_id' => 2147483647,
            'ingest_token_hash' => 'forged', 'signing_secret' => 'forged', 'health_status' => 'healthy',
            'credentials' => ['bearer_token' => 'first-private-token'],
        ]);
        $connector = $saved['connector']->refresh();
        $this->assertSame($lab->id, $connector->lab_id);
        $this->assertSame($user->id, $connector->created_by_id);
        $this->assertSame('unknown', $connector->health_status);
        $this->assertTrue($connector->matchesIngestToken($saved['token']));
        $this->assertNotSame('forged', $connector->signing_secret);
        $this->assertStringNotContainsString('first-private-token', $connector->getRawOriginal('credentials'));
        $item->delete();
        $updated = $save->execute($lab->id, $user->id, $this->payload() + [
            'inventory_item_id' => (string) $item->id, 'credentials' => ['bearer_token' => ''],
        ], $connector->id);
        $this->assertSame($item->id, $updated['connector']->inventory_item_id);
        $this->assertSame('first-private-token', $updated['connector']->credentials['bearer_token']);
        $this->assertNull($updated['token']);
        $this->assertSame($connector->ingest_token_hash, $updated['connector']->ingest_token_hash);
        $cleared = $save->execute($lab->id, $user->id, $this->payload() + ['inventory_item_id' => null], $connector->id);
        $this->assertNull($cleared['connector']->inventory_item_id);
        try {
            $save->execute($lab->id, $user->id, $this->payload() + ['inventory_item_id' => $item->id], $connector->id);
            $this->fail('An archived item cannot be newly relinked after explicit clearing.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('inventory_item_id', $exception->errors());
        }
        $this->assertNull($connector->fresh()->inventory_item_id);
    }

    public function test_direct_authoring_respects_equipment_only_permission_and_bounds_generated_keys(): void
    {
        [$lab, $user] = $this->fixture();
        $user->syncPermissions([Permission::findOrCreate('edit_iequipments', 'web')]);
        $save = app(SaveIntegrationConnector::class);
        $payload = ['name' => str_repeat('a', 120), 'direction' => 'outbound', 'adapter' => 'rest_json',
            'status' => 'draft', 'configuration' => ['endpoint' => 'https://receiver.example.test', 'timeout_seconds' => 10]];
        $first = $save->execute($lab->id, $user->id, $payload);
        $this->assertSame(str_repeat('a', 80), $first['connector']->key);
        $this->assertNull($first['token']);
        $this->assertSame(0, $first['connector']->mappings()->count());
        $first['connector']->delete();
        $second = $save->execute($lab->id, $user->id, $payload);
        $this->assertSame(str_repeat('a', 78).'-2', $second['connector']->key);
        $explicit = $save->execute($lab->id, $user->id, array_replace($payload, ['key' => str_repeat('b', 80)]));
        $this->assertSame(str_repeat('b', 80), $explicit['connector']->key);
    }

    public function test_identical_metadata_does_not_save_or_touch_root_but_retains_existing_audit_semantics(): void
    {
        [$lab, $user, $item] = $this->fixture();
        $payload = $this->payload() + ['inventory_item_id' => $item->id];
        $save = app(SaveIntegrationConnector::class);
        $connector = $save->execute($lab->id, $user->id, $payload)['connector']->refresh();
        $before = $connector->getRawOriginal();
        $saved = 0;
        Event::listen('eloquent.saving: '.IntegrationConnector::class, function () use (&$saved): void {
            $saved++;
        });
        try {
            $this->travel(5)->seconds();
            $save->execute($lab->id, $user->id, $payload, $connector->id);
            $this->assertSame(0, $saved);
            $this->assertSame($before, $connector->fresh()->getRawOriginal());
            $this->assertSame(2, ISOActivityLog::query()->where('subject_type', $connector->getMorphClass())->where('subject_id', $connector->id)->count());
        } finally {
            $this->travelBack();
            Event::forget('eloquent.saving: '.IntegrationConnector::class);
        }
    }

    #[DataProvider('invalidDirectChoices')]
    public function test_direct_authoring_validates_invalid_equipment_without_partial_records(mixed $value): void
    {
        [$lab, $user, $item] = $this->fixture();
        $connector = IntegrationConnector::factory()->create(['lab_id' => $lab->id, 'inventory_item_id' => $item->id]);
        $before = $connector->refresh()->getRawOriginal();
        $counts = $this->counts();
        foreach ([null, $connector->id] as $id) {
            try {
                app(SaveIntegrationConnector::class)->execute($lab->id, $user->id, $this->payload() + ['inventory_item_id' => $value], $id);
                $this->fail('Invalid equipment must be rejected by the action itself.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('inventory_item_id', $exception->errors());
            }
        }
        $this->assertSame($counts, $this->counts());
        $this->assertSame($before, $connector->fresh()->getRawOriginal());
    }

    /** @return array<string,array{mixed}> */
    public static function invalidDirectChoices(): array
    {
        return ['missing' => [2147483647], 'array' => [[1]], 'decimal' => ['1.5'], 'object' => [(object) ['id' => 1]]];
    }

    /** @return array{VAPLab,User,InventoryItem} */
    private function fixture(): array
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        $user->givePermissionTo(Permission::findOrCreate('edit_settings', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $category = ItemCategory::query()->create(['name' => 'Equipment '.fake()->uuid(), 'inventory_type' => 'equipment']);
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'category_id' => $category->id, 'name' => 'Instrument']);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        return [$lab, $user, $item];
    }

    /** @return array<string,mixed> */
    private function payload(): array
    {
        return ['name' => 'Connector '.fake()->uuid(), 'direction' => 'inbound', 'adapter' => 'rest_json', 'status' => 'draft'];
    }

    /** @return array<string,int> */
    private function counts(): array
    {
        return [
            'connectors' => IntegrationConnector::withTrashed()->count(),
            'mappings' => IntegrationMapping::query()->count(),
            'activity' => ISOActivityLog::query()->where('subject_type', (new IntegrationConnector)->getMorphClass())->count(),
        ];
    }
}
