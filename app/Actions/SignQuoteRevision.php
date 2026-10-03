<?php

namespace App\Actions;

use App\Models\ISOActivityLog;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use App\Support\DocumentSignature;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LogicException;

class SignQuoteRevision
{
    public function __construct(private readonly DocumentSignature $signatures) {}

    /** @param Collection<int, QuoteItem> $lines
     * @return array<string, mixed>
     */
    public function snapshot(Quote $quote, Collection $lines): array
    {
        return [
            'quote' => Arr::only($quote->getAttributes(), ['id', 'lab_id', 'user_id', 'quote_no', 'quote_month', 'seq', 'date', 'due_date',
                'customer_id', 'warehouse_id', 'description', 'internal_ref', 'sub_total', 'discount', 'tax', 'total', 'use_matrix_price', 'is_service']),
            'lines' => $lines->reject(fn (QuoteItem $line): bool => $line->trashed())->map(fn (QuoteItem $line): array => Arr::only($line->getAttributes(),
                ['id', 'quote_id', 'lab_id', 'item_id', 'item_description', 'obs', 'unit_id', 'itemable_id', 'itemable_type', 'qty', 'unit_price', 'total',
                    'discount_amount', 'discount_percentage', 'tax_amount', 'tax_percentage', 'tax_id', 'charge_tax', 'exemption_id', 'exemption_code', 'extra_data']))->values()->all(),
        ];
    }

    /** Caller holds the quote lock and authorizes the surrounding mutation.
     * @param  array<string, mixed>  $previousSnapshot
     * @param  array<int, array<string, mixed>>  $retainedHistory
     * @return array<int, array<string, mixed>>
     */
    public function execute(Quote $quote, User $operator, array $previousSnapshot, array $retainedHistory): array
    {
        if ($quote->getConnection()->transactionLevel() < 1 || $quote->invoice_id !== null || $quote->converted_to_invoice) {
            throw new LogicException('Only a transactionally locked unbilled quote can receive a draft revision signature.');
        }
        $before = Arr::except($quote->fresh()->getAttributes(), ['updated_at']);
        $history = $this->history($quote, true);
        if ($history !== $retainedHistory) {
            throw new LogicException('Retained quote signature history changed before revision signing.');
        }
        $payload = json_encode([
            'schema' => 'quote_revision_v1', 'revision_id' => (string) Str::uuid(),
            'previous_signature' => $quote->unique_hash,
            'previous_snapshot' => $previousSnapshot,
            'snapshot' => $this->snapshot($quote->fresh(), $quote->items()->orderBy('id')->lockForUpdate()->get()),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $signature = $this->signatures->sign($payload);
        if (blank($signature)) {
            throw new LogicException('Quote revision signing did not produce a signature.');
        }
        $quote->forceFill(['unique_hash' => $signature]);
        if (! $quote->save() || Arr::except($quote->fresh()->getAttributes(), ['updated_at']) !== [...$before, 'unique_hash' => $signature]) {
            throw new LogicException('The quote revision signature or content was not persisted.');
        }
        $properties = ['lab_id' => (int) $quote->lab_id, 'previous_signature' => $before['unique_hash'] ?? null,
            'signature' => $signature, 'signed_payload' => $payload];
        $audit = activity()->causedBy($operator)->performedOn($quote)->event('signed_revision')->withProperties($properties)->log('Assinou uma revisão da cotação.');
        $audit = $audit?->newQueryWithoutScopes()->find($audit->id);
        if (! $audit || $audit->subject_type !== $quote->getMorphClass() || (int) $audit->subject_id !== (int) $quote->id
            || $audit->causer_type !== $operator->getMorphClass() || (int) $audit->causer_id !== (int) $operator->id
            || $audit->event !== 'signed_revision' || $audit->properties->all() !== $properties) {
            throw new LogicException('Quote revision evidence was not persisted.');
        }
        $history[$audit->id] = $audit->getAttributes();
        if ($this->history($quote) !== $history) {
            throw new LogicException('Retained quote signature history changed during authoring.');
        }

        return $history;
    }

    /** @return array<int, array<string, mixed>> */
    public function history(Quote $quote, bool $lock = false): array
    {
        $query = ISOActivityLog::withoutGlobalScopes()->where('subject_type', $quote->getMorphClass())->where('subject_id', $quote->id)
            ->where('event', 'signed_revision')->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->mapWithKeys(fn (ISOActivityLog $audit): array => [$audit->id => $audit->getAttributes()])->all();
    }
}
