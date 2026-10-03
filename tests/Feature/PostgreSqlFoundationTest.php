<?php

namespace Tests\Feature;

use App\Models\LabCode;
use App\Models\Parameter;
use App\Models\Product;
use App\Models\Sample;
use App\Models\VAPLab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PostgreSqlFoundationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_fresh_postgresql_installation_serves_the_public_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_all_sequence_models_have_their_required_schema(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('lims_unleashed_test', DB::connection()->getDatabaseName());
        foreach (['ContractGuide', 'CreditNote', 'InventoryItem', 'InventoryOrder', 'Invoice', 'LabCode', 'MaintenanceTask', 'Occurrence', 'Proposal', 'Quote', 'Receipt', 'Sample', 'VAPProposal'] as $name) {
            $class = 'App\\Models\\'.$name;
            $model = new $class;
            $config = $model->sequence();
            foreach ([$config['fieldName'], ...(array) $config['group']] as $column) {
                $this->assertTrue(Schema::hasColumn($model->getTable(), $column), $name.'.'.$column);
            }
        }
    }

    public function test_backups_and_failed_jobs_use_the_application_connection(): void
    {
        $this->assertSame(['pgsql'], config('backup.backup.source.databases'));
        $this->assertSame('pgsql', config('queue.failed.database'));
    }

    public function test_runner_keeps_automatic_garbage_collection_and_the_existing_memory_limit(): void
    {
        $configuration = simplexml_load_file(base_path('phpunit.xml'));
        $this->assertNotSame('true', (string) $configuration['controlGarbageCollector']);
        $this->assertSame('512M', ini_get('memory_limit'));
        $this->assertTrue(gc_enabled());
    }

    /** @param class-string<Model> $modelClass */
    #[DataProvider('operationalSequenceModels')]
    public function test_operational_model_events_do_not_increment_an_allocated_sequence_again(string $modelClass, string $scopeField, array $scopeAttributes): void
    {
        $scope = 'NUMBERING-'.Str::uuid();
        $attributes = [$scopeField => $scope, ...$scopeAttributes];
        $first = $modelClass::query()->create($attributes);
        $second = $modelClass::query()->create($attributes);

        $this->assertSame([1, 2], [(int) $first->fresh()->seq, (int) $second->fresh()->seq]);
        $this->assertSame($scope.'/0001', $first->fresh()->code);
        $this->assertSame($scope.'/0002', $second->fresh()->code);
        $this->assertSame(2, (int) DB::table('sequence_counters')->where('table_name', $first->getTable())
            ->where('scope_values->'.$scopeField, $scope)->value('last_value'));
    }

    /** @param class-string<Model> $modelClass */
    #[DataProvider('operationalSequenceModels')]
    public function test_new_numbers_follow_retained_archived_evidence_without_rewriting_it(string $modelClass, string $scopeField, array $scopeAttributes): void
    {
        $scope = 'RETAINED-NUMBERING-'.Str::uuid();
        $attributes = [$scopeField => $scope, ...$scopeAttributes];
        $table = (new $modelClass)->getTable();
        $retainedId = DB::table($table)->insertGetId([
            ...$attributes, 'seq' => 40, 'code' => 'ISSUED-UNTOUCHED', 'deleted_at' => now(),
        ]);

        $next = $modelClass::query()->create($attributes);

        $this->assertSame(41, (int) $next->fresh()->seq);
        $this->assertSame($scope.'/0041', $next->fresh()->code);
        $retained = DB::table($table)->find($retainedId);
        $this->assertSame(40, (int) $retained->seq);
        $this->assertSame('ISSUED-UNTOUCHED', $retained->code);
        $this->assertNotNull($retained->deleted_at);
        $this->assertSame(41, (int) DB::table('sequence_counters')->where('table_name', $table)
            ->where('scope_values->'.$scopeField, $scope)->value('last_value'));
    }

    /** @param class-string<Model> $modelClass */
    #[DataProvider('operationalSequenceModels')]
    public function test_explicit_model_numbers_are_issued_exactly_as_reserved(string $modelClass, string $scopeField, array $scopeAttributes): void
    {
        $scope = 'EXPLICIT-NUMBERING-'.Str::uuid();
        $attributes = [$scopeField => $scope, ...$scopeAttributes];
        $explicit = $modelClass::query()->create([...$attributes, 'seq' => 100]);
        $next = $modelClass::query()->create($attributes);

        $this->assertSame([100, 101], [(int) $explicit->fresh()->seq, (int) $next->fresh()->seq]);
        $this->assertSame($scope.'/0100', $explicit->fresh()->code);
        $this->assertSame($scope.'/0101', $next->fresh()->code);
        $this->assertSame(101, (int) DB::table('sequence_counters')->where('table_name', $explicit->getTable())
            ->where('scope_values->'.$scopeField, $scope)->value('last_value'));
    }

    /** @return array<string, array{class-string<Model>, string, array<string, string>}> */
    public static function operationalSequenceModels(): array
    {
        return [
            'analytical lab codes' => [LabCode::class, 'cl_month', ['codeable_type' => 'analysis']],
            'analytical samples' => [Sample::class, 'sample_month', []],
        ];
    }

    public function test_commercial_and_result_configuration_defaults_match_postgresql(): void
    {
        $product = Product::query()->create(['name' => 'Default price regression']);
        $this->assertSame(0, $product->price);
        $this->assertSame(0.0, (float) $product->fresh()->price);
        $this->assertSame(0.0, (float) $product->fresh()->fixed_price);
        $this->assertSame(0.0, (float) $product->fresh()->tax_percentage);

        $parameter = Parameter::query()->create([
            'name' => 'Result configuration regression',
            'result_type' => 'quantitative',
            'decimal_places' => 2,
            'variables' => ['mass' => 'g'],
            'calculation_parameters' => ['factor' => 1.5],
        ]);
        $this->assertFalse($parameter->requires_calculation);
        $this->assertFalse($parameter->fresh()->requires_calculation);
        $this->assertSame(['mass' => 'g'], $parameter->fresh()->variables);
        $this->assertSame(['factor' => 1.5], $parameter->fresh()->calculation_parameters);
        $this->assertTrue(Schema::hasColumn('quotes', 'invoice_id'));
        $this->assertTrue(Schema::hasIndex('quotes', ['invoice_id']));
    }

    public function test_certificate_alignment_is_reversible_and_preserves_legacy_data(): void
    {
        $lab = VAPLab::factory()->create();
        $migration = require database_path('migrations/2026_09_27_103747_align_certificate_fields_with_application.php');
        $migration->down();

        $importId = DB::table('import_certificates')->insertGetId([
            'lab_id' => $lab->id,
            'code' => 'LEGACY-IMPORT', 'entry_port' => 'Luanda', 'exit_port' => 'Lisboa',
            'freight_cost' => 123.45, 'insurance_cost' => 67.89,
            'destination' => 'Angola', 'tax_cost' => 19.5, 'authorization' => 'Inspector',
        ]);
        $exportId = DB::table('export_certificates')->insertGetId([
            'lab_id' => $lab->id,
            'code' => 'LEGACY-EXPORT', 'origin_country' => 'Angola', 'authorization' => 'Inspector',
        ]);
        DB::table('importcert_items')->insert(['lab_id' => $lab->id, 'certificate_id' => $importId, 'qty' => 4]);
        DB::table('exportcert_items')->insert(['lab_id' => $lab->id, 'certificate_id' => $exportId, 'qty' => 5]);

        $migration->up();

        $import = DB::table('import_certificates')->find($importId);
        $this->assertSame('LEGACY-IMPORT', $import->cert_no);
        $this->assertSame('Luanda', $import->port_entry);
        $this->assertSame('Lisboa', $import->port_exit);
        $this->assertSame(123.45, (float) $import->cost_freight);
        $this->assertSame(67.89, (float) $import->cost_insurance);
        $this->assertSame('Inspector', $import->authorized_personnel);
        $this->assertSame('Angola', $import->destination);
        $this->assertSame(19.5, (float) $import->tax_cost);
        $this->assertNull($import->destination_country_id);
        $this->assertNull($import->vat);
        $this->assertNull($import->date);
        $this->assertSame('LEGACY-EXPORT', DB::table('export_certificates')->find($exportId)->cert_no);
        $this->assertSame('Angola', DB::table('export_certificates')->find($exportId)->origin_country);
        $this->assertSame(4.0, (float) DB::table('import_certificate_items')->where('certificate_id', $importId)->value('qty'));
        $this->assertSame(5.0, (float) DB::table('export_certificate_items')->where('certificate_id', $exportId)->value('qty'));

        $migration->down();
        $this->assertSame('LEGACY-IMPORT', DB::table('import_certificates')->find($importId)->code);
        $this->assertSame('LEGACY-EXPORT', DB::table('export_certificates')->find($exportId)->code);
        $migration->up();
    }
}
