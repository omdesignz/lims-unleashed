<?php

namespace App\Actions;

use App\Models\VAPProposal;
use App\Models\VAPProposalItem;
use App\Services\ProposalNotificationOwnership;
use App\Services\ProposalPdfArtifacts;
use App\Services\ProposalStaffAccess;
use App\Support\ProposalAuthoringPayload;
use App\Support\ProposalWorkflowNotifier;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReviseProposal
{
    public function __construct(
        private readonly ProposalStaffAccess $access,
        private readonly ProposalNotificationOwnership $ownership,
        private readonly ProposalAuthoringPayload $payload,
        private readonly ProposalPdfArtifacts $artifacts,
        private readonly ProposalWorkflowNotifier $notifier
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(int $labId, int $userId, VAPProposal $snapshot, array $data): VAPProposal
    {
        return DB::transaction(function () use ($labId, $userId, $snapshot, $data): VAPProposal {
            ['proposal' => $proposal, 'operator' => $operator] = $this->access->lock($labId, $userId, $snapshot, 'edit_proposals');
            $source = clone $proposal;
            abort_unless(in_array($proposal->status, ['PENDING', 'SENT', 'VIEWED', 'REJECTED'], true), 409,
                'A proposta não pode ser actualizada no estado actual.');
            $validated = $this->payload->validate($data, false);
            $items = $this->payload->normalizeItems($validated['items']);
            $totals = $this->payload->totals($items);
            $oldItems = $proposal->items()->orderBy('id')->lockForUpdate()->get();
            $proposal->setRelation('items', $oldItems);
            $oldItemSnapshots = $oldItems->map(fn (VAPProposalItem $item): array => $item->getRawOriginal())->all();
            $oldValues = $proposal->only(['obs', 'service_location', 'tolerance_days', 'total', 'sub_total', 'tax', 'discount', 'withhold_tax', 'use_matrix_price']);
            $previousPath = $proposal->file_path;
            $newValues = [
                'service_location' => $validated['service_location'], 'obs' => $validated['obs'] ?? null,
                'tolerance_days' => $validated['tolerance_days'], 'withhold_tax' => $validated['withhold_tax'] ?? false,
                'use_matrix_price' => $validated['use_matrix_price'] ?? true,
                'sub_total' => $totals['sub_total'], 'total' => $totals['total'],
            ];
            $proposal->fill([...$newValues, 'status' => 'REVISED', 'is_original' => false, 'file_path' => null]);
            abort_unless($proposal->save(), 409, 'Não foi possível registar a revisão.');
            foreach ($oldItems as $item) {
                abort_unless($item->delete(), 409, 'Não foi possível arquivar os itens anteriores.');
            }
            $itemIds = [];
            foreach ($items as $item) {
                $record = $proposal->items()->make($item);
                abort_unless($record->save(), 409, 'Não foi possível registar os novos itens.');
                $itemIds[] = $record->id;
            }
            $audit = activity()->performedOn($proposal)->causedBy($operator)->withProperties([
                'old_values' => [...$oldValues, 'items_count' => $oldItems->count()],
                'new_values' => [...$newValues, 'tax' => $totals['tax'], 'discount' => $totals['discount'], 'items_count' => count($items)],
                'reason' => $validated['revision_reason'],
                'old_items' => $oldItemSnapshots,
                'item_changes' => ['removed' => $oldItems->modelKeys(), 'added' => count($itemIds)],
            ])->event('revised')->log('revised');
            abort_unless($audit?->exists, 409, 'Não foi possível registar o histórico da revisão.');
            $proposal->unsetRelation('items');
            $this->payload->validate($validated, false);
            $this->access->lock($labId, $userId, $source, 'edit_proposals');
            $context = $this->ownership->context($proposal);
            $this->artifacts->removeAfterCommit($proposal->id, $previousPath);
            DB::afterCommit(function () use ($context, $itemIds): void {
                try {
                    $current = $this->ownership->resolve($context);
                    if ($current && $current->status === 'REVISED'
                        && $current->items()->orderBy('id')->pluck('id')->all() === $itemIds) {
                        $this->notifier->notifyRevised($current);
                    }
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

            return $proposal;
        }, 3);
    }
}
