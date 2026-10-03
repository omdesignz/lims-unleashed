<?php

namespace App\Services;

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
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use LogicException;

class IssuedFinancialDocumentIntegrity
{
    public function __construct(private readonly FinancialDocumentAssembly $assembly) {}

    /** @param list<string> $decimalFields */
    public function assertCreationIntent(Model $intended, Model $persisted, array $decimalFields = []): void
    {
        $intended = clone $intended;
        $persisted = clone $persisted;
        $casts = array_fill_keys($decimalFields, 'decimal:2');
        $intended->mergeCasts($casts);
        $persisted->mergeCasts($casts);
        foreach (array_keys($intended->getAttributes()) as $field) {
            $expected = $intended->getAttribute($field);
            $actual = $persisted->getAttribute($field);
            $expected = $expected instanceof Arrayable ? $expected->toArray() : $expected;
            $actual = $actual instanceof Arrayable ? $actual->toArray() : $actual;
            $expected = $expected instanceof DateTimeInterface ? $expected->format('Y-m-d H:i:s.uP') : $expected;
            $actual = $actual instanceof DateTimeInterface ? $actual->format('Y-m-d H:i:s.uP') : $actual;
            if ($actual !== $expected && ! (is_scalar($expected) && is_scalar($actual) && (string) $actual === (string) $expected)) {
                throw new LogicException('Financial creation changed intended '.$field.'.');
            }
        }
    }

    public function assertUpdating(Model $record): void
    {
        if ($record instanceof Quote || $record instanceof ImportCertificate || $record instanceof ExportCertificate) {
            $persisted = $record->newQueryWithoutScopes()->find($record->id);
            if ($persisted && $this->isBilledSource($persisted)) {
                foreach (array_keys($record->getDirty()) as $field) {
                    if (in_array($field, ['obs', 'updated_at', 'deleted_at'], true)) {
                        continue;
                    }
                    if (in_array($field, ['invoice_id', 'invoiced', 'converted_to_invoice'], true)) {
                        throw new LogicException('Billed source links and state cannot be changed.');
                    }
                    throw ValidationException::withMessages([$field => 'O documento facturado está bloqueado. Apenas as observações podem ser corrigidas.']);
                }
            }

            return;
        }
        if ($record instanceof QuoteItem || $record instanceof ImportCertificateItem || $record instanceof ExportCertificateItem) {
            if ($this->isBilledSource($this->parent($record)) && array_diff(array_keys($record->getDirty()), ['updated_at']) !== []) {
                throw ValidationException::withMessages(['items' => 'As linhas do documento facturado devem ser preservadas.']);
            }

            return;
        }
        if (! ($record instanceof Invoice || $record instanceof CreditNote || $record instanceof Receipt
            || $record instanceof InvoiceItem || $record instanceof CreditNoteItem || $record instanceof InvoiceReceipt)) {
            return;
        }
        foreach (array_keys($record->getDirty()) as $field) {
            if (in_array($field, ['obs', 'updated_at'], true)
                || ($field === 'deleted_at' && ($record instanceof Invoice || $record instanceof CreditNote || $record instanceof Receipt))) {
                continue;
            }
            if (($record instanceof Invoice || $record instanceof CreditNote || $record instanceof Receipt)
                && $field === 'unique_hash' && ! filled($record->getOriginal('unique_hash'))) {
                continue;
            }
            if ($record instanceof Invoice && (($field === 'paid_date' && ! filled($record->getOriginal('unique_hash')))
                || $this->assembly->allowsInvoiceState($record, $field))) {
                continue;
            }
            throw ValidationException::withMessages([$field => 'O conteúdo financeiro emitido está bloqueado. Apenas as observações podem ser corrigidas.']);
        }
    }

    public function assertCreatingLine(Model $record): void
    {
        $parent = $this->parent($record);
        if ($this->isBilledSource($parent)) {
            throw ValidationException::withMessages(['items' => 'Não é permitido acrescentar linhas a um documento facturado.']);
        }
        if (($parent instanceof Invoice || $parent instanceof CreditNote || $parent instanceof Receipt)
            && filled($parent->unique_hash) && ! $this->assembly->allowsLines($parent)) {
            throw ValidationException::withMessages(['items' => 'Não é permitido acrescentar linhas a um documento financeiro emitido.']);
        }
    }

    public function assertDeletingLine(Model $record): void
    {
        $parent = $this->parent($record);
        if ($this->isBilledSource($parent)) {
            throw ValidationException::withMessages(['items' => 'As linhas do documento facturado devem ser preservadas.']);
        }
        if (($parent instanceof Invoice || $parent instanceof CreditNote || $parent instanceof Receipt) && filled($parent->unique_hash)) {
            throw ValidationException::withMessages(['items' => 'As linhas de um documento financeiro emitido devem ser preservadas.']);
        }
    }

    private function isBilledSource(?Model $record): bool
    {
        return ($record instanceof Quote || $record instanceof ImportCertificate || $record instanceof ExportCertificate)
            && ($record->invoice_id !== null || (bool) $record->getAttribute($record instanceof Quote ? 'converted_to_invoice' : 'invoiced'));
    }

    private function parent(Model $record): ?Model
    {
        return match (true) {
            $record instanceof InvoiceItem => Invoice::withTrashed()->findOrFail($record->invoice_id),
            $record instanceof CreditNoteItem => CreditNote::withTrashed()->findOrFail($record->note_id),
            $record instanceof InvoiceReceipt => Receipt::withTrashed()->findOrFail($record->receipt_id),
            $record instanceof QuoteItem => Quote::withTrashed()->findOrFail($record->quote_id),
            $record instanceof ImportCertificateItem => ImportCertificate::withTrashed()->findOrFail($record->certificate_id),
            $record instanceof ExportCertificateItem => ExportCertificate::withTrashed()->findOrFail($record->certificate_id),
            default => null,
        };
    }
}
