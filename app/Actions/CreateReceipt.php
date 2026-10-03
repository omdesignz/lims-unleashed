<?php

namespace App\Actions;

use App\Models\Invoice;
use App\Models\InvoiceReceipt;
use App\Models\PaymentCategory;
use App\Models\Receipt;
use App\Models\Warehouse;
use App\Services\FinancialDocumentAssembly;
use App\Services\IssuedFinancialDocumentIntegrity;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LogicException;

class CreateReceipt
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function execute(int $userId, int $labId, array $attributes, array $items, string $permission = 'add_receipts'): Receipt
    {
        abort_unless(in_array($permission, ['add_receipts', 'edit_invoices'], true), 403);
        $attributes = Validator::make($attributes, [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'invoice_id' => ['nullable', 'integer', 'min:1'],
            'payment_type' => ['nullable', 'integer', 'exists:payment_categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'obs' => ['nullable', 'string', 'max:5000'],
        ])->validate();
        Validator::make(['items' => $items], [
            'items' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'items.*.invoice_id' => ['required', 'integer', 'min:1', 'distinct:strict'],
            'items.*.payment_id' => ['required', 'integer', 'exists:payment_categories,id'],
            'items.*.paid_amount' => ['required', 'numeric', 'gt:0', 'regex:/^\d{1,8}(\.\d{1,2})?$/'],
            'items.*.obs' => ['nullable', 'string', 'max:5000'],
        ])->validate();

        return DB::transaction(function () use ($userId, $labId, $attributes, $items, $permission): Receipt {
            $operator = $this->access->operator($userId, $labId, $permission);
            $invoices = Invoice::query()->where('lab_id', $labId)->whereIn('id', array_column($items, 'invoice_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            abort_unless($invoices->count() === count($items), 404);
            $customerId = (int) ($attributes['customer_id'] ?? 0);
            $warehouseId = (int) ($attributes['warehouse_id'] ?? 0);
            if (! Warehouse::query()->whereKey($warehouseId)->where('customer_id', $customerId)->exists()) {
                throw ValidationException::withMessages(['warehouse_id' => 'O local deve pertencer ao cliente seleccionado.']);
            }

            $allocations = [];
            $invoiceSnapshots = [];
            foreach ($items as $index => $item) {
                $invoice = $invoices->get($item['invoice_id']);
                if ((int) $invoice->customer_id !== $customerId || (int) $invoice->warehouse_id !== $warehouseId
                    || $invoice->status_code !== Invoice::STATUS_CODE_NORMAL) {
                    throw ValidationException::withMessages(["items.$index.invoice_id" => 'A factura não pode ser liquidada neste recibo.']);
                }
                $paid = $this->cents((string) $item['paid_amount']);
                $due = $this->cents((string) $invoice->amount_due);
                if ($paid > $due) {
                    throw ValidationException::withMessages(["items.$index.paid_amount" => 'O pagamento excede o saldo actual da factura.']);
                }
                $allocations[] = [
                    'invoice_id' => $invoice->id,
                    'payment_id' => (int) $item['payment_id'],
                    'user_id' => $operator->id,
                    'paid_amount' => $this->decimal($paid),
                    'invoice_pending_amount' => $this->decimal($due),
                    'pending_amount' => $this->decimal($due - $paid),
                    'obs' => $item['obs'] ?? null,
                ];
                $invoiceSnapshots[$invoice->id] = Arr::except($invoice->getAttributes(), ['updated_at']);
            }

            $receipt = new Receipt([
                'customer_id' => $customerId, 'warehouse_id' => $warehouseId,
                'invoice_id' => $attributes['invoice_id'] ?? null,
                'payment_type' => $attributes['payment_type'] ?? null,
                'user_id' => $operator->id, 'rec_month' => now()->format('Y'),
                'date' => now()->toDateString(), 'is_original' => true, 'exported_saft' => false,
                'description' => $attributes['description'] ?? '', 'obs' => $attributes['obs'] ?? null,
            ]);
            $receipt->lab_id = $labId;
            $intendedReceipt = clone $receipt;
            abort_unless($receipt->save(), 409);
            $persistedReceipt = $receipt->fresh();
            if (filled($persistedReceipt->unique_hash)) {
                throw new LogicException('New receipts cannot supply a pre-existing signature.');
            }
            app(IssuedFinancialDocumentIntegrity::class)->assertCreationIntent($intendedReceipt, $persistedReceipt);
            abort_unless((int) $persistedReceipt->seq > 0 && $persistedReceipt->rec_no === 'RG '.$intendedReceipt->rec_month.'/'.$persistedReceipt->seq, 409);
            $receiptBefore = Arr::except($persistedReceipt->getAttributes(), ['updated_at']);
            $issuedLines = [];

            foreach ($allocations as $allocation) {
                $line = new InvoiceReceipt($allocation);
                $line->receipt_id = $receipt->id;
                abort_unless($line->save(), 409);
                $persistedLine = $line->fresh();
                foreach ($allocation as $field => $value) {
                    abort_unless($persistedLine->getAttribute($field) === $value
                        || ($value !== null && (string) $persistedLine->getAttribute($field) === (string) $value), 409);
                }
                abort_unless((int) $persistedLine->lab_id === $labId && (int) $persistedLine->receipt_id === (int) $receipt->id, 409);
                $issuedLines[] = $persistedLine->getAttributes();
                $invoice = $invoices->get($allocation['invoice_id']);
                $settled = $allocation['pending_amount'] === '0.00';
                $settlement = [
                    'amount_due' => $allocation['pending_amount'], 'status' => $settled,
                    'paid_date' => $settled ? now()->toDateString() : null,
                    'payment_method' => PaymentCategory::query()->findOrFail($allocation['payment_id'])->name,
                ];
                $invoiceSnapshots[$invoice->id] = [...$invoiceSnapshots[$invoice->id], ...$settlement];
                abort_unless(app(FinancialDocumentAssembly::class)->withInvoiceState($invoice, ['amount_due', 'status', 'paid_date', 'payment_method'], fn (): bool => $invoice->update($settlement)), 409);
                abort_unless((string) $invoice->fresh()->amount_due === $allocation['pending_amount'], 409);
            }

            if (filled($receipt->fresh()->unique_hash)) {
                throw new LogicException('Receipt allocations cannot supply a pre-existing signature.');
            }
            abort_unless(Artisan::call('app:sign-receipt-with-hash', ['receipt' => $receipt->id]) === 0, 409);
            abort_unless(filled($receipt->fresh()->unique_hash), 409);
            $receiptBefore['unique_hash'] = $receipt->fresh()->unique_hash;
            $audit = activity()->causedBy($operator)->performedOn($receipt)->event('issued')
                ->withProperties(['lab_id' => $labId, 'allocations' => $allocations])
                ->log('Emitiu um recibo com pagamentos de facturas.');
            abort_unless($audit?->exists && $audit->newQueryWithoutScopes()->whereKey($audit->id)->exists(), 409);
            $persistedReceipt = $receipt->newQueryWithoutScopes()->findOrFail($receipt->id);
            $persistedAudit = $audit->newQueryWithoutScopes()->find($audit->id);
            $persistedLines = $persistedReceipt->items()->withoutGlobalScope('financial_laboratory')->withTrashed()->orderBy('id')->get()
                ->map(fn (InvoiceReceipt $line): array => $line->getAttributes())->all();
            abort_unless(Arr::except($persistedReceipt->getAttributes(), ['updated_at']) === $receiptBefore
                && $persistedLines === $issuedLines && $persistedAudit
                && $persistedAudit->subject_type === $receipt->getMorphClass()
                && (int) $persistedAudit->subject_id === (int) $receipt->id
                && $persistedAudit->causer_type === $operator->getMorphClass()
                && (int) $persistedAudit->causer_id === $userId && $persistedAudit->event === 'issued'
                && (int) $persistedAudit->properties->get('lab_id') === $labId
                && $persistedAudit->properties->get('allocations') === $allocations, 409);
            foreach ($invoices as $invoice) {
                abort_unless(Arr::except($invoice->newQueryWithoutScopes()->findOrFail($invoice->id)->getAttributes(), ['updated_at']) === $invoiceSnapshots[$invoice->id], 409);
            }
            $this->access->operator($userId, $labId, $permission);

            return $receipt->fresh();
        }, 3);
    }

    private function cents(string $amount): int
    {
        if (! preg_match('/^\d{1,8}(\.\d{1,2})?$/', $amount)) {
            throw ValidationException::withMessages(['items' => 'O saldo ou montante da factura é inválido.']);
        }
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    private function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
