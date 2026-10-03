<?php

namespace App\Actions;

use App\Models\ExportCertificate;
use App\Models\ImportCertificate;
use App\Models\Invoice;
use App\Models\InvoiceCategory;
use App\Models\InvoiceItem;
use App\Models\Quote;
use App\Services\FinancialDocumentAssembly;
use App\Services\IssuedFinancialDocumentIntegrity;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class IssueBillingSourceInvoice
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /** @param array<string, mixed> $attributes
     * @param  list<array<string, mixed>>  $items
     */
    public function execute(int $userId, int $labId, string $sourceType, int $sourceId, array $attributes, array $items = []): Invoice
    {
        [$class, $permission, $customerColumn, $siteColumn, $issuedColumn] = match ($sourceType) {
            'quote' => [Quote::class, 'view_quotes', 'customer_id', 'warehouse_id', 'converted_to_invoice'],
            'import_certificate' => [ImportCertificate::class, 'view_import_certificates', 'importer_id', 'importer_warehouse_id', 'invoiced'],
            'export_certificate' => [ExportCertificate::class, 'view_export_certificates', 'exporter_id', 'exporter_warehouse_id', 'invoiced'],
            default => throw new LogicException('Unsupported billing source.'),
        };

        return DB::transaction(function () use ($userId, $labId, $sourceType, $sourceId, $attributes, $items, $class, $permission, $customerColumn, $siteColumn, $issuedColumn): Invoice {
            $operator = $this->access->operator($userId, $labId, 'add_invoices');
            abort_unless($operator->can($permission), 403);
            $source = $class::query()->where('lab_id', $labId)->lockForUpdate()->findOrFail($sourceId);
            abort_if($source->getAttribute($issuedColumn) || $source->invoice_id !== null, 409, 'Este documento já foi facturado.');
            $sourceBefore = Arr::except($source->getAttributes(), ['updated_at']);
            $sourceItems = $this->lines($source);

            if ($source instanceof Quote) {
                $quoteItems = $source->items()->orderBy('id')->lockForUpdate()->get();
                foreach ($quoteItems as $item) {
                    $item->assertOperationalSourceOwnership();
                }
                $items = $quoteItems->toArray();
                $attributes = [
                    ...Arr::only($source->getAttributes(), ['description', 'internal_ref', 'discount', 'tax', 'sub_total', 'total', 'obs', 'use_matrix_price', 'is_service', 'discount_type']),
                    'extra_data' => $source->extra_data,
                    'type_id' => $attributes['type_id'],
                ];
            } elseif ((int) ($attributes['customer_id'] ?? 0) !== (int) $source->getAttribute($customerColumn)
                || (int) ($attributes['warehouse_id'] ?? 0) !== (int) $source->getAttribute($siteColumn)) {
                throw ValidationException::withMessages(['customer_id' => 'O cliente e o local devem corresponder ao certificado.']);
            }
            if ($items === []) {
                throw ValidationException::withMessages(['items' => 'É necessário pelo menos um item para emitir a factura.']);
            }

            $invoice = new Invoice([
                ...Arr::only($attributes, ['type_id', 'discount_type', 'description', 'internal_ref', 'discount', 'tax', 'sub_total', 'total', 'obs', 'use_matrix_price', 'is_service', 'extra_data', 'due_date']),
                'customer_id' => $source->getAttribute($customerColumn),
                'warehouse_id' => $source->getAttribute($siteColumn),
                'user_id' => $userId,
                'invoice_month' => now()->format('Y'),
                'date' => now()->toDateString(),
                'amount_due' => $attributes['total'],
                'status_code' => Invoice::STATUS_CODE_NORMAL,
                'status' => false,
                'is_original' => true,
                'exported_saft' => false,
                'invoiceable_type' => $sourceType,
                'invoiceable_id' => $source->id,
            ]);
            $invoice->lab_id = $labId;
            $intendedInvoice = clone $invoice;
            $category = InvoiceCategory::query()->findOrFail($invoice->type_id);
            if ($category->code === 'FR') {
                $intendedInvoice->status = true;
                $intendedInvoice->paid_date = now()->toDateString();
            }
            if (! $invoice->save()) {
                throw new LogicException('Invoice issuance was not persisted.');
            }
            $persistedInvoice = $invoice->newQueryWithoutScopes()->findOrFail($invoice->id);
            if (! $persistedInvoice->unique_hash || $persistedInvoice->unique_hash !== $invoice->unique_hash) {
                throw new LogicException('The issued invoice signature was not persisted.');
            }
            app(IssuedFinancialDocumentIntegrity::class)->assertCreationIntent($intendedInvoice, $persistedInvoice,
                ['discount', 'tax', 'sub_total', 'total', 'amount_due']);
            if ((int) $persistedInvoice->seq < 1 || $persistedInvoice->inv_no !== $category->code.' '.$intendedInvoice->invoice_month.'/'.$persistedInvoice->seq) {
                throw new LogicException('Invoice issued identity changed during creation.');
            }
            $invoiceBefore = Arr::except($persistedInvoice->getAttributes(), ['updated_at']);
            $issuedLines = [];
            foreach ($items as $item) {
                if (! ($source instanceof Quote)) {
                    unset($item['itemable_id'], $item['itemable_type'], $item['extra_data']);
                }
                $line = new InvoiceItem(Arr::only($item, ['item_id', 'item_description', 'exemption_id', 'exemption_code', 'discount_id', 'unit_id', 'tax_id', 'qty', 'unit_price', 'total', 'charge_tax', 'tax_amount', 'tax_percentage', 'discount_amount', 'discount_percentage', 'obs', 'itemable_id', 'itemable_type', 'extra_data']));
                $line->invoice_id = $invoice->id;
                $line->lab_id = $labId;
                $intendedLine = clone $line;
                if (! app(FinancialDocumentAssembly::class)->withLines($invoice, fn (): bool => $line->save())) {
                    throw new LogicException('Invoice line issuance was not persisted.');
                }
                $persistedLine = $line->fresh();
                app(IssuedFinancialDocumentIntegrity::class)->assertCreationIntent($intendedLine, $persistedLine,
                    ['qty', 'unit_price', 'total', 'tax_amount', 'tax_percentage', 'discount_amount', 'discount_percentage']);
                $issuedLines[] = $persistedLine->getAttributes();
            }
            $source->invoice_id = $invoice->id;
            $source->setAttribute($issuedColumn, true);
            if (! $source->save()) {
                throw new LogicException('Billing source issuance was not persisted.');
            }
            $persistedSource = $source->newQueryWithoutScopes()->findOrFail($source->id);
            if ((int) $persistedSource->lab_id !== $labId
                || (int) $persistedSource->getAttribute($customerColumn) !== (int) $invoice->customer_id
                || (int) $persistedSource->getAttribute($siteColumn) !== (int) $invoice->warehouse_id
                || (int) $persistedSource->invoice_id !== (int) $invoice->id
                || ! $persistedSource->getAttribute($issuedColumn) || $persistedSource->trashed()) {
                throw new LogicException('The billing source changed during issuance.');
            }
            $audit = activity()->causedBy($operator)->performedOn($source)->event('issued')
                ->withProperties(['lab_id' => $labId, 'invoice_id' => $invoice->id])->log('Emitiu a factura do documento de origem.');
            if (! $audit || ! $audit->newQueryWithoutScopes()->whereKey($audit->id)->exists()) {
                throw new LogicException('Invoice issuance audit was not persisted.');
            }
            $finalInvoice = $invoice->newQueryWithoutScopes()->findOrFail($invoice->id);
            $finalSource = $source->newQueryWithoutScopes()->findOrFail($source->id);
            $finalAudit = $audit->newQueryWithoutScopes()->find($audit->id);
            $sourceBefore['invoice_id'] = $invoice->id;
            $sourceBefore[$issuedColumn] = true;
            if (Arr::except($finalInvoice->getAttributes(), ['updated_at']) !== $invoiceBefore
                || $this->lines($finalInvoice) !== $issuedLines
                || Arr::except($finalSource->getAttributes(), ['updated_at']) !== $sourceBefore
                || $this->lines($finalSource) !== $sourceItems
                || ! $finalAudit || $finalAudit->subject_type !== $source->getMorphClass()
                || (int) $finalAudit->subject_id !== (int) $source->id
                || $finalAudit->causer_type !== $operator->getMorphClass()
                || (int) $finalAudit->causer_id !== $userId || $finalAudit->event !== 'issued'
                || (int) $finalAudit->properties->get('lab_id') !== $labId
                || (int) $finalAudit->properties->get('invoice_id') !== (int) $invoice->id) {
                throw new LogicException('Invoice, issued lines, source or audit changed during issuance.');
            }
            $operator = $this->access->operator($userId, $labId, 'add_invoices');
            abort_unless($operator->can($permission), 403);

            return $invoice->refresh();
        }, 3);
    }

    /** @return list<array<string, mixed>> */
    private function lines(Model $document): array
    {
        return $document->items()->withoutGlobalScope('financial_laboratory')->withTrashed()->orderBy('id')->lockForUpdate()->get()
            ->map(fn (Model $line): array => $line->getAttributes())->all();
    }
}
