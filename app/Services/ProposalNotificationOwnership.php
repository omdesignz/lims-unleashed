<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\User;
use App\Models\VAPProposal;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;

class ProposalNotificationOwnership
{
    public function __construct(private readonly LaboratoryWorkflowOwnership $laboratoryOwnership) {}

    /** @return array<string, scalar|null> */
    public function context(Proposal|VAPProposal $proposal): array
    {
        return [
            'proposal_id' => $proposal->getKey(),
            'lab_id' => $proposal->lab_id,
            'customer_id' => $proposal->customer_id,
            'warehouse_id' => $proposal->warehouse_id,
            'proposal_owner_id' => $proposal->user_id,
            'proposal_hash' => $proposal->unique_hash,
        ];
    }

    /** @param array<string, mixed> $context */
    public function resolve(array $context): ?VAPProposal
    {
        $proposalId = filter_var($context['proposal_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $labId = filter_var($context['lab_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (! $proposalId || ! $labId) {
            return null;
        }

        $proposal = VAPProposal::query()->withoutGlobalScope('proposal_laboratory')
            ->where('lab_id', $labId)->whereHas('lab')->whereHas('customer')
            ->where(fn (Builder $query): Builder => $query->whereNull('warehouse_id')
                ->orWhereHas('warehouse', fn (Builder $warehouse): Builder => $warehouse->whereColumn('warehouses.customer_id', 'proposals.customer_id')))
            ->find($proposalId);
        if (! $proposal) {
            return null;
        }

        foreach ($this->context($proposal) as $field => $value) {
            if (array_key_exists($field, $context)
                && ((! is_scalar($context[$field]) && $context[$field] !== null)
                    || (string) $context[$field] !== (string) $value)) {
                return null;
            }
        }

        return $proposal;
    }

    public function canReceive(object $recipient, VAPProposal $proposal): bool
    {
        if ($recipient instanceof User) {
            return (int) $recipient->getKey() === (int) $proposal->user_id
                && $this->laboratoryOwnership->eligibleUsers((int) $proposal->lab_id)->whereKey($recipient->getKey())->exists();
        }
        if ($recipient instanceof Warehouse && (int) $recipient->getKey() === (int) $proposal->warehouse_id) {
            return filled($proposal->unique_hash) && Warehouse::query()->whereKey($recipient->getKey())
                ->where('customer_id', $proposal->customer_id)->exists();
        }

        return false;
    }
}
