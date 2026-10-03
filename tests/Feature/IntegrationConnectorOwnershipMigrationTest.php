<?php

namespace Tests\Feature;

use App\Models\IntegrationConnector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class IntegrationConnectorOwnershipMigrationTest extends TestCase
{
    use DatabaseTransactions;

    private function migration(): object
    {
        return require database_path('migrations/2026_09_28_223538_add_lab_id_to_integration_connectors_table.php');
    }

    public function test_empty_schema_rollback_and_replay_restore_required_ownership(): void
    {
        $this->assertSame(0, DB::table('integration_connectors')->count());
        $migration = $this->migration();

        $migration->down();
        $this->assertFalse(Schema::hasColumn('integration_connectors', 'lab_id'));
        $this->assertFalse(Schema::hasIndex('integration_connectors', 'integration_connectors_lab_id_status_index'));

        $migration->up();
        $migration->up();
        $this->assertTrue(Schema::hasColumn('integration_connectors', 'lab_id'));
        $this->assertTrue(Schema::hasIndex('integration_connectors', 'integration_connectors_lab_id_status_index'));
        $this->assertTrue(collect(Schema::getForeignKeys('integration_connectors'))
            ->contains(fn (array $key): bool => $key['name'] === 'integration_connectors_lab_id_foreign'));
    }

    public function test_rollback_refuses_to_erase_retained_connector_ownership(): void
    {
        $connector = IntegrationConnector::factory()->create();
        $before = $connector->fresh()->getAttributes();

        try {
            $this->migration()->down();
            $this->fail('The migration removed ownership from a retained connector.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('retained connectors', $exception->getMessage());
        }

        $this->assertSame($before, $connector->fresh()->getAttributes());
        $this->assertTrue(Schema::hasColumn('integration_connectors', 'lab_id'));
    }

    public function test_forward_migration_refuses_to_guess_ownership_of_existing_connectors(): void
    {
        $this->migration()->down();
        $connectorId = DB::table('integration_connectors')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Unreviewed connector',
            'key' => 'unreviewed-'.Str::lower(Str::random(8)),
            'adapter' => 'rest_json',
        ]);
        $before = DB::table('integration_connectors')->find($connectorId);

        try {
            $this->migration()->up();
            $this->fail('The migration guessed ownership for a retained connector.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('ownership must be reviewed', $exception->getMessage());
        }

        $this->assertEquals($before, DB::table('integration_connectors')->find($connectorId));
        $this->assertFalse(Schema::hasColumn('integration_connectors', 'lab_id'));
    }
}
