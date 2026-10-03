<?php

namespace App\Actions;

use App\Models\CollectionProduct;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\IssuedFinancialDocumentIntegrity;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\LaboratoryWorkflowOwnership;
use App\Services\QuoteAuthoringData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class SaveQuote
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly LaboratoryWorkflowOwnership $ownership, private readonly QuoteAuthoringData $data,
        private readonly IssuedFinancialDocumentIntegrity $integrity, private readonly UpdateFinancialDocumentObservation $observations,
        private readonly SignQuoteRevision $revisions) {}

    /** @param array<string, mixed> $input */
    public function execute(int $userId, int $labId, array $input, ?int $id = null): Quote
    {
        $permission = $id === null ? 'add_quotes' : 'edit_quotes';

        return DB::transaction(function () use ($userId, $labId, $input, $id, $permission): Quote {
            $operator = $this->access->operator($userId, $labId, $permission);
            $record = $id === null ? new Quote : Quote::query()->where('lab_id', $labId)->lockForUpdate()->findOrFail($id);
            $locked = $record->exists && ($record->invoice_id !== null || $record->converted_to_invoice);
            $attributes = $this->data->validate($input, $locked);
            if ($locked) {
                $this->observations->execute($userId, $labId, Quote::class, $record->id, $attributes['obs']);

                return $record->refresh();
            }
            $retainedHistory = $record->exists ? $this->revisions->history($record, true) : [];
            $oldLines = $record->exists ? $record->items()->withTrashed()->orderBy('id')->lockForUpdate()->get() : collect();
            $previousSnapshot = $record->exists ? $this->revisions->snapshot($record, $oldLines) : null;
            $lineEvidence = $oldLines->mapWithKeys(fn (Model $line): array => [$line->id => $line->getAttributes()])->all();
            if (! Customer::query()->whereKey($attributes['customer_id'])->sharedLock()->first()
                || ! Warehouse::query()->whereKey($attributes['warehouse_id'])->where('customer_id', $attributes['customer_id'])->sharedLock()->first()) {
                throw ValidationException::withMessages(['warehouse_id' => 'O local deve pertencer ao cliente seleccionado.']);
            }
            $units = collect($attributes['items'])->pluck('unit_id')->filter()->unique()->sort()->values();
            if (Unit::query()->whereKey($units)->orderBy('id')->sharedLock()->get()->count() !== $units->count()) {
                throw ValidationException::withMessages(['items' => 'Uma unidade seleccionada já não está disponível.']);
            }
            $newSources = collect($attributes['items'])->pluck('collection_product_id')->filter()->unique()->sort()->values();
            $oldSources = $oldLines->where('itemable_type', 'collectionproduct')->pluck('itemable_id')->filter()->unique();
            $sourceIds = $oldSources->merge($newSources)->unique()->sort()->values();
            $sources = $this->ownership->collectionProductsForLaboratory($labId, true)->withTrashed()->whereKey($sourceIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($sources->count() !== $sourceIds->count()) {
                throw ValidationException::withMessages(['items' => 'A origem da cotação não pertence a este laboratório.']);
            }
            foreach ($newSources as $sourceId) {
                $source = $sources[$sourceId];
                if ($source->trashed() || ! $this->ownership->collectionProductsForLaboratory($labId)->whereKey($sourceId)->exists()
                    || (int) $source->customer_id !== (int) $attributes['customer_id'] || (int) $source->warehouse_id !== (int) $attributes['warehouse_id']
                    || $source->invoiced || $source->invoice_id !== null || ($source->quote_id !== null && (int) $source->quote_id !== $id)) {
                    throw ValidationException::withMessages(['items' => 'A origem deve estar disponível para este cliente, local e cotação.']);
                }
            }
            $calculations = [];
            $subtotal = $tax = $discount = 0;
            foreach ($attributes['items'] as $index => $item) {
                $calculation = $this->data->calculate($item, $index);
                $calculations[] = $calculation;
                $subtotal += $this->data->scaled($calculation['line']['total']);
                $tax += $this->data->scaled($calculation['line']['tax_amount']);
                $discount += $calculation['discount_total'];
            }
            $record->fill(Arr::except($attributes, ['items']));
            $record->forceFill(['sub_total' => $this->data->decimal($subtotal), 'tax' => $this->data->decimal($tax),
                'discount' => $this->data->decimal($discount), 'total' => $this->data->decimal($subtotal + $tax)]);
            if ($id === null) {
                $record->forceFill(['lab_id' => $labId, 'user_id' => $operator->id, 'quote_month' => now()->format('Y'),
                    'date' => $attributes['date'] ?? now()->toDateString(), 'status' => false, 'is_original' => true,
                    'converted_to_invoice' => false, 'exported_saft' => false, 'invoice_id' => null]);
            }
            $intended = clone $record;
            unset($intended->updated_at);
            if (! $record->save()) {
                throw new LogicException('Quote authoring was not persisted.');
            }
            $persisted = $record->fresh();
            $this->integrity->assertCreationIntent($intended, $persisted, ['sub_total', 'tax', 'discount', 'total']);
            if ($id === null && (! filled($persisted->unique_hash) || (int) $persisted->seq < 1
                || $persisted->quote_no !== 'PP '.$persisted->quote_month.'/'.$persisted->seq)) {
                throw new LogicException('The new quote identity or signature was not persisted.');
            }
            if ($id === null) {
                foreach ($record->generatedAuthoringIdentity() ?? [] as $field => $value) {
                    if ((string) $persisted->$field !== (string) $value) {
                        throw new LogicException('The generated quote identity or signature changed during creation.');
                    }
                }
            }
            $rootEvidence = Arr::except($persisted->getAttributes(), ['updated_at']);
            $previousSnapshot ??= $this->revisions->snapshot($persisted, collect());
            foreach ($oldLines->reject(fn (Model $line): bool => $line->trashed()) as $line) {
                $before = Arr::except($line->getAttributes(), ['deleted_at', 'updated_at']);
                if (! $line->delete() || ! $line->fresh()->trashed()
                    || Arr::except($line->fresh()->getAttributes(), ['deleted_at', 'updated_at']) !== $before) {
                    throw new LogicException('A retired quote line was lost or changed.');
                }
                $lineEvidence[$line->id] = $line->fresh()->getAttributes();
            }
            foreach ($calculations as $calculation) {
                $line = new QuoteItem($calculation['line']);
                $line->forceFill(['quote_id' => $record->id, 'lab_id' => $labId]);
                $intendedLine = clone $line;
                if (! $line->save()) {
                    throw new LogicException('Quote line authoring was not persisted.');
                }
                $this->integrity->assertCreationIntent($intendedLine, $line->fresh(), ['qty', 'unit_price', 'total', 'discount_percentage', 'discount_amount', 'tax_amount', 'tax_percentage']);
                $lineEvidence[$line->id] = $line->fresh()->getAttributes();
            }
            $sourceEvidence = [];
            foreach ($sources as $source) {
                if ($newSources->contains($source->id)) {
                    $source->forceFill(['quote_id' => $record->id, 'quoted' => true]);
                } elseif ((int) $source->quote_id === (int) $record->id) {
                    $source->forceFill(['quote_id' => null, 'quoted' => false]);
                }
                $intendedSource = clone $source;
                unset($intendedSource->updated_at);
                if (! $source->save()) {
                    throw new LogicException('Quote source linkage was not persisted.');
                }
                $this->integrity->assertCreationIntent($intendedSource, $source->fresh());
                $sourceEvidence[$source->id] = $source->fresh()->getAttributes();
            }
            if (Arr::except($record->fresh()->getAttributes(), ['updated_at']) !== $rootEvidence) {
                throw new LogicException('Quote content changed before revision signing.');
            }
            $record->refresh();
            $signatureEvidence = $this->revisions->execute($record, $operator, $previousSnapshot, $retainedHistory);
            $rootEvidence['unique_hash'] = $record->unique_hash;
            $properties = ['lab_id' => $labId, 'operation' => $id === null ? 'create' : 'update', 'line_ids' => array_keys($lineEvidence)];
            $audit = activity()->causedBy($operator)->performedOn($record)->event('authored')->withProperties($properties)->log('Guardou a cotação.');
            $audit = $audit?->newQueryWithoutScopes()->find($audit->id);
            if (! $audit || $audit->subject_type !== $record->getMorphClass() || (int) $audit->subject_id !== (int) $record->id
                || $audit->causer_type !== $operator->getMorphClass() || (int) $audit->causer_id !== $userId
                || $audit->event !== 'authored' || $audit->properties->all() !== $properties
                || Arr::except($record->newQueryWithoutScopes()->findOrFail($record->id)->getAttributes(), ['updated_at']) !== $rootEvidence
                || $record->items()->withoutGlobalScope('financial_laboratory')->withTrashed()->orderBy('id')->get()->mapWithKeys(fn (Model $line): array => [$line->id => $line->getAttributes()])->all() !== $lineEvidence
                || CollectionProduct::withoutGlobalScopes()->whereKey($sourceIds)->orderBy('id')->get()->mapWithKeys(fn (Model $source): array => [$source->id => $source->getAttributes()])->all() !== $sourceEvidence
                || $this->revisions->history($record) !== $signatureEvidence) {
                throw new LogicException('Quote content, retained lines, source links or audit changed during authoring.');
            }
            foreach ($attributes['items'] as $index => $item) {
                if ($this->data->calculate($item, $index) !== $calculations[$index]) {
                    throw new LogicException('The selected catalogue changed during quote authoring.');
                }
            }
            if (! Customer::query()->whereKey($attributes['customer_id'])->sharedLock()->first()
                || ! Warehouse::query()->whereKey($attributes['warehouse_id'])->where('customer_id', $attributes['customer_id'])->sharedLock()->first()
                || Unit::query()->whereKey($units)->orderBy('id')->sharedLock()->get()->count() !== $units->count()
                || $this->ownership->collectionProductsForLaboratory($labId)->whereKey($newSources)->count() !== $newSources->count()) {
                throw ValidationException::withMessages(['items' => 'O cliente, local, unidade ou origem mudou durante a gravação.']);
            }
            $this->access->operator($userId, $labId, $permission);

            return $record->refresh();
        }, 3);
    }
}
