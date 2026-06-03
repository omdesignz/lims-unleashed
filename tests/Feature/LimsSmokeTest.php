<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\AnalysisCategory;
use App\Models\CollectionCollaboration;
use App\Models\CollectionEndResult;
use App\Models\CollectionProduct;
use App\Models\CollectionReason;
use App\Models\ContactCategory;
use App\Models\ContractGuide;
use App\Models\Country;
use App\Models\CreditNote;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomerCategory;
use App\Models\CustomerRequest;
use App\Models\CustomerRequestCategory;
use App\Models\Department;
use App\Models\DiscountCategory;
use App\Models\EquipmentCategory;
use App\Models\ExportCertificate;
use App\Models\FAQ;
use App\Models\FAQAnswer;
use App\Models\FAQCategory;
use App\Models\Formula;
use App\Models\ImportCertificate;
use App\Models\InventoryBatch;
use App\Models\InventoryDelivery;
use App\Models\InventoryItem;
use App\Models\InventoryItemLocation;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryItemType;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryNeed;
use App\Models\InventoryOrder;
use App\Models\InventoryUnit;
use App\Models\Invoice;
use App\Models\InvoiceCategory;
use App\Models\ItemCategory;
use App\Models\ItemStatus;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceTask;
use App\Models\Matrix;
use App\Models\NormativeWorkProcedure;
use App\Models\Occurrence;
use App\Models\OccurrenceCategory;
use App\Models\OccurrenceOrigin;
use App\Models\OccurrenceStatus;
use App\Models\PackagingCategory;
use App\Models\PaidService;
use App\Models\Parameter;
use App\Models\PaymentCategory;
use App\Models\PhytosanitaryProduct;
use App\Models\Product;
use App\Models\ProficiencyTest;
use App\Models\Profile;
use App\Models\Proposal;
use App\Models\Protocol;
use App\Models\QualityCertificate;
use App\Models\Quote;
use App\Models\ReagentConsumption;
use App\Models\Receipt;
use App\Models\ResultCategory;
use App\Models\Role;
use App\Models\Standard;
use App\Models\TaxExemption;
use App\Models\TaxType;
use App\Models\Temperature;
use App\Models\TransportCategory;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPLabel;
use App\Models\VAPLabelTemplate;
use App\Models\VAPNonConformity;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\VAPSampleEntry;
use App\Models\Variable;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Tests\TestCase;

class LimsSmokeTest extends TestCase
{
    private function verifiedAdmin(): User
    {
        $admin = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->first();

        $this->assertNotNull($admin, 'Expected at least one verified admin user for smoke testing.');

        return $admin;
    }

    public function test_verified_admin_can_open_core_lims_pages(): void
    {
        $user = $this->verifiedAdmin();

        $routes = [
            'dashboard',
            'analysis.index',
            'samples.index',
            'standards.index',
            'occurrences.index',
            'complaints.index',
            'customers.index',
            'directcollections.index',
            'programmedcollections.index',
            'management-reviews.index',
            'qualitycertificates.index',
            'importcertificates.index',
            'exportcertificates.index',
            'inventory.index',
            'iequipments.index',
            'systemactivity.index',
            'vap_samples.index',
            'vap_non_conformities.index',
            'worksheets.index',
        ];

        $failures = [];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get(route($route));

            if (! $response->isSuccessful() && ! $response->isRedirection()) {
                $failures[] = sprintf(
                    'Expected route [%s] to load or redirect successfully, got HTTP %d.',
                    $route,
                    $response->getStatusCode()
                );
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_verified_admin_can_open_broad_backoffice_index_and_create_pages(): void
    {
        $user = $this->verifiedAdmin();

        $routes = [
            'analysiscategories.index',
            'analysiscategories.create',
            'collectioncollaborations.index',
            'collectioncollaborations.create',
            'collectionendresults.index',
            'collectionendresults.create',
            'collectionreasons.index',
            'collectionreasons.create',
            'contactcategories.index',
            'contactcategories.create',
            'countries.index',
            'countries.create',
            'currencies.index',
            'currencies.create',
            'customercategories.index',
            'customercategories.create',
            'customerrequestcategories.index',
            'customerrequestcategories.create',
            'customerrequests.index',
            'customerrequests.create',
            'departments.index',
            'departments.create',
            'discountcategories.index',
            'discountcategories.create',
            'equipmentcategories.index',
            'equipmentcategories.create',
            'faqanswers.index',
            'faqanswers.create',
            'faqcategories.index',
            'faqcategories.create',
            'faqs.index',
            'faqs.create',
            'formulas.index',
            'formulas.create',
            'iitems.index',
            'iitems.create',
            'iequipments.index',
            'iequipments.create',
            'ideliveries.index',
            'ideliveries.create',
            'ilocations.index',
            'ilocations.create',
            'invoicecategories.index',
            'invoicecategories.create',
            'invoices.index',
            'invoices.create',
            'itemcategories.index',
            'itemcategories.create',
            'itemstatuses.index',
            'itemstatuses.create',
            'iunits.index',
            'iunits.create',
            'itypes.index',
            'itypes.create',
            'iwarehouses.index',
            'iwarehouses.create',
            'isuppliers.index',
            'isuppliers.create',
            'maintenancecategories.index',
            'maintenancecategories.create',
            'matrixes.index',
            'matrixes.create',
            'nwps.index',
            'nwps.create',
            'occurrencecategories.index',
            'occurrencecategories.create',
            'occurrenceorigins.index',
            'occurrenceorigins.create',
            'occurrencestatuses.index',
            'occurrencestatuses.create',
            'packagingcategories.index',
            'packagingcategories.create',
            'paidservices.index',
            'paidservices.create',
            'parameters.index',
            'parameters.create',
            'paymentcategories.index',
            'paymentcategories.create',
            'phytosanitary_products.index',
            'phytosanitary_products.create',
            'profiles.index',
            'profiles.create',
            'protocols.index',
            'protocols.create',
            'receipts.index',
            'receipts.create',
            'resultcategories.index',
            'resultcategories.create',
            'roles.index',
            'roles.create',
            'taxtypes.index',
            'taxtypes.create',
            'taxexemptions.index',
            'taxexemptions.create',
            'temperatures.index',
            'temperatures.create',
            'transportcategories.index',
            'transportcategories.create',
            'units.index',
            'units.create',
            'users.index',
            'users.create',
            'variables.index',
            'variables.create',
            'vehicles.index',
            'vehicles.create',
            'warehouses.index',
            'warehouses.create',
        ];

        $failures = [];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get(route($route));

            if (! $response->isSuccessful() && ! $response->isRedirection()) {
                $failures[] = sprintf(
                    'Expected route [%s] to load or redirect successfully, got HTTP %d.',
                    $route,
                    $response->getStatusCode()
                );
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_verified_admin_can_open_vap_workflow_pages_without_server_errors(): void
    {
        $user = $this->verifiedAdmin();

        $routes = [
            'proficiency_tests.index',
            'proficiency_tests.create',
            'ratings.index',
            'report-studios.index',
            'responsibility-matrix.index',
            'supplier-assessments.index',
            'uncertainty-sources.index',
            'vap-maintenance.dashboard',
            'vap-maintenance.categories',
            'vap-maintenance.tasks',
            'vap-maintenance.tasks.create',
            'vap-inventory.analytics.index',
            'vap-inventory.items.index',
            'vap-inventory.items.create',
            'vap-inventory.items.calibration.schedule',
            'vap-inventory.items.reagents.expiry',
            'vap-inventory.needs.index',
            'vap-inventory.needs.create',
            'vap-inventory.orders.index',
            'vap-inventory.orders.create',
            'vap-inventory.reagents.consumption.index',
            'vap-inventory.reagents.consumption.create',
            'vap-inventory.reports.consumption',
            'vap-inventory.reports.inventory-value',
            'vap-inventory.reports.low-stock',
            'vap-inventory.reports.stock-movement',
            'vap-inventory.transfers.index',
            'vap-inventory.transfers.create',
            'vap_labels.label-templates.index',
            'vap_labels.label-templates.create',
            'vap_labels.labels.index',
            'vap_labels.labels.create',
            'vap-labs.labs.index',
            'vap-labs.labs.create',
            'vap_non_conformities.index',
            'vap_non_conformities.create',
            'vap-proposals.index',
            'vap-proposals.create',
            'vap-proposals.templates.index',
            'vap-proposals.templates.create',
            'vap_samples.dashboard',
            'vap_samples.index',
            'vap_samples.reports',
        ];

        $failures = [];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get(route($route));

            if (! $response->isSuccessful() && ! $response->isRedirection()) {
                $failures[] = sprintf(
                    'Expected route [%s] to load or redirect successfully, got HTTP %d.',
                    $route,
                    $response->getStatusCode()
                );
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_verified_admin_can_open_existing_record_edit_and_show_pages_without_server_errors(): void
    {
        $user = $this->verifiedAdmin();

        $checks = [
            ['model' => Analysis::class, 'routes' => ['analysis.edit']],
            ['model' => AnalysisCategory::class, 'routes' => ['analysiscategories.edit']],
            ['model' => CollectionCollaboration::class, 'routes' => ['collectioncollaborations.edit']],
            ['model' => CollectionEndResult::class, 'routes' => ['collectionendresults.edit']],
            ['model' => CollectionReason::class, 'routes' => ['collectionreasons.edit']],
            ['model' => ContactCategory::class, 'routes' => ['contactcategories.edit']],
            ['model' => ContractGuide::class, 'routes' => ['contractguides.edit']],
            ['model' => Country::class, 'routes' => ['countries.edit']],
            ['model' => CreditNote::class, 'routes' => ['creditnotes.edit']],
            ['model' => Currency::class, 'routes' => ['currencies.edit']],
            ['model' => Customer::class, 'routes' => ['customers.edit', 'customers.show']],
            ['model' => CustomerCategory::class, 'routes' => ['customercategories.edit']],
            ['model' => CustomerRequest::class, 'routes' => ['customerrequests.edit']],
            ['model' => CustomerRequestCategory::class, 'routes' => ['customerrequestcategories.edit']],
            ['model' => Department::class, 'routes' => ['departments.edit']],
            [
                'query' => fn () => CollectionProduct::query()
                    ->whereRelation('collection', 'collectionable_type', 'direct')
                    ->first(),
                'routes' => ['directcollections.edit', 'directcollections.show'],
            ],
            ['model' => DiscountCategory::class, 'routes' => ['discountcategories.edit']],
            ['model' => EquipmentCategory::class, 'routes' => ['equipmentcategories.edit']],
            ['model' => ExportCertificate::class, 'routes' => ['exportcertificates.edit', 'exportcertificates.show']],
            ['model' => FAQ::class, 'routes' => ['faqs.edit']],
            ['model' => FAQAnswer::class, 'routes' => ['faqanswers.edit']],
            ['model' => FAQCategory::class, 'routes' => ['faqcategories.edit']],
            ['model' => Formula::class, 'routes' => ['formulas.edit']],
            ['model' => ImportCertificate::class, 'routes' => ['importcertificates.edit', 'importcertificates.show']],
            ['model' => InventoryDelivery::class, 'routes' => ['ideliveries.edit']],
            ['model' => InventoryItem::class, 'routes' => ['iitems.edit', 'iitems.show', 'vap-inventory.items.edit', 'vap-inventory.items.show']],
            ['model' => InventoryItemLocation::class, 'routes' => ['ilocations.edit']],
            ['model' => InventoryItemSupplier::class, 'routes' => ['isuppliers.edit']],
            ['model' => InventoryItemTransfer::class, 'routes' => ['itransfers.edit', 'vap-inventory.transfers.show']],
            ['model' => InventoryItemType::class, 'routes' => ['itypes.edit']],
            ['model' => InventoryItemWarehouse::class, 'routes' => ['iwarehouses.edit']],
            ['model' => InventoryNeed::class, 'routes' => ['vap-inventory.needs.show']],
            ['model' => InventoryOrder::class, 'routes' => ['vap-inventory.orders.edit', 'vap-inventory.orders.show']],
            ['model' => InventoryUnit::class, 'routes' => ['iunits.edit']],
            ['model' => Invoice::class, 'routes' => ['invoices.edit', 'invoices.show']],
            ['model' => InvoiceCategory::class, 'routes' => ['invoicecategories.edit']],
            ['model' => ItemCategory::class, 'routes' => ['itemcategories.edit']],
            ['model' => ItemStatus::class, 'routes' => ['itemstatuses.edit']],
            ['model' => MaintenanceCategory::class, 'routes' => ['maintenancecategories.edit']],
            ['model' => MaintenanceTask::class, 'routes' => ['maintenancetasks.edit', 'maintenancetasks.show']],
            ['model' => Matrix::class, 'routes' => ['matrixes.edit', 'matrixes.show']],
            ['model' => NormativeWorkProcedure::class, 'routes' => ['nwps.edit']],
            ['model' => Occurrence::class, 'routes' => ['occurrences.edit', 'occurrences.show']],
            ['model' => OccurrenceCategory::class, 'routes' => ['occurrencecategories.edit']],
            ['model' => OccurrenceOrigin::class, 'routes' => ['occurrenceorigins.edit']],
            ['model' => OccurrenceStatus::class, 'routes' => ['occurrencestatuses.edit']],
            ['model' => PackagingCategory::class, 'routes' => ['packagingcategories.edit']],
            ['model' => PaidService::class, 'routes' => ['paidservices.edit']],
            ['model' => Parameter::class, 'routes' => ['parameters.edit']],
            ['model' => PaymentCategory::class, 'routes' => ['paymentcategories.edit']],
            ['model' => PhytosanitaryProduct::class, 'routes' => ['phytosanitary_products.edit']],
            ['model' => Product::class, 'routes' => ['products.edit']],
            ['model' => ProficiencyTest::class, 'routes' => ['proficiency_tests.edit', 'proficiency_tests.show']],
            ['model' => Profile::class, 'routes' => ['profiles.edit', 'profiles.show']],
            [
                'query' => fn () => CollectionProduct::query()
                    ->whereRelation('collection', 'collectionable_type', 'programmed')
                    ->first(),
                'routes' => ['programmedcollections.edit', 'programmedcollections.show'],
            ],
            ['model' => Protocol::class, 'routes' => ['protocols.edit']],
            ['model' => Quote::class, 'routes' => ['quotes.edit', 'quotes.show']],
            ['model' => ReagentConsumption::class, 'routes' => ['vap-inventory.reagents.consumption.show']],
            ['model' => Receipt::class, 'routes' => ['receipts.edit']],
            ['model' => ResultCategory::class, 'routes' => ['resultcategories.edit']],
            ['model' => Role::class, 'routes' => ['roles.edit']],
            ['model' => Standard::class, 'routes' => ['standards.edit']],
            ['model' => TaxExemption::class, 'routes' => ['taxexemptions.edit']],
            ['model' => TaxType::class, 'routes' => ['taxtypes.edit']],
            ['model' => Temperature::class, 'routes' => ['temperatures.edit']],
            ['model' => TransportCategory::class, 'routes' => ['transportcategories.edit']],
            ['model' => Unit::class, 'routes' => ['units.edit']],
            ['model' => User::class, 'routes' => ['users.edit']],
            ['model' => VAPLabel::class, 'routes' => ['vap_labels.labels.edit', 'vap_labels.labels.show']],
            ['model' => VAPLabelTemplate::class, 'routes' => ['vap_labels.label-templates.edit']],
            ['model' => VAPLab::class, 'routes' => ['vap-labs.labs.edit', 'vap-labs.labs.show']],
            ['model' => VAPNonConformity::class, 'routes' => ['vap_non_conformities.edit', 'vap_non_conformities.show']],
            ['model' => VAPProposal::class, 'routes' => ['vap-proposals.edit', 'vap-proposals.show']],
            ['model' => VAPProposalTemplate::class, 'routes' => ['vap-proposals.templates.edit', 'vap-proposals.templates.show']],
            ['model' => VAPSampleEntry::class, 'routes' => ['vap_samples.show']],
            ['model' => Variable::class, 'routes' => ['variables.edit']],
            ['model' => Vehicle::class, 'routes' => ['vehicles.edit']],
            ['model' => Warehouse::class, 'routes' => ['warehouses.edit', 'warehouses.show']],
        ];

        $failures = [];

        foreach ($checks as $check) {
            $record = isset($check['query'])
                ? $check['query']()
                : $check['model']::query()->first();

            if (! $record) {
                continue;
            }

            foreach ($check['routes'] as $route) {
                $response = $this->actingAs($user)->get(route($route, $record));

                if (! $response->isSuccessful() && ! $response->isRedirection()) {
                    $failures[] = sprintf(
                        'Expected route [%s] for [%s:%s] to load or redirect successfully, got HTTP %d.',
                        $route,
                        class_basename($record),
                        $record->getKey(),
                        $response->getStatusCode()
                    );
                }
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_verified_admin_can_open_iso_17025_audit_and_record_pages(): void
    {
        $user = $this->verifiedAdmin();
        $certificate = QualityCertificate::query()->first();
        $occurrence = Occurrence::query()->first();
        $vapNonConformity = VAPNonConformity::query()->first();
        $importCertificate = ImportCertificate::query()->first();
        $exportCertificate = ExportCertificate::query()->first();

        $this->assertNotNull($certificate, 'Expected at least one quality certificate for ISO smoke testing.');
        $this->assertNotNull($occurrence, 'Expected at least one occurrence for smoke testing.');

        $checks = [
            route('qualitycertificates.show', $certificate),
            route('qualitycertificates.iso-revisions.index', $certificate),
            route('qualitycertificates.iso-revisions.audit-trail', $certificate),
            route('qualitycertificates.getApprove', $certificate),
            route('occurrences.show', $occurrence),
            route('vap_samples.reports'),
            route('vap_samples.samples.stats'),
            route('vap_samples.discards.stats'),
            route('dashboard.export'),
        ];

        if ($vapNonConformity) {
            $checks[] = route('vap_non_conformities.show', $vapNonConformity);
        }

        if ($importCertificate) {
            $checks[] = route('importcertificates.show', $importCertificate);
        }

        if ($exportCertificate) {
            $checks[] = route('exportcertificates.show', $exportCertificate);
        }

        $failures = [];

        foreach ($checks as $url) {
            $response = $this->actingAs($user)->get($url);

            if (! $response->isSuccessful()) {
                $failures[] = sprintf(
                    'Expected [%s] to load successfully, got HTTP %d.',
                    $url,
                    $response->getStatusCode()
                );
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_verified_admin_can_open_recent_customer_settings_backup_and_inventory_analytics_pages(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->first();

        $this->assertNotNull($customer, 'Expected at least one customer for admin smoke testing.');

        $checks = [
            route('customers.show', $customer),
            route('generalsettings.index'),
            route('systembackups.backups'),
            route('systembackups.statuses'),
            route('systembackups.index'),
            route('environmental-conditions.index'),
            route('vap-inventory.items.index'),
            route('vap-inventory.analytics.index'),
            route('vap-inventory.analytics.data'),
        ];

        $failures = [];

        foreach ($checks as $url) {
            $response = $this->actingAs($user)->get($url);

            if (! $response->isSuccessful()) {
                $failures[] = sprintf(
                    'Expected [%s] to load successfully, got HTTP %d.',
                    $url,
                    $response->getStatusCode()
                );
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_verified_admin_can_fetch_operational_json_endpoints_without_server_errors(): void
    {
        $user = $this->verifiedAdmin();
        $batch = InventoryBatch::query()->first();
        $item = InventoryItem::query()->first();
        $warehouse = InventoryItemWarehouse::query()->first();

        $checks = [
            route('tags.index'),
            route('workflow.tasks.index'),
            route('files.list'),
            route('files.breadcrumbs'),
            route('files.folders-list'),
            route('files.search', ['query' => '']),
            route('files.search', ['query' => 'smoke']),
            route('vap-inventory.analytics.data'),
            route('vap-inventory.analytics.realtime'),
            route('vap-inventory.analytics.summary'),
            route('vap-inventory.reports.dashboard-stats'),
            route('vap-inventory.transfers.all-stock-info'),
            route('vap_labels.templates.list'),
            route('vap_samples.samples.stats'),
            route('vap_samples.discards.stats'),
            route('vap_samples.discards.recent'),
            route('systemactivity.stats'),
            route('systemactivity.cleanup.recommendations'),
        ];

        if ($batch) {
            $checks[] = route('inventory.batches.lookup', $batch);
        }

        if ($item && $warehouse) {
            $checks[] = route('vap-inventory.transfers.item-stock', [
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
            ]);

            $checks[] = route('vap-inventory.transfers.item-stock-all', [
                'item_id' => $item->id,
            ]);
        }

        $failures = [];

        foreach ($checks as $url) {
            $response = $this->actingAs($user)->getJson($url);

            if ($response->getStatusCode() >= 500) {
                $failures[] = sprintf(
                    'Expected JSON endpoint [%s] not to return a server error, got HTTP %d.',
                    $url,
                    $response->getStatusCode()
                );

                continue;
            }

            if ($response->isSuccessful()) {
                $decoded = json_decode($response->getContent(), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $failures[] = sprintf(
                        'Expected successful endpoint [%s] to return valid JSON.',
                        $url
                    );
                }

                $this->assertIsArray($decoded);
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_vap_samples_and_labs_breadcrumbs_use_user_facing_labels(): void
    {
        $user = $this->verifiedAdmin();
        $sampleEntry = VAPSampleEntry::query()->first();
        $lab = VAPLab::query()->first();

        $this->assertNotNull($sampleEntry, 'Expected at least one VAP sample entry for breadcrumb smoke testing.');
        $this->assertNotNull($lab, 'Expected at least one VAP lab for breadcrumb smoke testing.');

        $sampleIndex = $this->actingAs($user)->get(route('vap_samples.index'));
        $sampleIndex->assertOk();
        $this->assertSame('Sample Entry', data_get($sampleIndex->viewData('page'), 'props.breadcrumbs.0.title'));
        $this->assertTrue(data_get($sampleIndex->viewData('page'), 'props.breadcrumbs.0.current'));

        $sampleShow = $this->actingAs($user)->get(route('vap_samples.show', $sampleEntry));
        $sampleShow->assertOk();
        $this->assertSame('Sample Entry', data_get($sampleShow->viewData('page'), 'props.breadcrumbs.0.title'));
        $this->assertSame('Show', data_get($sampleShow->viewData('page'), 'props.breadcrumbs.1.title'));
        $this->assertTrue(data_get($sampleShow->viewData('page'), 'props.breadcrumbs.1.current'));

        $labIndex = $this->actingAs($user)->get(route('vap-labs.labs.index'));
        $labIndex->assertOk();
        $this->assertSame(trans('gestlab.general.labels.vap_labs.title'), data_get($labIndex->viewData('page'), 'props.breadcrumbs.0.title'));
        $this->assertTrue(data_get($labIndex->viewData('page'), 'props.breadcrumbs.0.current'));

        $labShow = $this->actingAs($user)->get(route('vap-labs.labs.show', $lab));
        $labShow->assertOk();
        $this->assertSame(trans('gestlab.general.labels.vap_labs.title'), data_get($labShow->viewData('page'), 'props.breadcrumbs.0.title'));
        $this->assertSame(trans('gestlab.general.labels.vap_labs.buttons.view_details'), data_get($labShow->viewData('page'), 'props.breadcrumbs.1.title'));
        $this->assertTrue(data_get($labShow->viewData('page'), 'props.breadcrumbs.1.current'));
    }

    public function test_verified_user_can_persist_theme_from_web_session(): void
    {
        $user = $this->verifiedAdmin();
        $originalTheme = $user->theme;

        try {
            $this->actingAs($user)
                ->patchJson(route('user.theme.update'), ['theme' => 'dark'])
                ->assertOk()
                ->assertJson(['theme' => 'dark']);

            $this->assertSame('dark', $user->refresh()->theme);
        } finally {
            $user->forceFill(['theme' => $originalTheme])->save();
        }
    }

    public function test_proposal_qr_accessor_returns_svg_data_uri(): void
    {
        $proposal = Proposal::query()->first();

        $this->assertNotNull($proposal, 'Expected at least one proposal for QR smoke testing.');
        $this->assertStringStartsWith('data:image/svg+xml', $proposal->qr);
    }
}
