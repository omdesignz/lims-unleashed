<?php

namespace App\Actions;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quote;
use App\Models\Receipt;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use LogicException;

class UpdateFinancialDocumentObservation
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access, private readonly SignQuoteRevision $revisions) {}

    /** @param class-string<Invoice|InvoiceItem|CreditNote|Receipt|Quote> $class */
    public function execute(int $userId, int $labId, string $class, int $recordId, ?string $observation): void
    {
        $permission = match ($class) {
            Invoice::class, InvoiceItem::class => 'edit_invoices',
            CreditNote::class => 'edit_credit_notes',
            Receipt::class => 'edit_receipts',
            Quote::class => 'edit_quotes',
            default => throw new LogicException('Unsupported financial observation source.'),
        };
        Validator::make(['obs' => $observation], ['obs' => ['nullable', 'string', 'max:5000']])->validate();
        DB::transaction(function () use ($userId, $labId, $class, $recordId, $observation, $permission): void {
            $operator = $this->access->operator($userId, $labId, $permission);
            if ($class === InvoiceItem::class) {
                $line = InvoiceItem::query()->where('lab_id', $labId)->findOrFail($recordId);
                Invoice::query()->where('lab_id', $labId)->lockForUpdate()->findOrFail($line->invoice_id);
            }
            $record = $class::query()->where('lab_id', $labId)->lockForUpdate()->findOrFail($recordId);
            $signatureHistory = $record instanceof Quote ? $this->revisions->history($record, true) : null;
            $lines = $record instanceof Quote ? $record->items()->withTrashed()->orderBy('id')->lockForUpdate()->get()
                ->mapWithKeys(fn (Model $line): array => [$line->id => $line->getAttributes()])->all() : null;
            $before = Arr::except($record->getAttributes(), ['obs', 'updated_at']);
            if (! $record->update(['obs' => $observation])) {
                throw new LogicException('Financial observation correction was not persisted.');
            }
            $this->assertCorrectionPersisted($record, $before, $observation);
            $audit = activity()->causedBy($operator)->performedOn($record)->event('updated')
                ->withProperties(['lab_id' => $labId])->log('Corrigiu as observações de um documento financeiro emitido.');
            $audit = $audit?->newQueryWithoutScopes()->find($audit->id);
            if (! $audit || $audit->subject_type !== $record->getMorphClass() || (int) $audit->subject_id !== $recordId
                || $audit->causer_type !== $operator->getMorphClass() || (int) $audit->causer_id !== $userId
                || $audit->event !== 'updated' || $audit->properties->all() !== ['lab_id' => $labId]) {
                throw new LogicException('Financial observation correction audit was not persisted.');
            }
            $this->assertCorrectionPersisted($record, $before, $observation);
            if ($lines !== null && $record->items()->withoutGlobalScope('financial_laboratory')->withTrashed()->orderBy('id')->get()
                ->mapWithKeys(fn (Model $line): array => [$line->id => $line->getAttributes()])->all() !== $lines) {
                throw new LogicException('Retained billed quote lines changed during observation correction.');
            }
            if ($signatureHistory !== null && $this->revisions->history($record) !== $signatureHistory) {
                throw new LogicException('Retained quote signature history changed during observation correction.');
            }
            $this->access->operator($userId, $labId, $permission);
        }, 3);
    }

    /** @param array<string, mixed> $before */
    private function assertCorrectionPersisted(Model $record, array $before, ?string $observation): void
    {
        $persisted = $record->fresh();
        if (! $persisted || $persisted->obs !== $observation
            || Arr::except($persisted->getAttributes(), ['obs', 'updated_at']) !== $before) {
            throw new LogicException('Issued financial content changed during an observation correction.');
        }
    }
}
