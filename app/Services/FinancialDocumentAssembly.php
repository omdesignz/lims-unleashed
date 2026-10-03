<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Invoice;
use Closure;
use Illuminate\Container\Attributes\Give;
use Illuminate\Database\DatabaseTransactionRecord;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use WeakMap;

class FinancialDocumentAssembly
{
    /** @var WeakMap<Invoice|CreditNote, bool> */
    private WeakMap $newDocuments;

    /** @var array<string, int> */
    private array $assembling = [];

    /** @var array<int, array<string, int>> */
    private array $invoiceState = [];

    public function __construct(#[Give('db.transactions')] private readonly DatabaseTransactionsManager $transactions)
    {
        $this->newDocuments = new WeakMap;
    }

    public function rememberCreation(Invoice|CreditNote $document): void
    {
        if ($document->getConnection()->transactionLevel() < 1) {
            return;
        }
        $this->newDocuments[$document] = true;
        $expire = function () use ($document): void {
            unset($this->newDocuments[$document]);
        };
        $records = $this->transactions->getPendingTransactions()
            ->filter(fn (DatabaseTransactionRecord $record): bool => $record->connection === $document->getConnection()->getName());
        if ($records->isEmpty()) {
            unset($this->newDocuments[$document]);
            throw new LogicException('Document creation requires a tracked issuance transaction.');
        }
        $document->getConnection()->afterCommit($expire);
        /** A committed child's rollback callbacks are discarded if an ancestor rolls back. */
        foreach ($records as $record) {
            $record->addCallbackForRollback($expire);
        }
    }

    public function withLines(Invoice|CreditNote $document, Closure $callback): mixed
    {
        if (! isset($this->newDocuments[$document]) || $document->getConnection()->transactionLevel() < 1) {
            throw new LogicException('Only a newly created transactional document can assemble issued lines.');
        }
        $key = $document->getMorphClass().':'.$document->id;
        $this->assembling[$key] = ($this->assembling[$key] ?? 0) + 1;
        try {
            return $callback();
        } finally {
            if (--$this->assembling[$key] === 0) {
                unset($this->assembling[$key]);
            }
        }
    }

    public function allowsLines(Model $document): bool
    {
        return isset($this->assembling[$document->getMorphClass().':'.$document->id]);
    }

    /** @param list<string> $fields */
    public function withInvoiceState(Invoice $invoice, array $fields, Closure $callback): mixed
    {
        if ($invoice->getConnection()->transactionLevel() < 1
            || array_diff($fields, ['amount_due', 'status', 'paid_date', 'payment_method', 'status_code'])) {
            throw new LogicException('Invoice settlement/cancellation must be transactional and narrowly scoped.');
        }
        foreach ($fields as $field) {
            $this->invoiceState[$invoice->id][$field] = ($this->invoiceState[$invoice->id][$field] ?? 0) + 1;
        }
        try {
            return $callback();
        } finally {
            foreach ($fields as $field) {
                if (--$this->invoiceState[$invoice->id][$field] === 0) {
                    unset($this->invoiceState[$invoice->id][$field]);
                }
            }
            if ($this->invoiceState[$invoice->id] === []) {
                unset($this->invoiceState[$invoice->id]);
            }
        }
    }

    public function allowsInvoiceState(Invoice $invoice, string $field): bool
    {
        return isset($this->invoiceState[$invoice->id][$field]);
    }
}
