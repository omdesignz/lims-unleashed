<?php

namespace App\Console\Commands;

use App\Models\Quote;
use App\Support\DocumentSignature;
use Illuminate\Console\Command;

class signQuoteWithHash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sign-quote-with-hash {quote}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sign Credit Note with hash and save to DB';

    /**
     * Execute the console command.
     */
    public function handle(DocumentSignature $documentSignature): void
    {
        $quote = Quote::findOrFail($this->argument('quote'));

        if (Quote::whereQuoteMonth(now()->format('Y'))->count() !== 1) {
            $prev_hash = Quote::where('id', '<', $quote->id)->orderBy('id', 'desc')->first()->unique_hash;
            $data = $quote->date . ';' . $quote->created_at->toDateTimeLocalString() . ';' . $quote->quote_no . ';' . $quote->total . ';' . $prev_hash;

            $quote->unique_hash = $documentSignature->sign($data);
        }

        if (Quote::whereQuoteMonth(now()->format('Y'))->count() == 1) {
            $data = $quote->date . ';' . $quote->created_at->toDateTimeLocalString() . ';' . $quote->quote_no . ';' . $quote->total . ';';

            $quote->unique_hash = $documentSignature->sign($data);
        }

        $quote->save();
    }
}
