<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

abstract class IsolatedPostgresTestCase extends TestCase
{
    protected ?string $schema = null;

    /** @var array<string, mixed> */
    private array $originalConnection = [];

    protected function setUp(): void
    {
        parent::setUp();
        $connection = DB::connection();
        $this->assertSame('pgsql', $connection->getDriverName());
        $this->assertSame('lims_unleashed_test', $connection->getDatabaseName());
        $this->assertSame(0, $connection->transactionLevel());
        $this->originalConnection = config('database.connections.pgsql');
        $this->schema = 'document_test_'.bin2hex(random_bytes(8));
        DB::statement('CREATE SCHEMA "'.$this->schema.'"');
        try {
            config(['database.connections.pgsql.search_path' => $this->schema, 'queue.default' => 'database',
                'cache.default' => 'array', 'mail.default' => 'array']);
            DB::purge('pgsql');
            $this->assertSame(0, Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]), Artisan::output());
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (Throwable $exception) {
            $this->removeSchema();
            throw $exception;
        }
    }

    protected function tearDown(): void
    {
        try {
            $this->removeSchema();
        } finally {
            parent::tearDown();
        }
    }

    private function removeSchema(): void
    {
        if ($this->schema === null) {
            return;
        }
        while (DB::connection()->transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::statement('DROP SCHEMA "'.$this->schema.'" CASCADE');
        config(['database.connections.pgsql' => $this->originalConnection]);
        DB::purge('pgsql');
        $this->schema = null;
    }
}
