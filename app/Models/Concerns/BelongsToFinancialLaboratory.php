<?php

namespace App\Models\Concerns;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\ExportCertificate;
use App\Models\ExportCertificateItem;
use App\Models\ImportCertificate;
use App\Models\ImportCertificateItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReceipt;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Receipt;
use App\Models\VAPLab;
use App\Services\FinancialDocumentAssembly;
use App\Services\IssuedFinancialDocumentIntegrity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Staff HTTP queries use the directly joined active laboratory. Portal reads
 * retain separate customer/site authorization; console callers must provide
 * an explicit owner. Database composite keys also constrain children.
 */
trait BelongsToFinancialLaboratory
{
    public static function bootBelongsToFinancialLaboratory(): void
    {
        static::addGlobalScope('financial_laboratory', function (Builder $query): void {
            if (request()->attributes->has('proposal_laboratory_id')) {
                $query->where($query->qualifyColumn('lab_id'), request()->attributes->get('proposal_laboratory_id'));
            }
        });

        static::creating(function (Model $record): void {
            if (request()->attributes->has('proposal_laboratory_id')) {
                $labId = (int) request()->attributes->get('proposal_laboratory_id');
                abort_unless($labId > 0, 403);
                $record->lab_id = $labId;
            }
            $parents = match (true) {
                $record instanceof InvoiceItem => ['invoice_id' => Invoice::class],
                $record instanceof QuoteItem => ['quote_id' => Quote::class],
                $record instanceof ImportCertificateItem => ['certificate_id' => ImportCertificate::class],
                $record instanceof ExportCertificateItem => ['certificate_id' => ExportCertificate::class],
                $record instanceof CreditNoteItem => ['note_id' => CreditNote::class],
                $record instanceof InvoiceReceipt => ['invoice_id' => Invoice::class, 'receipt_id' => Receipt::class],
                $record instanceof CreditNote, $record instanceof Receipt => $record->invoice_id ? ['invoice_id' => Invoice::class] : [],
                default => [],
            };
            foreach ($parents as $column => $class) {
                $parent = $class::query()->findOrFail($record->getAttribute($column));
                $record->lab_id ??= $parent->lab_id;
                if ((int) $parent->lab_id !== (int) $record->lab_id) {
                    throw ValidationException::withMessages([$column => 'O documento não pertence a este laboratório.']);
                }
                if ($record instanceof Receipt || $record instanceof CreditNote) {
                    if ((int) $record->customer_id !== (int) $parent->customer_id
                        || (int) $record->warehouse_id !== (int) $parent->warehouse_id) {
                        throw ValidationException::withMessages([$column => 'O cliente e o local devem corresponder ao documento.']);
                    }
                }
            }
            if (! $record->lab_id || ! VAPLab::query()->whereKey($record->lab_id)->exists()) {
                throw ValidationException::withMessages(['lab_id' => 'É obrigatório indicar o laboratório proprietário.']);
            }
            self::validateBillingSourceInvoice($record);
            if ($record instanceof QuoteItem) {
                $record->assertOperationalSourceOwnership();
            }
            app(IssuedFinancialDocumentIntegrity::class)->assertCreatingLine($record);
        });

        static::updating(function (Model $record): void {
            $identity = match (true) {
                $record instanceof InvoiceItem => ['lab_id', 'invoice_id'],
                $record instanceof QuoteItem => ['lab_id', 'quote_id'],
                $record instanceof ImportCertificateItem, $record instanceof ExportCertificateItem => ['lab_id', 'certificate_id'],
                $record instanceof CreditNoteItem => ['lab_id', 'note_id'],
                $record instanceof InvoiceReceipt => ['lab_id', 'invoice_id', 'receipt_id'],
                $record instanceof Receipt, $record instanceof CreditNote => ['lab_id', 'invoice_id'],
                default => ['lab_id'],
            };
            if ($record->isDirty($identity)) {
                throw new LogicException('Financial document ownership and parent identity cannot be reassigned.');
            }
            app(IssuedFinancialDocumentIntegrity::class)->assertUpdating($record);
            if ($record instanceof ImportCertificate || $record instanceof ExportCertificate) {
                if ($record->getOriginal('invoice_id') !== null && $record->isDirty('invoice_id')) {
                    throw new LogicException('An issued certificate invoice link cannot be cleared or reassigned.');
                }
                if ($record->getOriginal('invoiced') && $record->isDirty('invoiced') && ! $record->invoiced) {
                    throw new LogicException('An issued certificate cannot be manually marked as unbilled.');
                }
            }
            self::validateBillingSourceInvoice($record);
            if ($record instanceof QuoteItem) {
                $record->assertOperationalSourceOwnership();
            }
        });
        static::created(function (Model $record): void {
            if ($record instanceof Invoice || $record instanceof CreditNote) {
                app(FinancialDocumentAssembly::class)->rememberCreation($record);
            }
        });
        static::deleting(function (Model $record): void {
            app(IssuedFinancialDocumentIntegrity::class)->assertDeletingLine($record);
        });
    }

    private static function validateBillingSourceInvoice(Model $record): void
    {
        if (! ($record instanceof Quote || $record instanceof ImportCertificate || $record instanceof ExportCertificate)) {
            return;
        }
        if ($record->invoice_id === null) {
            return;
        }
        $invoice = Invoice::query()
            ->when($record->exists && ! $record->isDirty('invoice_id'), fn (Builder $query): Builder => $query->withTrashed())
            ->findOrFail($record->invoice_id);
        [$customer, $site] = match (true) {
            $record instanceof ImportCertificate => ['importer_id', 'importer_warehouse_id'],
            $record instanceof ExportCertificate => ['exporter_id', 'exporter_warehouse_id'],
            default => ['customer_id', 'warehouse_id'],
        };
        if ((int) $invoice->lab_id !== (int) $record->lab_id
            || (int) $invoice->customer_id !== (int) $record->getAttribute($customer)
            || (int) $invoice->warehouse_id !== (int) $record->getAttribute($site)) {
            throw ValidationException::withMessages(['invoice_id' => 'A factura deve pertencer ao mesmo laboratório, cliente e local.']);
        }
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }
}
