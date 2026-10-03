<?php

namespace App\Models\Concerns;

use App\Models\VAPProposal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Applies the staff proposal boundary to items, agreements and agreement logs. */
trait BelongsToPrivateProposal
{
    public static function bootBelongsToPrivateProposal(): void
    {
        static::addGlobalScope('proposal_laboratory', function (Builder $query): void {
            if (request()->attributes->has('proposal_laboratory_id')) {
                $query->whereIn($query->qualifyColumn('proposal_id'), VAPProposal::withTrashed()->select('id'));
            }
        });

        static::saving(function (Model $record): void {
            if (request()->attributes->has('proposal_laboratory_id')) {
                abort_unless(VAPProposal::query()->whereKey($record->proposal_id)->exists(), 404);
            }
        });
    }
}
