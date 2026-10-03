<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Support\DocumentSignature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use LogicException;

class signInvoiceWithHash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sign-invoice-with-hash {invoice}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assinar a factura com um resumo criptográfico e guardá-la na base de dados';

    /**
     * Execute the console command.
     */
    public function handle(DocumentSignature $documentSignature): int
    {
        return DB::transaction(function () use ($documentSignature): int {
            $invoice = Invoice::with('invoice_category')->lockForUpdate()->findOrFail($this->argument('invoice'));
            if (filled($invoice->unique_hash)) {
                return self::SUCCESS;
            }

            if ($invoice->invoice_category?->code === 'FR') {
                $cashBefore = Arr::except($invoice->getAttributes(), ['updated_at']);
                $paidDate = now()->toDateString();
                if (! $invoice->update([
                    'paid_date' => $paidDate,
                ])) {
                    throw new LogicException('Cash invoice payment date was not persisted.');
                }
                $cashBefore['paid_date'] = $paidDate;
                if (Arr::except($invoice->fresh()->getAttributes(), ['updated_at']) !== $cashBefore) {
                    throw new LogicException('Cash invoice content changed during payment-date initialization.');
                }
            }

            $previousHash = Invoice::withoutGlobalScope('financial_laboratory')->withTrashed()
                ->where('type_id', $invoice->type_id)->where('invoice_month', $invoice->invoice_month)
                ->where('id', '<', $invoice->id)->whereNotNull('unique_hash')->where('unique_hash', '!=', '')
                ->orderByDesc('id')->value('unique_hash');
            $data = $invoice->date.';'.$invoice->created_at->toDateTimeLocalString().';'.$invoice->inv_no.';'.$invoice->total.';'.$previousHash;

            $signature = $documentSignature->sign($data);
            if (blank($signature)) {
                throw new LogicException('Invoice signing did not produce a signature.');
            }
            $expected = [...Arr::except($invoice->getAttributes(), ['updated_at']), 'unique_hash' => $signature];
            $invoice->unique_hash = $signature;

            if (! $invoice->save()) {
                throw new LogicException('Invoice signature was not persisted.');
            }
            if (Arr::except($invoice->fresh()->getAttributes(), ['updated_at']) !== $expected) {
                throw new LogicException('Invoice content or signature changed during signing.');
            }

            return self::SUCCESS;
        }, 3);
    }
}
