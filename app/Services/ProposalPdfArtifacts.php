<?php

namespace App\Services;

use App\Models\VAPProposal;
use Illuminate\Container\Attributes\Give;
use Illuminate\Database\DatabaseTransactionRecord;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProposalPdfArtifacts
{
    public function __construct(
        #[Give('db.transactions')] private readonly DatabaseTransactionsManager $transactions
    ) {}

    public function store(VAPProposal $proposal, string $filename, string $content): string
    {
        $path = "vap-proposals/{$proposal->id}/".str()->uuid()."/{$filename}";
        $records = $this->transactions->getPendingTransactions()
            ->filter(fn (DatabaseTransactionRecord $record): bool => $record->connection === DB::connection()->getName());
        abort_unless($records->isNotEmpty(), 409, 'É necessária uma transacção para registar o documento.');

        /** Rollback callbacks on staged child records are discarded on an ancestor rollback. */
        foreach ($records as $record) {
            $record->addCallbackForRollback(fn () => $this->discardAttempt($path));
        }
        abort_unless(Storage::put($path, $content), 409, 'Não foi possível guardar o documento da proposta.');

        return $path;
    }

    public function removeAfterCommit(int $proposalId, ?string $path): void
    {
        if (! $path || ! str_starts_with($path, "vap-proposals/{$proposalId}/") || str_contains($path, '..')) {
            return;
        }
        DB::afterCommit(function () use ($path): void {
            try {
                if (! VAPProposal::withoutGlobalScope('proposal_laboratory')->withTrashed()->where('file_path', $path)->exists()
                    && Storage::exists($path) && ! Storage::delete($path)) {
                    throw new RuntimeException('Não foi possível remover o documento desactualizado da proposta.');
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }

    private function discardAttempt(string $path): void
    {
        try {
            if (Storage::exists($path) && ! Storage::delete($path)) {
                throw new RuntimeException('Não foi possível remover o documento de uma operação cancelada.');
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
