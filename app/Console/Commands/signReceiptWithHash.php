<?php

namespace App\Console\Commands;

use App\Models\Receipt;
use App\Support\DocumentSignature;
use Illuminate\Console\Command;

class signReceiptWithHash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sign-receipt-with-hash {receipt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sign Receipt with hash and save to DB';

    /**
     * Execute the console command.
     */
    public function handle(DocumentSignature $documentSignature): void
    {
        $receipt = Receipt::with('items')->findOrFail($this->argument('receipt'));

        if (Receipt::whereRecMonth(now()->format('Y'))->count() !== 1) {
            $prev_hash = Receipt::where('id', '<', $receipt->id)->orderBy('id', 'desc')->first()->unique_hash;
            $data = $receipt->date . ';' . $receipt->created_at->toDateTimeLocalString() . ';' . $receipt->rec_no . ';' . $receipt->items->sum('paid_amount') . ';' . $prev_hash;

            $receipt->unique_hash = $documentSignature->sign($data);
        }

        if (Receipt::whereRecMonth(now()->format('Y'))->count() == 1) {
            $data = $receipt->date . ';' . $receipt->created_at->toDateTimeLocalString() . ';' . $receipt->rec_no . ';' . $receipt->items->sum('paid_amount') . ';';

            $receipt->unique_hash = $documentSignature->sign($data);
        }

        $receipt->save();
    }
}
