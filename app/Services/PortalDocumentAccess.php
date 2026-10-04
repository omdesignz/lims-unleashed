<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\QualityCertificate;
use App\Models\Quote;
use App\Models\Receipt;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

class PortalDocumentAccess
{
    /**
     * Portal ownership is the current customer/site pair, not the staff session's active lab.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @return Builder<TModel>
     */
    public function query(string $model, Warehouse $site): Builder
    {
        $site = Warehouse::query()->whereHas('customer')->findOrFail($site->id);
        $query = $model::query()->withoutGlobalScope('financial_laboratory');

        $query->where($query->qualifyColumn('customer_id'), $site->customer_id)
            ->where($query->qualifyColumn('warehouse_id'), $site->id)
            ->whereNull($query->qualifyColumn('deleted_at'));
        if (in_array($model, [CreditNote::class, Quote::class, Receipt::class], true)) {
            $query->where(fn (Builder $document): Builder => $document
                ->whereNull($query->qualifyColumn('invoice_id'))
                ->orWhereHas('invoice', fn (Builder $invoice): Builder => $this->matchingInvoice($invoice, $query)));
        }
        if ($model === Receipt::class) {
            $query->whereDoesntHave('items', fn (Builder $item): Builder => $item
                ->withoutGlobalScope('financial_laboratory')
                ->where(fn (Builder $invalid): Builder => $invalid
                    ->whereNull('invoice_receipt.lab_id')
                    ->orWhereColumn('invoice_receipt.lab_id', '!=', 'receipts.lab_id')
                    ->orWhereDoesntHave('invoice', fn (Builder $invoice): Builder => $this->matchingInvoice($invoice, $query))))
                ->withSum('items as portal_total', 'paid_amount');
        }

        return $query;
    }

    private function matchingInvoice(Builder $invoice, Builder $document): Builder
    {
        return $invoice->withoutGlobalScope('financial_laboratory')->withTrashed()
            ->whereColumn('invoices.lab_id', $document->qualifyColumn('lab_id'))
            ->whereColumn('invoices.customer_id', $document->qualifyColumn('customer_id'))
            ->whereColumn('invoices.warehouse_id', $document->qualifyColumn('warehouse_id'));
    }

    /** @return Builder<QualityCertificate> */
    public function releasedCertificates(Warehouse $site): Builder
    {
        return $this->query(QualityCertificate::class, $site)
            ->whereNotNull('validated_at')
            ->whereHas('lab_code', fn (Builder $code): Builder => $code
                ->whereColumn('lab_codes.collection_id', 'quality_certificates.collection_id'))
            ->whereHas('collection', fn (Builder $collection): Builder => $collection
                ->whereColumn('collection_product.customer_id', 'quality_certificates.customer_id')
                ->whereColumn('collection_product.warehouse_id', 'quality_certificates.warehouse_id')
                ->whereHas('invoice', fn (Builder $invoice): Builder => $invoice
                    ->withoutGlobalScope('financial_laboratory')
                    ->where('status', true)
                    ->whereColumn('invoices.customer_id', 'quality_certificates.customer_id')
                    ->whereColumn('invoices.warehouse_id', 'quality_certificates.warehouse_id')
                    ->whereHas('lab')
                    ->whereExists(fn (QueryBuilder $entry): QueryBuilder => $entry->selectRaw('1')->from('sample_entries')
                        ->whereColumn('sample_entries.collection_product_id', 'collection_product.id')
                        ->whereColumn('sample_entries.lab_id', 'invoices.lab_id')
                        ->whereColumn('sample_entries.customer_id', 'quality_certificates.customer_id')
                        ->whereColumn('sample_entries.warehouse_id', 'quality_certificates.warehouse_id')
                        ->whereNull('sample_entries.deleted_at'))
                    ->whereNotExists(fn (QueryBuilder $entry): QueryBuilder => $entry->selectRaw('1')->from('sample_entries')
                        ->whereColumn('sample_entries.collection_product_id', 'collection_product.id')
                        ->where(fn (QueryBuilder $conflict): QueryBuilder => $conflict
                            ->whereNull('sample_entries.lab_id')
                            ->orWhereColumn('sample_entries.lab_id', '!=', 'invoices.lab_id')))));
    }
}
