<?php

namespace Tests\Unit;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Tests\Feature\InventoryReagentConsumptionIntegrityTest;
use Tests\Feature\LaboratoryMembershipTest;
use Tests\Feature\StaffAccountLifecycleTest;
use WeakReference;

class ApplicationLifecycleTest extends TestCase
{
    /**
     * Inspect middle lifecycles separately from the first dump-handler root and
     * the latest framework singleton roots. Retaining fixtures deliberately
     * exercises a stronger condition than PHPUnit's normal object release.
     */
    #[DataProvider('requestPaths')]
    public function test_destroyed_application_graphs_are_collectable_when_test_objects_are_retained(?string $requestPath, bool $queryDatabase): void
    {
        $fixtures = [];
        $references = [];
        for ($iteration = 0; $iteration < 8; $iteration++) {
            $fixture = new ApplicationLifecycleFixture('probe');
            $references[] = $fixture->bootAndDestroy($requestPath, $queryDatabase);
            $fixtures[] = $fixture;
            $this->addToAssertionCount($fixture->numberOfAssertionsPerformed());
        }

        gc_collect_cycles();
        foreach (array_slice($references, 1, -1) as $iteration => $graph) {
            foreach ($graph as $name => $reference) {
                $this->assertFalse($reference->get() !== null, 'Destroyed application graph remains rooted: '.$iteration.' '.$name);
            }
        }
        $this->assertCount(8, $fixtures);
    }

    /** @return array<string, array{?string, bool}> */
    public static function requestPaths(): array
    {
        return ['without HTTP' => [null, false], 'public HTTP' => ['/', false], 'missing HTTP' => ['/__lifecycle_missing_route', false],
            'rendered server error' => ['/__lifecycle_server_error', false], 'PostgreSQL transaction' => [null, true]];
    }

    /**
     * This exercises Laravel setup, the original fixture assertions and teardown,
     * not PHPUnit's complete runBare lifecycle or process-global restoration.
     *
     * @param  class-string<\Tests\TestCase>  $class
     * @param  list<mixed>  $arguments
     */
    #[DataProvider('faultFixtures')]
    public function test_destroyed_fault_injection_fixture_graphs_are_collectable(string $class, string $method, array $arguments): void
    {
        $this->assertSame('testing', getenv('APP_ENV'));
        $this->assertSame('lims_unleashed_test', getenv('DB_DATABASE'));
        $fixtures = [];
        $references = [];
        for ($iteration = 0; $iteration < 8; $iteration++) {
            $fixture = new $class($method);
            try {
                (new ReflectionMethod($fixture, 'setUp'))->invoke($fixture);
                $fixture->{$method}(...$arguments);
                $application = (new ReflectionProperty($fixture, 'app'))->getValue($fixture);
                $connection = $application['db']->connection();
                $references[] = [
                    'application' => WeakReference::create($application),
                    'events' => WeakReference::create($application['events']),
                    'database_connection' => WeakReference::create($connection),
                    'database_pdo' => WeakReference::create($connection->getPdo()),
                    'exception_handler' => WeakReference::create($application->make(ExceptionHandler::class)),
                ];
            } finally {
                (new ReflectionMethod($fixture, 'tearDown'))->invoke($fixture);
            }
            $fixtures[] = $fixture;
            $this->addToAssertionCount($fixture->numberOfAssertionsPerformed());
            unset($application, $connection);
        }

        gc_collect_cycles();
        foreach (array_slice($references, 1, -1) as $iteration => $graph) {
            foreach ($graph as $name => $reference) {
                $this->assertFalse($reference->get() !== null, 'Destroyed fault-fixture graph remains rooted: '.$iteration.' '.$name);
            }
        }
        $this->assertCount(8, $fixtures);
    }

    /** @return array<string, array{class-string<\Tests\TestCase>, string, list<mixed>}> */
    public static function faultFixtures(): array
    {
        return [
            'staff lifecycle authority revocation' => [StaffAccountLifecycleTest::class,
                'test_audit_faults_rollback_lifecycle_history_authority_and_assignments', ['deactivate', 'actor_state']],
            'membership operator evidence mutation' => [LaboratoryMembershipTest::class,
                'test_membership_preserves_operator_identity_and_exact_local_membership', ['account', true, 'audit']],
            'consumption late operator revocation' => [InventoryReagentConsumptionIntegrityTest::class,
                'test_failed_consumption_preserves_the_complete_stock_graph', ['late_actor']],
        ];
    }
}

class ApplicationLifecycleFixture extends \Tests\TestCase
{
    use DatabaseTransactions;

    /** @return array<string, WeakReference> */
    public function bootAndDestroy(?string $requestPath, bool $queryDatabase): array
    {
        try {
            $this->setUp();
            $databaseReferences = [];
            if ($queryDatabase) {
                $connection = $this->app['db']->connection();
                $this->assertSame('pgsql', $connection->getDriverName());
                $this->assertSame('lims_unleashed_test', $connection->getDatabaseName());
                $this->assertSame(1, (int) $connection->selectOne('select 1 as value')->value);
                $databaseReferences['database_connection'] = WeakReference::create($connection);
                $databaseReferences['database_pdo'] = WeakReference::create($connection->getPdo());
            }

            if ($requestPath === '/__lifecycle_server_error') {
                $this->app['router']->get($requestPath, static function (): never {
                    throw new ApplicationLifecycleFailure('Lifecycle renderer probe');
                });
            }

            if ($requestPath !== null) {
                $response = $this->get($requestPath);
                $response->assertStatus(match ($requestPath) {
                    '/' => 200, '/__lifecycle_server_error' => 500, default => 404,
                });
            }

            return [
                'application' => WeakReference::create($this->app),
                'router' => WeakReference::create($this->app['router']),
                'events' => WeakReference::create($this->app['events']),
                'exception_handler' => WeakReference::create($this->app->make(ExceptionHandler::class)),
                ...$databaseReferences,
            ];
        } finally {
            $this->tearDown();
        }
    }

    public function probe(): void {}
}

class ApplicationLifecycleFailure extends RuntimeException implements ShouldntReport {}
