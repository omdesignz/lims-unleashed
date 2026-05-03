<?php

namespace App\Console\Commands;

use App\Models\Proposal;
use App\Support\DocumentSignature;
use Illuminate\Console\Command;

class signProposalWithHash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sign-proposal-with-hash {proposal}';

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
        $proposal = Proposal::findOrFail($this->argument('proposal'));

        if (Proposal::whereProposalYear(now()->format('Y'))->count() !== 1) {
            $prev_hash = Proposal::where('id', '<', $proposal->id)->orderBy('id', 'desc')->first()->unique_hash;
            $data = $proposal->created_at->format('Y-m-d') . ';' . $proposal->created_at->toDateTimeLocalString() . ';' . $proposal->proposal_no . ';' . $proposal->total . ';' . $prev_hash;

            $proposal->unique_hash = $documentSignature->sign($data);
        }

        if (Proposal::whereProposalYear(now()->format('Y'))->count() == 1) {
            $data = $proposal->created_at->format('Y-m-d') . ';' . $proposal->created_at->toDateTimeLocalString() . ';' . $proposal->proposal_no . ';' . $proposal->total . ';';

            $proposal->unique_hash = $documentSignature->sign($data);
        }

        $proposal->save();
    }
}
