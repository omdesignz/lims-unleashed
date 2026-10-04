<?php

namespace App\Models\Concerns;

use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Database\Eloquent\Builder;

/**
 * A quality certificate belongs to the laboratory that received its sample: it is
 * reachable from staff HTTP requests only through a collection product whose sample
 * entry is owned by the active, directly joined laboratory, archived accessions
 * included. Portal token routes and
 * console jobs carry no staff context and must authorize on their own.
 */
trait BelongsToSampleLaboratory
{
    public static function bootBelongsToSampleLaboratory(): void
    {
        static::addGlobalScope('sample_laboratory', function (Builder $query): void {
            if (! request()->attributes->has('proposal_laboratory_id')) {
                return;
            }

            $labId = (int) request()->attributes->get('proposal_laboratory_id');
            $query->whereIn(
                $query->qualifyColumn('collection_id'),
                app(LaboratoryWorkflowOwnership::class)->collectionProductsForLaboratory($labId, true)->withTrashed()->select('collection_product.id')
            );
        });
    }
}
