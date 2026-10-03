<?php

namespace App\Models\Concerns;

use App\Models\VAPLab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Staff HTTP queries are restricted to the directly joined, active laboratory.
 * Public token routes and console jobs have no staff context; their callers must
 * provide their own authorization. Unassigned legacy records are never shared.
 */
trait BelongsToProposalLaboratory
{
    public static function bootBelongsToProposalLaboratory(): void
    {
        static::addGlobalScope('proposal_laboratory', function (Builder $query): void {
            if (request()->attributes->has('proposal_laboratory_id')) {
                $query->where($query->qualifyColumn('lab_id'), request()->attributes->get('proposal_laboratory_id'));
            }
        });

        static::creating(function (Model $proposal): void {
            if (request()->attributes->has('proposal_laboratory_id')) {
                $labId = request()->attributes->get('proposal_laboratory_id');
                abort_unless($labId > 0, 403);
                $proposal->lab_id = $labId;
            }
        });

        static::updating(function (Model $proposal): void {
            if ($proposal->isDirty('lab_id')) {
                throw new LogicException('Proposal laboratory ownership cannot be reassigned.');
            }
        });
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }
}
