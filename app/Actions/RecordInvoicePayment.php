<?php

namespace App\Actions;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Facades\DB;

class RecordInvoicePayment
{
    public function __construct(private readonly CreateReceipt $receipts, private readonly LaboratoryWorkflowMutationAccess $access) {}

    public function execute(int $userId, int $labId, int $invoiceId, int $paymentId): Receipt
    {
        return DB::transaction(function () use ($userId, $labId, $invoiceId, $paymentId): Receipt {
            $this->access->operator($userId, $labId, 'edit_invoices');
            $invoice = Invoice::query()->where('lab_id', $labId)->lockForUpdate()->findOrFail($invoiceId);

            return $this->receipts->execute($userId, $labId, [
                'customer_id' => $invoice->customer_id,
                'warehouse_id' => $invoice->warehouse_id,
                'invoice_id' => $invoice->id,
                'payment_type' => $paymentId,
            ], [[
                'invoice_id' => $invoiceId,
                'payment_id' => $paymentId,
                'paid_amount' => $invoice->amount_due,
            ]], 'edit_invoices');
        }, 3);
    }
}
