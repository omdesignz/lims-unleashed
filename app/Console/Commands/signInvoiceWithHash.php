<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Support\DocumentSignature;
use Illuminate\Console\Command;

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
    protected $description = 'Sign Invoice with hash and save to DB';

    /**
     * Execute the console command.
     */
    public function handle(DocumentSignature $documentSignature): void
    {
        $invoice = Invoice::findOrFail($this->argument('invoice'));

        if ($invoice->type_id == 2) {
            $invoice->update([
                'paid_date' => now()->format('Y-m-d'),
            ]);
        }

        if (Invoice::where('type_id', $invoice->type_id)->whereInvoiceMonth(now()->format('Y'))->count() !== 1) {
            $prev_hash = Invoice::where('type_id', $invoice->type_id)->where('id', '<', $invoice->id)->orderBy('id', 'desc')->first()->unique_hash;
            $data = $invoice->date . ';' . $invoice->created_at->toDateTimeLocalString() . ';' . $invoice->inv_no . ';' . $invoice->total . ';' . $prev_hash;

            $invoice->unique_hash = $documentSignature->sign($data);
        }

        if (Invoice::where('type_id', $invoice->type_id)->whereInvoiceMonth(now()->format('Y'))->count() == 1) {
            $data = $invoice->date . ';' . $invoice->created_at->toDateTimeLocalString() . ';' . $invoice->inv_no . ';' . $invoice->total . ';';

            $invoice->unique_hash = $documentSignature->sign($data);
        }

        $invoice->save();
    }
}
