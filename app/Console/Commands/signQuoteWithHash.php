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
    protected $description = 'Assinar a cotação com um resumo criptográfico e guardá-la na base de dados';

    /**
     * Execute the console command.
     */
    public function handle(DocumentSignature $documentSignature): void
    {
        $quote = Quote::findOrFail($this->argument('quote'));
        if (filled($quote->unique_hash)) {
            return;
        }

        if (Quote::withoutGlobalScope('financial_laboratory')->whereQuoteMonth(now()->format('Y'))->count() !== 1) {
            $prev_hash = Quote::withoutGlobalScope('financial_laboratory')->where('id', '<', $quote->id)->orderBy('id', 'desc')->first()?->unique_hash ?? '';
            $data = $quote->date.';'.$quote->created_at->toDateTimeLocalString().';'.$quote->quote_no.';'.$quote->total.';'.$prev_hash;

            $quote->unique_hash = $documentSignature->sign($data);
        }

        if (Quote::withoutGlobalScope('financial_laboratory')->whereQuoteMonth(now()->format('Y'))->count() == 1) {
            $data = $quote->date.';'.$quote->created_at->toDateTimeLocalString().';'.$quote->quote_no.';'.$quote->total.';';

            $quote->unique_hash = $documentSignature->sign($data);
        }

        $intendedHash = $quote->unique_hash;
        if (! $quote->save() || $quote->fresh()->unique_hash !== $intendedHash) {
            throw new \LogicException('The quote signature was not persisted.');
        }
    }
}
