<?php

namespace App\Console\Commands;

use App\Models\CreditNote;
use App\Support\DocumentSignature;
use Illuminate\Console\Command;

class signCreditNoteWithHash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sign-credit-note-with-hash {credit_note}';

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
        $note = CreditNote::findOrFail($this->argument('credit_note'));

        if (CreditNote::whereNoteMonth(now()->format('Y'))->count() !== 1) {
            $prev_hash = CreditNote::where('id', '<', $note->id)->orderBy('id', 'desc')->first()->unique_hash;
            $data = $note->date . ';' . $note->created_at->toDateTimeLocalString() . ';' . $note->note_no . ';' . $note->total . ';' . $prev_hash;

            $note->unique_hash = $documentSignature->sign($data);
        }

        if (CreditNote::whereNoteMonth(now()->format('Y'))->count() == 1) {
            $data = $note->date . ';' . $note->created_at->toDateTimeLocalString() . ';' . $note->note_no . ';' . $note->total . ';';

            $note->unique_hash = $documentSignature->sign($data);
        }

        $note->save();
    }
}
