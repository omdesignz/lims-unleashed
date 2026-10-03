<?php

namespace App\Support;

use App\Models\CollectionProduct;
use App\Models\QualityCertificate;
use App\Models\VAPProposal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalysisReportService
{
    /**
     * Returns the live certificate of a collection product, creating it once. The
     * collection product row is locked and a partial unique index backs the rule, so
     * repeated or concurrent requests reuse the same certificate.
     */
    public function ensureForCollectionProduct(CollectionProduct $collectionProduct, int $userId): QualityCertificate
    {
        return DB::transaction(function () use ($collectionProduct, $userId): QualityCertificate {
            $locked = CollectionProduct::query()->with('code')->lockForUpdate()->findOrFail($collectionProduct->id);
            $existing = QualityCertificate::query()->where('collection_id', $locked->id)->first();

            if ($existing) {
                return $existing;
            }

            return QualityCertificate::query()->create([
                'collection_id' => $locked->id,
                'user_id' => $userId,
                'customer_id' => $locked->customer_id,
                'warehouse_id' => $locked->warehouse_id,
                'product_id' => $locked->product_id,
                'cl_id' => $locked->code?->id,
                'code' => now('Africa/Luanda')->format('YmdHis').'-'.$locked->id,
                'obs' => $locked->obs,
                'status' => (bool) $locked->status,
                'file_path' => null,
            ]);
        });
    }

    /**
     * @return Collection<int, QualityCertificate>
     */
    public function ensureForProposal(VAPProposal $proposal, int $userId): Collection
    {
        $proposal->loadMissing([
            'sampleEntries.collectionProduct.code.samples.analysis',
            'sampleEntries.collectionProduct.code.samples.results',
        ]);

        return $proposal->sampleEntries
            ->pluck('collectionProduct')
            ->filter()
            ->filter(function (CollectionProduct $collectionProduct): bool {
                $laboratorySamples = $collectionProduct->code?->samples ?? collect();
                $analyses = $laboratorySamples->pluck('analysis')->filter();
                $results = $laboratorySamples->flatMap->results;

                return $analyses->isNotEmpty()
                    && $results->isNotEmpty()
                    && $results->every(fn ($result): bool => filled($result->approved_date));
            })
            ->map(fn (CollectionProduct $collectionProduct): QualityCertificate => $this->ensureForCollectionProduct(
                $collectionProduct,
                $userId
            ))
            ->values();
    }
}
