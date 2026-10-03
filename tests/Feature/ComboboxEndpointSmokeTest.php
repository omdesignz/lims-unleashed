<?php

namespace Tests\Feature;

use App\Models\AnalysisCategory;
use App\Models\Department;
use App\Models\EquipmentCategory;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\ItemStatus;
use App\Models\Matrix;
use App\Models\PaidService;
use App\Models\Parameter;
use App\Models\Permission;
use App\Models\PhytosanitaryProduct;
use App\Models\Profile;
use App\Models\ResultCategory;
use App\Models\Role;
use App\Models\TaxExemption;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ComboboxEndpointSmokeTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    #[DataProvider('comboboxRoutes')]
    public function test_verified_admin_can_fetch_a_combobox_endpoint(string $routeName, array $parameters): void
    {
        $user = $this->verifiedAdmin();
        if (in_array($routeName, [
            'invoices.getInvoice',
            'receipts.getReceipt',
            'creditnotes.getCreditNote',
            'quotes.getQuote',
            'importcertificates.getImportCertificate',
            'exportcertificates.getExportCertificate',
            'users.getUser',
            'proposalcomplianceagreements.getProposalComplianceAgreement',
            'worksheets.getWorksheet',
            'iwarehouses.getInventoryItemWarehouse',
            'itransfers.getInventoryItemTransfer',
            'inventory.getInventory',
            'inventory.getInventoryReagentItem',
            'iitems.getInventoryItem',
            'iitems.getReagentInventoryItem',
            'iequipments.getInventoryItem',
            'iorders.getInventoryOrder',
        ], true)) {
            $this->actingAs($user)->getJson(route($routeName, $parameters))->assertForbidden();
            $lab = VAPLab::factory()->create();
            DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
            $this->withSession(['active_lab_id' => $lab->id]);
        }

        $response = $this->actingAs($user)->getJson(route($routeName, $parameters));

        $response->assertOk();
        $this->assertIsArray($response->json());
        $this->assertStringContainsString('application/json', (string) $response->headers->get('content-type'));
    }

    public static function comboboxRoutes(): array
    {
        $checks = [
            ['name' => 'units.getUnit', 'params' => ['q' => 'a']],
            ['name' => 'standards.getStandard', 'params' => ['q' => 'a']],
            ['name' => 'departments.getDepartment', 'params' => ['q' => 'a']],
            ['name' => 'users.getUser', 'params' => ['q' => 'a']],
            ['name' => 'roles.getRole', 'params' => ['q' => 'a']],
            ['name' => 'permissions.getPermission', 'params' => ['q' => 'a']],
            ['name' => 'profiles.getProfile', 'params' => ['q' => 'a']],
            ['name' => 'parameters.getParameter', 'params' => ['q' => 'a']],
            ['name' => 'customers.getCustomer', 'params' => ['q' => 'a']],
            ['name' => 'warehouses.getWarehouse', 'params' => ['q' => 'a']],
            ['name' => 'countries.getCountry', 'params' => ['q' => 'a']],
            ['name' => 'products.getProduct', 'params' => ['q' => 'a']],
            ['name' => 'matrixes.getMatrix', 'params' => ['q' => 'a']],
            ['name' => 'variables.getVariable', 'params' => ['q' => 'a']],
            ['name' => 'temperatures.getTemperature', 'params' => ['q' => 'a']],
            ['name' => 'analysiscategories.getAnalysisCategory', 'params' => ['q' => 'a']],
            ['name' => 'collectioncollaborations.getCollectionCollaboration', 'params' => ['q' => 'a']],
            ['name' => 'collectionendresults.getCollectionEndResult', 'params' => ['q' => 'a']],
            ['name' => 'collectionreasons.getCollectionReason', 'params' => ['q' => 'a']],
            ['name' => 'currencies.getCurrency', 'params' => ['q' => 'a']],
            ['name' => 'customercategories.getCustomerCategory', 'params' => ['q' => 'a']],
            ['name' => 'equipmentcategories.getEquipmentCategory', 'params' => ['q' => 'a']],
            ['name' => 'faqcategories.getFAQCategory', 'params' => ['q' => 'a']],
            ['name' => 'faqs.getFAQ', 'params' => ['q' => 'a']],
            ['name' => 'faqanswers.getFAQAnswer', 'params' => ['q' => 'a']],
            ['name' => 'formulas.getFormula', 'params' => ['q' => 'a']],
            ['name' => 'maintenancecategories.getMaintenanceCategory', 'params' => ['q' => 'a']],
            ['name' => 'nwps.getNwp', 'params' => ['q' => 'a']],
            ['name' => 'occurrencecategories.getOccurrenceCategory', 'params' => ['q' => 'a']],
            ['name' => 'occurrenceorigins.getOccurrenceOrigin', 'params' => ['q' => 'a']],
            ['name' => 'occurrencestatuses.getOccurrenceStatus', 'params' => ['q' => 'a']],
            ['name' => 'packagingcategories.getPackagingCategory', 'params' => ['q' => 'a']],
            ['name' => 'phytosanitary_products.getPhytosanitaryProduct', 'params' => ['q' => 'a']],
            ['name' => 'resultcategories.getResultCategory', 'params' => ['q' => 'a']],
            ['name' => 'customerrequestcategories.getCustomerRequestCategory', 'params' => ['q' => 'a']],
            ['name' => 'invoicecategories.getInvoiceCategory', 'params' => ['q' => 'a']],
            ['name' => 'itemcategories.getItemCategory', 'params' => ['q' => 'a']],
            ['name' => 'itemstatuses.getItemStatus', 'params' => ['q' => 'a']],
            ['name' => 'iunits.getInventoryUnit', 'params' => ['q' => 'a']],
            ['name' => 'itypes.getInventoryItemType', 'params' => ['q' => 'a']],
            ['name' => 'ilocations.getInventoryItemLocation', 'params' => ['q' => 'a']],
            ['name' => 'iwarehouses.getInventoryItemWarehouse', 'params' => ['q' => 'a']],
            ['name' => 'isuppliers.getInventoryItemSupplier', 'params' => ['q' => 'a']],
            ['name' => 'iorders.getInventoryOrder', 'params' => ['q' => 'a']],
            ['name' => 'itransactiontypes.getInventoryTransactionType', 'params' => ['q' => 'a']],
            ['name' => 'taxexemptions.getExemption', 'params' => ['q' => 'a']],
            ['name' => 'taxtypes.getTaxType', 'params' => ['q' => 'a']],
            ['name' => 'transportcategories.getTransportCategory', 'params' => ['q' => 'a']],
            ['name' => 'paymentcategories.getPaymentCategory', 'params' => ['q' => 'a']],
            ['name' => 'vehicles.getVehicle', 'params' => ['q' => 'a']],
            ['name' => 'messages.getMessage', 'params' => ['q' => 'a']],
            ['name' => 'inventory.getInventory', 'params' => ['q' => 'a']],
            ['name' => 'inventory.getInventoryReagentItem', 'params' => ['q' => 'a']],
            ['name' => 'iitems.getInventoryItem', 'params' => ['q' => 'a']],
            ['name' => 'iitems.getReagentInventoryItem', 'params' => ['q' => 'a']],
            ['name' => 'iequipments.getInventoryItem', 'params' => ['q' => 'a']],
            ['name' => 'ideliveries.getInventoryDelivery', 'params' => ['q' => 'a']],
            ['name' => 'itransfers.getInventoryItemTransfer', 'params' => ['q' => 'a']],
            ['name' => 'customerrequests.getCustomerRequest', 'params' => ['q' => 'a']],
            ['name' => 'protocols.getProtocol', 'params' => ['q' => 'a']],
            ['name' => 'paidservices.getPaidService', 'params' => ['q' => 'a']],
            ['name' => 'proposaltemplates.getProposalTemplate', 'params' => ['q' => 'a']],
            ['name' => 'proposalcomplianceagreements.getProposalComplianceAgreement', 'params' => ['q' => 'a']],
            ['name' => 'qualitycertificates.getQualityCertificate', 'params' => ['q' => 'a']],
            ['name' => 'invoices.getInvoice', 'params' => ['q' => 'a']],
            ['name' => 'worksheets.getWorksheet', 'params' => ['q' => 'a']],
            ['name' => 'importcertificates.getImportCertificate', 'params' => ['q' => 'a']],
            ['name' => 'exportcertificates.getExportCertificate', 'params' => ['q' => 'a']],
            ['name' => 'creditnotes.getCreditNote', 'params' => ['q' => 'a']],
            ['name' => 'contractguides.getContractGuide', 'params' => ['q' => 'a']],
            ['name' => 'quotes.getQuote', 'params' => ['q' => 'a']],
            ['name' => 'receipts.getReceipt', 'params' => ['q' => 'a']],
        ];

        $datasets = [];

        foreach ($checks as $check) {
            $datasets[$check['name']] = [$check['name'], $check['params']];
        }

        return $datasets;
    }

    public function test_active_catalog_lookups_use_the_aligned_schema(): void
    {
        $admin = $this->verifiedAdmin();
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $admin->id]);
        $this->withSession(['active_lab_id' => $lab->id]);
        $suffix = Str::upper(Str::random(8));
        $equipmentCategory = EquipmentCategory::query()->create([
            'name' => 'Equipment '.$suffix,
            'code' => 'EQ-'.$suffix,
        ]);
        $phytosanitaryProduct = PhytosanitaryProduct::query()->create([
            'name' => 'Phytosanitary '.$suffix,
        ]);
        $paidService = PaidService::query()->create([
            'name' => 'Paid service '.$suffix,
        ]);
        $itemCategory = ItemCategory::query()->create([
            'name' => 'Item category '.$suffix,
            'code' => 'IT-'.$suffix,
        ]);
        $itemStatus = ItemStatus::query()->create([
            'name' => 'Item status '.$suffix,
            'category_id' => $itemCategory->id,
        ]);
        $inventoryItemId = DB::table('i_items')->insertGetId([
            'lab_id' => $lab->id,
            'name' => 'Equipment item '.$suffix,
            'eq_cat_id' => $equipmentCategory->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue(Schema::hasColumn('i_items', 'eq_cat_id'));
        $this->assertSame($equipmentCategory->id, InventoryItem::query()->findOrFail($inventoryItemId)->eq_cat->id);
        $this->assertSame(0.0, (float) $paidService->fresh()->price);
        $this->actingAs($admin)->getJson(route('equipmentcategories.getEquipmentCategory', ['q' => $suffix]))
            ->assertOk()->assertJsonPath('0.id', $equipmentCategory->id);
        $this->getJson(route('phytosanitary_products.getPhytosanitaryProduct', ['q' => $suffix]))
            ->assertOk()->assertJsonPath('0.id', $phytosanitaryProduct->id);
        $this->getJson(route('paidservices.getPaidService', ['q' => $suffix]))
            ->assertOk()->assertJsonPath('0.id', $paidService->id);
        $this->getJson(route('itemstatuses.getItemStatus', ['q' => $suffix, 'category_id' => $itemCategory->id]))
            ->assertOk()->assertJsonPath('0.id', $itemStatus->id);

        $migration = require database_path('migrations/2026_09_28_125658_align_active_catalog_tables_and_inventory_category_links.php');
        try {
            $migration->down();
            $this->fail('Catalog rollback cannot discard retained catalog records or inventory links.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('retained records', $exception->getMessage());
            $this->assertTrue(Schema::hasTable('equipment_categories'));
        }
    }

    public function test_role_and_permission_labels_are_searchable(): void
    {
        $user = $this->verifiedAdmin();
        $role = Role::findOrCreate('admin', 'web');
        $role->update(['label' => 'Laboratory Administrator']);
        $permission = Permission::findOrCreate('view_regression_lookup', 'web');
        $permission->update(['label' => 'Certified Reviewer']);

        $this->actingAs($user)
            ->getJson(route('roles.getRole', ['q' => 'Laboratory Administrator']))
            ->assertOk()->assertJsonPath('0.id', $role->id)
            ->assertJsonPath('0.label', 'Laboratory Administrator');

        $this->getJson(route('permissions.getPermission', ['q' => 'Certified Reviewer']))
            ->assertOk()->assertJsonPath('0.id', $permission->id)
            ->assertJsonPath('0.label', 'Certified Reviewer');
    }

    public function test_unauthenticated_combobox_fetches_return_json_instead_of_login_html(): void
    {
        $this->getJson(route('units.getUnit', ['q' => 'a']))
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_unauthenticated_browser_modal_routes_redirect_to_login_instead_of_json(): void
    {
        $checks = [
            route('quotes.getConvertToInvoiceModal', ['id' => 1]),
            route('importcertificates.getIssueInvoiceModal', ['id' => 1]),
            route('exportcertificates.getIssueInvoiceModal', ['id' => 1]),
        ];

        foreach ($checks as $url) {
            $response = $this->get($url);

            $response->assertRedirect(route('login'));
            $this->assertStringNotContainsString('application/json', (string) $response->headers->get('content-type'));
        }
    }

    public function test_catalog_scope_endpoints_and_edit_pages_expose_control_metadata(): void
    {
        $user = $this->verifiedAdmin();
        $department = Department::factory()->create();
        $unit = Unit::query()->create(['code' => 'mg/L']);
        $resultCategory = ResultCategory::query()->create(['name' => 'Numeric result']);
        $suffix = Str::upper(Str::random(6));

        $analysisCategory = AnalysisCategory::query()->create([
            'name' => 'Scope Category '.$suffix,
            'code' => 'SC-'.$suffix,
            'description' => 'Scope control category',
            'department_id' => $department->id,
        ]);

        $parameter = Parameter::query()->create([
            'name' => 'Scope Parameter '.$suffix,
            'code' => 'SP-'.$suffix,
            'description' => 'Scope control parameter',
            'price' => 1250,
            'charge_tax' => false,
            'withhold_tax' => false,
            'active' => true,
            'tax_percentage' => 0,
            'optimal_analysis_time' => '24h',
        ]);

        $profile = Profile::query()->create([
            'name' => 'Scope Profile '.$suffix,
            'code' => 'PR-'.$suffix,
            'description' => 'Scope control profile',
            'price' => 1250,
            'category_id' => $analysisCategory->id,
        ]);

        $profile->parameters()->attach($parameter->id, [
            'category_id' => $resultCategory->id,
            'category_label' => $resultCategory->name,
            'unit_id' => $unit->id,
            'unit_label' => $unit->code,
            'standard_id' => null,
            'standard_label' => null,
            'formula_id' => null,
            'formula_label' => null,
            'protocol_id' => null,
            'protocol_label' => null,
            'nwp_id' => null,
            'nwp_label' => null,
            'min_ref_value' => '1',
            'max_ref_value' => '10',
            'dilutions' => null,
            'extra_data' => json_encode([]),
            'optimal_analysis_time' => '24h',
            'count' => true,
            'ref_val_origin' => 'ISO 17025',
        ]);

        $matrix = Matrix::query()->create([
            'code' => 'MX-'.$suffix,
            'description' => 'Scope control matrix',
            'price' => 1250,
            'fixed_price' => 0,
            'charge_tax' => false,
            'withhold_tax' => false,
            'tax_percentage' => 0,
            'exemption_id' => TaxExemption::query()->value('id'),
            'exemption_code' => TaxExemption::query()->value('code'),
        ]);

        $matrix->profiles()->attach($profile->id, [
            'matrix' => $matrix->code,
            'profile' => $profile->name,
        ]);

        $parameterResponse = $this->actingAs($user)->getJson(route('parameters.getParameter', ['q' => $suffix]));
        $parameterPayload = collect($parameterResponse->json())->firstWhere('id', $parameter->id);

        $parameterResponse->assertOk();
        $this->assertNotNull($parameterPayload);
        $this->assertSame($parameter->code, $parameterPayload['code']);
        $this->assertSame('24h', $parameterPayload['optimal_analysis_time']);
        $this->assertTrue((bool) $parameterPayload['active']);

        $profileResponse = $this->actingAs($user)->getJson(route('profiles.getProfile', ['q' => $suffix]));
        $profilePayload = collect($profileResponse->json())->firstWhere('id', $profile->id);

        $profileResponse->assertOk();
        $this->assertNotNull($profilePayload);
        $this->assertSame($department->id, $profilePayload['department_id']);
        $this->assertSame($department->name, $profilePayload['department_name']);
        $this->assertSame(1, (int) $profilePayload['active_parameter_count']);
        $this->assertSame(1, (int) $profilePayload['total_parameter_count']);

        $this->actingAs($user)
            ->get(route('profiles.edit', $profile))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profiles/Edit')
                ->where('record.category_id.department_id', $department->id)
                ->where('record.category_id.department_name', $department->name)
                ->where('record.parameters.0.parameter_id.code', $parameter->code)
                ->where('record.parameters.0.parameter_id.active', true)
            );

        $this->actingAs($user)
            ->get(route('matrixes.edit', $matrix))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Matrixes/Edit')
                ->where('record.profiles.0.profile_id.code', $profile->code)
                ->where('record.profiles.0.profile_id.department_id', $department->id)
                ->where('record.profiles.0.profile_id.department_name', $department->name)
                ->where('record.profiles.0.profile_id.active_parameter_count', 1)
                ->where('record.profiles.0.profile_id.total_parameter_count', 1)
            );
    }

    public function test_result_category_lookup_excludes_soft_deleted_matches(): void
    {
        $user = $this->verifiedAdmin();
        $suffix = Str::upper(Str::random(6));

        $activeCategory = ResultCategory::query()->create([
            'name' => 'Scope Result Category '.$suffix,
            'description' => 'Active category for combobox filtering',
        ]);

        $deletedCategory = ResultCategory::query()->create([
            'name' => 'Deleted Result Category '.$suffix,
            'description' => 'Deleted category for combobox filtering',
        ]);
        $deletedCategory->delete();

        $response = $this->actingAs($user)->getJson(route('resultcategories.getResultCategory', ['q' => $suffix]));

        $response->assertOk();

        $payload = collect($response->json());

        $this->assertTrue($payload->contains(fn (array $item) => $item['id'] === $activeCategory->id));
        $this->assertFalse($payload->contains(fn (array $item) => $item['id'] === $deletedCategory->id));
    }
}
