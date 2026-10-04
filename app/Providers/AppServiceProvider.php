<?php

namespace App\Providers;

use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\Complaint;
use App\Models\ControlChart;
use App\Models\CounterAnalysis;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\CustomerRequest;
use App\Models\Department;
use App\Models\DirectCollection;
use App\Models\ExportCertificate;
use App\Models\ExportCertificateItem;
use App\Models\ImportCertificate;
use App\Models\ImportCertificateItem;
use App\Models\IntegrationConnector;
use App\Models\IntegrationDelivery;
use App\Models\IntegrationMapping;
use App\Models\IntegrationTransmission;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryOrder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReceipt;
use App\Models\LabCode;
use App\Models\MaintenanceTask;
use App\Models\ManagementReview;
use App\Models\Matrix;
use App\Models\Occurrence;
use App\Models\PackagingCategory;
use App\Models\PaidService;
use App\Models\Parameter;
use App\Models\Profile;
use App\Models\ProgrammedCollection;
use App\Models\QualityCertificate;
use App\Models\QualityCertificateRevision;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\ReagentConsumption;
use App\Models\Receipt;
use App\Models\Result;
use App\Models\Sample;
use App\Models\User;
use App\Models\VAPFile;
use App\Models\VAPLab;
use App\Models\VAPNonConformity;
use App\Models\VAPProposal;
use App\Models\VAPProposalItem;
use App\Models\VAPProposalTemplate;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Models\Worksheet;
use App\Observers\OperationalModelObserver;
use App\Services\FinancialDocumentAssembly;
use App\Services\ProposalActivityAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Vite as FoundationVite;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(FinancialDocumentAssembly::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Activity::addGlobalScope('proposal_laboratory', function (Builder $query): void {
            ProposalActivityAccess::constrain($query->getQuery());
        });
        $activityModel = config('activitylog.activity_model', Activity::class);
        if ($activityModel !== Activity::class) {
            $activityModel::addGlobalScope('financial_laboratory', function (Builder $query): void {
                ProposalActivityAccess::constrainStaffAccounts($query->getQuery());
                ProposalActivityAccess::constrainMemberships($query->getQuery());
                ProposalActivityAccess::constrainFinancialDocuments($query->getQuery());
                ProposalActivityAccess::constrainInventoryWarehouses($query->getQuery());
                ProposalActivityAccess::constrainInventoryItems($query->getQuery());
            });
        }

        if ($this->app->environment('local')) {
            $this->flushCachedViteManifest();
        }

        Relation::enforceMorphMap([
            'laboratory' => VAPLab::class,
            'programmed' => ProgrammedCollection::class,
            'direct' => DirectCollection::class,
            'analysis' => Analysis::class,
            'user' => User::class,
            'counteranalysis' => CounterAnalysis::class,
            'collectionproduct' => CollectionProduct::class,
            'complaint' => Complaint::class,
            'labcode' => LabCode::class,
            'sample' => Sample::class,
            'department' => Department::class,
            'profile' => Profile::class,
            'result' => Result::class,
            'invoice' => Invoice::class,
            'invoice_item' => InvoiceItem::class,
            'quote' => Quote::class,
            'quote_item' => QuoteItem::class,
            'import_certificate' => ImportCertificate::class,
            'import_certificate_item' => ImportCertificateItem::class,
            'export_certificate' => ExportCertificate::class,
            'export_certificate_item' => ExportCertificateItem::class,
            'credit_note' => CreditNote::class,
            'credit_note_item' => CreditNoteItem::class,
            'receipt' => Receipt::class,
            'proposal' => VAPProposal::class,
            'proposal_item' => VAPProposalItem::class,
            'proposal_template' => VAPProposalTemplate::class,
            'receipt_item' => InvoiceReceipt::class,
            'quality_certificate' => QualityCertificate::class,
            'parameter' => Parameter::class,
            'warehouse' => Warehouse::class,
            'matrix' => Matrix::class,
            'order' => InventoryOrder::class,
            'customer_request' => CustomerRequest::class,
            'sample_entry' => VAPSampleEntry::class,
            'maintenance_task' => MaintenanceTask::class,
            'management_review' => ManagementReview::class,
            'occurrence' => Occurrence::class,
            'packaging_category' => PackagingCategory::class,
            'inventoryitem' => InventoryItem::class,
            'inventory_warehouse' => InventoryItemWarehouse::class,
            'integration_connector' => IntegrationConnector::class,
            'integration_delivery' => IntegrationDelivery::class,
            'integration_mapping' => IntegrationMapping::class,
            'integration_transmission' => IntegrationTransmission::class,
            'vap_file' => VAPFile::class,
            'vap_non_conformity' => VAPNonConformity::class,
            'control_chart' => ControlChart::class,
            'reagent_consumption' => ReagentConsumption::class,
            'quality_certificate_revision' => QualityCertificateRevision::class,
            'paid_service' => PaidService::class,
            'worksheet' => Worksheet::class,
        ]);

        foreach ([Invoice::class, Quote::class, CreditNote::class, Receipt::class, ImportCertificate::class, ExportCertificate::class, QualityCertificate::class] as $model) {
            $model::observe(OperationalModelObserver::class);
        }

        Vite::prefetch(3);
    }

    protected function flushCachedViteManifest(): void
    {
        static $reflection = null;

        if ($reflection === null) {
            $reflection = new ReflectionClass(FoundationVite::class);
        }

        $property = $reflection->getProperty('manifests');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }
}
