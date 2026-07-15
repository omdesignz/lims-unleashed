<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\Product;
use App\Models\User;
use App\Support\ExportHubCatalog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class ExportHubTest extends TestCase
{
    use DatabaseTransactions;

    public function test_hub_catalog_and_datasets_are_isolated_by_export_permission(): void
    {
        $customerExporter = $this->userWithPermissions(['export_customers']);

        $this->actingAs($customerExporter)
            ->get(route('exports.index', ['dataset' => 'customers']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Exports/Index')
                ->where('selectedDataset', 'customers')
                ->has('datasets', 1)
                ->where('datasets.0.key', 'customers')
            );

        $this->actingAs($customerExporter)
            ->get(route('exports.index', ['dataset' => 'products']))
            ->assertForbidden();

        $this->actingAs($customerExporter)
            ->get(route('exports.download', ['dataset' => 'activity_log']))
            ->assertForbidden();

        $userWithoutExports = User::factory()->create(['is_active' => true]);

        $this->actingAs($userWithoutExports)
            ->get(route('exports.index'))
            ->assertForbidden();
    }

    public function test_activity_log_export_is_filtered_streamed_and_formula_safe(): void
    {
        $exporter = $this->userWithPermissions(['export_activity_log']);
        $batchUuid = (string) Str::uuid();
        $activity = Activity::query()->create([
            'log_name' => 'codex-export-audit',
            'description' => '=WEBSERVICE("https://invalid.test")',
            'event' => 'export-test',
            'causer_type' => 'user',
            'causer_id' => $exporter->id,
            'subject_type' => Customer::class,
            'subject_id' => 987654,
            'properties' => ['attributes' => ['reference' => 'EXPORT-HUB-ACTIVITY']],
            'batch_uuid' => $batchUuid,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->travelTo(now()->startOfSecond());

        $response = $this->actingAs($exporter)
            ->get(route('exports.download', [
                'dataset' => 'activity_log',
                'batch_uuid' => $batchUuid,
            ]))
            ->assertOk()
            ->assertDownload('registo-actividade-'.now()->format('Ymd-His').'.xlsx');

        $sheet = $this->worksheetFromResponse($response->baseResponse);
        $rows = collect($sheet->toArray());
        $activityRow = $rows->first(fn (array $row): bool => (int) $row[0] === $activity->id);

        $this->assertSame('Data e hora', $sheet->getCell('B1')->getValue());
        $this->assertCount(2, $rows);
        $this->assertNotNull($activityRow);
        $this->assertSame("'=WEBSERVICE(\"https://invalid.test\")", $activityRow[4]);
        $this->assertSame($exporter->name, $activityRow[6]);
        $this->assertSame('A2', $sheet->getFreezePane());
        $this->assertNotSame('', $sheet->getAutoFilter()->getRange());

        $this->actingAs($exporter)
            ->get(route('systemactivity.export', ['batch_uuid' => $batchUuid]))
            ->assertOk()
            ->assertDownload('registo-actividade-'.now()->format('Ymd-His').'.xlsx');
    }

    public function test_customer_and_product_exports_apply_master_data_filters(): void
    {
        $exporter = $this->userWithPermissions(['export_customers', 'export_products']);
        $suffix = Str::lower(Str::random(8));
        $customer = Customer::query()->create([
            'code' => 'EXP-'.$suffix,
            'name' => '=CUSTOMER-'.$suffix,
            'description' => 'Customer export fixture '.$suffix,
        ]);
        $product = Product::query()->create([
            'name' => '=PRODUCT-'.$suffix,
            'description' => 'Product export fixture '.$suffix,
            'price' => 1250.75,
            'fixed_price' => 1500,
            'charge_tax' => true,
            'withhold_tax' => false,
            'tax_percentage' => 14,
        ]);
        $this->travelTo(now()->startOfSecond());

        $customerResponse = $this->actingAs($exporter)
            ->get(route('exports.download', [
                'dataset' => 'customers',
                'search' => $customer->code,
                'status' => 'active',
            ]))
            ->assertOk()
            ->assertDownload('clientes-'.now()->format('Ymd-His').'.xlsx');

        $customerSheet = $this->worksheetFromResponse($customerResponse->baseResponse);
        $customerRow = collect($customerSheet->toArray())->first(fn (array $row): bool => (int) $row[0] === $customer->id);
        $this->assertSame('Cliente', $customerSheet->getCell('C1')->getValue());
        $this->assertNotNull($customerRow);
        $this->assertSame("'=CUSTOMER-{$suffix}", $customerRow[2]);
        $this->assertSame('Activo', $customerRow[5]);

        $productResponse = $this->actingAs($exporter)
            ->get(route('exports.download', [
                'dataset' => 'products',
                'search' => $suffix,
                'status' => 'active',
                'tax_status' => 'taxable',
                'min_price' => 1200,
                'max_price' => 1300,
            ]))
            ->assertOk()
            ->assertDownload('produtos-'.now()->format('Ymd-His').'.xlsx');

        $productSheet = $this->worksheetFromResponse($productResponse->baseResponse);
        $productRow = collect($productSheet->toArray())->first(fn (array $row): bool => (int) $row[0] === $product->id);
        $this->assertSame('Produto', $productSheet->getCell('B1')->getValue());
        $this->assertNotNull($productRow);
        $this->assertSame("'=PRODUCT-{$suffix}", $productRow[1]);
        $this->assertSame(1250.75, (float) $productRow[5]);
        $this->assertSame(DataType::TYPE_NUMERIC, $productSheet->getCell('F2')->getDataType());
        $this->assertSame('Sim', $productRow[7]);
    }

    public function test_export_filters_validate_ranges_and_required_indexes_exist(): void
    {
        $exporter = $this->userWithPermissions(['export_activity_log', 'export_products']);

        $this->actingAs($exporter)
            ->from(route('exports.index', ['dataset' => 'activity_log']))
            ->get(route('exports.index', [
                'dataset' => 'activity_log',
                'date_from' => '2026-07-15',
                'date_to' => '2026-07-14',
            ]))
            ->assertRedirect(route('exports.index', ['dataset' => 'activity_log']))
            ->assertSessionHasErrors('date_to');

        $this->actingAs($exporter)
            ->from(route('exports.index', ['dataset' => 'products']))
            ->get(route('exports.index', [
                'dataset' => 'products',
                'min_price' => 100,
                'max_price' => 50,
            ]))
            ->assertRedirect(route('exports.index', ['dataset' => 'products']))
            ->assertSessionHasErrors('max_price');

        $activityIndexes = collect(Schema::getIndexes('activity_log'))->pluck('name');
        $customerIndexes = collect(Schema::getIndexes('customers'))->pluck('name');
        $productIndexes = collect(Schema::getIndexes('products'))->pluck('name');

        $this->assertContains('activity_log_created_at_index', $activityIndexes);
        $this->assertContains('activity_log_event_created_at_index', $activityIndexes);
        $this->assertContains('activity_log_batch_uuid_index', $activityIndexes);
        $this->assertContains('customers_export_status_date_index', $customerIndexes);
        $this->assertContains('products_export_status_date_index', $productIndexes);
    }

    public function test_extended_catalog_is_permission_isolated(): void
    {
        $exporter = $this->userWithPermissions(['export_parameters', 'export_invoices']);

        $this->actingAs($exporter)
            ->get(route('exports.index', ['dataset' => 'parameters']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Exports/Index')
                ->where('selectedDataset', 'parameters')
                ->has('datasets', 2)
                ->where('datasets.0.key', 'parameters')
                ->where('datasets.1.key', 'invoices')
                ->where('datasets.0.filter_group', 'parameters')
            );

        $this->actingAs($exporter)
            ->get(route('exports.download', ['dataset' => 'quotes']))
            ->assertForbidden();
    }

    public function test_every_extended_direct_dataset_produces_a_structured_workbook(): void
    {
        $catalog = app(ExportHubCatalog::class);
        $datasets = collect($catalog->directKeys())->reject(fn (string $dataset): bool => in_array($dataset, ['activity_log', 'customers', 'products'], true));
        $permissions = $datasets->map(fn (string $dataset): string => $catalog->get($dataset)['permission'])->all();
        $exporter = $this->userWithPermissions($permissions);

        foreach ($datasets as $dataset) {
            $response = $this->actingAs($exporter)
                ->get(route('exports.download', ['dataset' => $dataset, 'status' => 'all']))
                ->assertOk();

            $sheet = $this->worksheetFromResponse($response->baseResponse);

            $this->assertSame($catalog->get($dataset)['sheet'], $sheet->getTitle(), $dataset);
            $this->assertSame($catalog->columns($dataset)[0]['label'], $sheet->getCell('A1')->getValue(), $dataset);
            $this->assertSame('A2', $sheet->getFreezePane(), $dataset);
            $this->assertNotSame('', $sheet->getAutoFilter()->getRange(), $dataset);
        }
    }

    public function test_parameter_and_invoice_exports_apply_domain_filters_and_preserve_types(): void
    {
        $exporter = $this->userWithPermissions(['export_parameters', 'export_invoices']);
        $suffix = Str::lower(Str::random(8));
        $customer = Customer::query()->create([
            'code' => 'DOC-'.$suffix,
            'name' => 'Export Customer '.$suffix,
        ]);
        $parameterId = DB::table('parameters')->insertGetId([
            'code' => 'PAR-'.$suffix,
            'name' => '=PARAMETER-'.$suffix,
            'description' => 'Filtered parameter export',
            'price' => 875.25,
            'active' => true,
            'charge_tax' => false,
            'withhold_tax' => false,
            'requires_calculation' => false,
            'result_is_qualitative' => false,
            'result_type' => DB::table('parameters')->whereNotNull('result_type')->value('result_type') ?? 'numeric',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $invoiceId = DB::table('invoices')->insertGetId([
            'user_id' => $exporter->id,
            'customer_id' => $customer->id,
            'inv_no' => 'FT-'.$suffix,
            'invoice_month' => now()->format('Y'),
            'date' => now()->toDateString(),
            'sub_total' => 1500.50,
            'total' => 1500.50,
            'amount_due' => 0,
            'status_code' => 'N',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $parameterResponse = $this->actingAs($exporter)
            ->get(route('exports.download', [
                'dataset' => 'parameters',
                'search' => 'PAR-'.$suffix,
                'enabled' => 'yes',
                'min_price' => 800,
                'max_price' => 900,
            ]))
            ->assertOk();
        $parameterSheet = $this->worksheetFromResponse($parameterResponse->baseResponse);
        $parameterRow = collect($parameterSheet->toArray())->first(fn (array $row): bool => (int) $row[0] === $parameterId);

        $this->assertNotNull($parameterRow);
        $this->assertSame("'=PARAMETER-{$suffix}", $parameterRow[2]);
        $this->assertSame(875.25, (float) $parameterRow[4]);
        $this->assertSame(DataType::TYPE_NUMERIC, $parameterSheet->getCell('E2')->getDataType());

        $invoiceResponse = $this->actingAs($exporter)
            ->get(route('exports.download', [
                'dataset' => 'invoices',
                'search' => 'FT-'.$suffix,
                'payment_status' => 'paid',
                'min_total' => 1500,
                'max_total' => 1501,
            ]))
            ->assertOk();
        $invoiceSheet = $this->worksheetFromResponse($invoiceResponse->baseResponse);
        $invoiceRow = collect($invoiceSheet->toArray())->first(fn (array $row): bool => (int) $row[0] === $invoiceId);

        $this->assertNotNull($invoiceRow);
        $this->assertSame('Paga', $invoiceRow[15]);
        $this->assertSame(1500.50, (float) $invoiceRow[13]);
        $this->assertSame(DataType::TYPE_NUMERIC, $invoiceSheet->getCell('N2')->getDataType());
    }

    public function test_extended_export_permissions_and_indexes_are_installed(): void
    {
        $catalog = app(ExportHubCatalog::class);
        $expectedPermissions = collect($catalog->directDatasets())->pluck('permission')->sort()->values();
        $actualPermissions = Permission::query()->whereIn('name', $expectedPermissions)->pluck('name')->sort()->values();

        $this->assertSame($expectedPermissions->all(), $actualPermissions->all());
        $this->assertContains('parameters_export_status_date_index', collect(Schema::getIndexes('parameters'))->pluck('name'));
        $this->assertContains('invoices_export_customer_date_index', collect(Schema::getIndexes('invoices'))->pluck('name'));
        $this->assertContains('quotes_export_conversion_date_index', collect(Schema::getIndexes('quotes'))->pluck('name'));
        $this->assertContains('quality_cert_export_validation_index', collect(Schema::getIndexes('quality_certificates'))->pluck('name'));
        $this->assertContains('occurrences_export_status_date_index', collect(Schema::getIndexes('occurrences'))->pluck('name'));
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(collect($permissions)->map(fn (string $permission) => Permission::findByName($permission)));

        return $user;
    }

    private function worksheetFromResponse(BinaryFileResponse $response): Worksheet
    {
        return IOFactory::load($response->getFile()->getPathname())->getActiveSheet();
    }
}
