<?php

namespace App\Actions;

use App\Models\CollectionProduct;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Unit;
use App\Services\IssuedFinancialDocumentIntegrity;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class UpdateQuoteItem
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly IssuedFinancialDocumentIntegrity $integrity,
        private readonly SignQuoteRevision $revisions) {}

    /** @param array<string, mixed> $attributes */
    public function execute(int $userId, int $labId, int $itemId, array $attributes): void
    {
        DB::transaction(function () use ($userId, $labId, $itemId, $attributes): void {
            $operator = $this->access->operator($userId, $labId, 'edit_quotes');
            $item = QuoteItem::query()->where('lab_id', $labId)->findOrFail($itemId);
            $quote = Quote::query()->where('lab_id', $labId)->lockForUpdate()->findOrFail($item->quote_id);
            $rootBefore = Arr::except($quote->getAttributes(), ['updated_at']);
            $retainedHistory = $this->revisions->history($quote, true);
            $retainedLines = $quote->items()->withTrashed()->orderBy('id')->lockForUpdate()->get();
            $lineEvidence = $retainedLines->mapWithKeys(fn (Model $line): array => [$line->id => $line->getAttributes()])->all();
            $previousSnapshot = $this->revisions->snapshot($quote, $retainedLines);
            $item = QuoteItem::query()->where('lab_id', $labId)->lockForUpdate()->findOrFail($itemId);
            $selectedUnitId = $attributes['unit_id'] ?? null;
            if ($selectedUnitId !== null && ! Unit::query()->whereKey($selectedUnitId)->sharedLock()->first()) {
                throw ValidationException::withMessages(['unit_id' => 'A unidade seleccionada já não está disponível.']);
            }
            $previousSourceId = $item->itemable_type === 'collectionproduct' ? $item->itemable_id : null;
            if (array_key_exists('lab_code_id', $attributes)) {
                $code = $attributes['lab_code_id'] === null ? null : $this->ownership->labCodesForLaboratory($labId)
                    ->whereHas('collection', fn (Builder $query): Builder => $query->where('customer_id', $quote->customer_id)->where('warehouse_id', $quote->warehouse_id))
                    ->lockForUpdate()->findOrFail($attributes['lab_code_id']);
                $attributes['itemable_id'] = $code?->collection_id;
                $attributes['itemable_type'] = $code ? 'collectionproduct' : null;
            }
            $nextSourceId = $attributes['itemable_id'] ?? (array_key_exists('itemable_id', $attributes) ? null : $previousSourceId);
            $sources = $this->ownership->collectionProductsForLaboratory($labId)
                ->whereKey(array_filter([$previousSourceId, $nextSourceId]))->orderBy('id')->lockForUpdate()->get();
            if ($nextSourceId !== null) {
                $source = $sources->firstWhere('id', $nextSourceId);
                if (! $source || $source->invoiced || $source->invoice_id !== null || ($source->quote_id !== null && (int) $source->quote_id !== (int) $quote->id)) {
                    throw ValidationException::withMessages(['lab_code_id' => 'A origem já foi reservada ou facturada.']);
                }
            }
            $item->fill(Arr::only($attributes, ['obs', 'unit_id', 'itemable_id', 'itemable_type']));
            $intendedItem = clone $item;
            unset($intendedItem->updated_at);
            if (! $item->save()) {
                throw new LogicException('Quote item correction was not persisted.');
            }
            $this->integrity->assertCreationIntent($intendedItem, $item->fresh());
            $lineBefore = $item->fresh()->getAttributes();
            $lineEvidence[$item->id] = $lineBefore;
            $sourceEvidence = [];
            foreach ($sources as $source) {
                if ((int) $source->id === (int) $nextSourceId) {
                    $source->forceFill(['quote_id' => $quote->id, 'quoted' => true]);
                } elseif ((int) $source->quote_id === (int) $quote->id
                    && ! $quote->items()->where('itemable_type', 'collectionproduct')->where('itemable_id', $source->id)->exists()) {
                    $source->forceFill(['quote_id' => null, 'quoted' => false]);
                }
                $intendedSource = clone $source;
                unset($intendedSource->updated_at);
                if (! $source->save()) {
                    throw new LogicException('Quote source correction was not persisted.');
                }
                $this->integrity->assertCreationIntent($intendedSource, $source->fresh());
                $sourceEvidence[$source->id] = $source->fresh()->getAttributes();
            }
            if (Arr::except($quote->fresh()->getAttributes(), ['updated_at']) !== $rootBefore) {
                throw new LogicException('Quote content changed before line revision signing.');
            }
            $quote->refresh();
            $signatureEvidence = $this->revisions->execute($quote, $operator, $previousSnapshot, $retainedHistory);
            $rootBefore['unique_hash'] = $quote->unique_hash;
            $audit = activity()->causedBy($operator)->performedOn($item)->event('updated')
                ->withProperties(['lab_id' => $labId])->log('Corrigiu os metadados de um item da cotação.');
            $audit = $audit?->newQueryWithoutScopes()->find($audit->id);
            if (! $audit || $audit->event !== 'updated' || $audit->subject_type !== $item->getMorphClass() || (int) $audit->subject_id !== (int) $itemId
                || $audit->causer_type !== $operator->getMorphClass() || (int) $audit->causer_id !== (int) $userId || $audit->properties->all() !== ['lab_id' => $labId]
                || Arr::except($quote->newQueryWithoutScopes()->findOrFail($quote->id)->getAttributes(), ['updated_at']) !== $rootBefore
                || $item->newQueryWithoutScopes()->findOrFail($itemId)->getAttributes() !== $lineBefore
                || $quote->items()->withoutGlobalScope('financial_laboratory')->withTrashed()->orderBy('id')->get()->mapWithKeys(fn (Model $line): array => [$line->id => $line->getAttributes()])->all() !== $lineEvidence
                || $this->revisions->history($quote) !== $signatureEvidence
                || CollectionProduct::withoutGlobalScopes()->whereKey(array_keys($sourceEvidence))->orderBy('id')->get()->mapWithKeys(fn (Model $source): array => [$source->id => $source->getAttributes()])->all() !== $sourceEvidence) {
                throw new LogicException('Quote item correction audit was not persisted.');
            }
            if ($nextSourceId !== null && ! $this->ownership->collectionProductsForLaboratory($labId)->whereKey($nextSourceId)
                ->where('customer_id', $quote->customer_id)->where('warehouse_id', $quote->warehouse_id)->exists()) {
                throw ValidationException::withMessages(['lab_code_id' => 'A origem mudou durante a correcção.']);
            }
            if ($selectedUnitId !== null && ! Unit::query()->whereKey($selectedUnitId)->sharedLock()->first()) {
                throw ValidationException::withMessages(['unit_id' => 'A unidade mudou durante a correcção.']);
            }
            $this->access->operator($userId, $labId, 'edit_quotes');
        }, 3);
    }
}
