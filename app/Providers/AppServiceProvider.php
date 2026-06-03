<?php

namespace App\Providers;

use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\Complaint;
use App\Models\CounterAnalysis;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\CustomerRequest;
use App\Models\Department;
use App\Models\DirectCollection;
use App\Models\InventoryItem;
use App\Models\InventoryOrder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReceipt;
use App\Models\LabCode;
use App\Models\MaintenanceTask;
use App\Models\ManagementReview;
use App\Models\Matrix;
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
use App\Models\VAPNonConformity;
use App\Models\VAPProposal;
use App\Models\VAPProposalItem;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Vite as FoundationVite;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('local')) {
            $this->flushCachedViteManifest();
        }

        Relation::enforceMorphMap([
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
            'credit_note' => CreditNote::class,
            'credit_note_item' => CreditNoteItem::class,
            'receipt' => Receipt::class,
            'proposal' => VAPProposal::class,
            'proposal_item' => VAPProposalItem::class,
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
            'packaging_category' => PackagingCategory::class,
            'inventoryitem' => InventoryItem::class,
            'vap_file' => VAPFile::class,
            'vap_non_conformity' => VAPNonConformity::class,
            'reagent_consumption' => ReagentConsumption::class,
            'quality_certificate_revision' => QualityCertificateRevision::class,
            'paid_service' => PaidService::class,
        ]);

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
