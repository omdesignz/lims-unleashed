<?php

namespace App\Services;

use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\CounterAnalysis;
use App\Models\DirectCollection;
use App\Models\LabCode;
use App\Models\ProgrammedCollection;
use App\Models\Result;
use App\Models\Sample;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Notifications\SampleRetentionDeadlineNotification;
use App\Notifications\SampleTrackingNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

class LaboratoryWorkflowOwnership
{
    /** @return Builder<CollectionProduct> */
    public function collectionAccessionsForLaboratory(int $labId, string $type, bool $includeArchivedEntries = false): Builder
    {
        $subject = match ($type) {
            'direct' => DirectCollection::class,
            'programmed' => ProgrammedCollection::class,
            default => null,
        };

        if ($subject === null) {
            return CollectionProduct::query()->whereRaw('1 = 0');
        }

        return $this->collectionProductsForLaboratory($labId, $includeArchivedEntries)
            ->whereHas('collection', fn (Builder $collection): Builder => $collection
                ->where('collectionable_type', $type)
                ->whereColumn('collections.customer_id', 'collection_product.customer_id')
                ->whereColumn('collections.warehouse_id', 'collection_product.warehouse_id')
                ->whereHasMorph('collectionable', [$subject]));
    }

    /** @return Builder<CollectionProduct> */
    public function collectionProductsForLaboratory(int $labId, bool $includeArchivedEntries = false): Builder
    {
        if ($labId <= 0) {
            return CollectionProduct::query()->whereRaw('1 = 0');
        }

        return CollectionProduct::query()
            ->whereHas('sampleEntry', fn (Builder $entry): Builder => $entry
                ->when($includeArchivedEntries, fn (Builder $entry): Builder => $entry->withTrashed())
                ->where('lab_id', $labId)
                ->whereHas('lab')
                ->where(fn (Builder $customer): Builder => $customer
                    ->whereNull('collection_product.customer_id')
                    ->orWhereColumn('sample_entries.customer_id', 'collection_product.customer_id')))
            ->whereNotExists(function (QueryBuilder $entries) use ($labId): void {
                $entries->selectRaw('1')->from('sample_entries')
                    ->whereColumn('sample_entries.collection_product_id', 'collection_product.id')
                    ->where(fn (QueryBuilder $conflict): QueryBuilder => $conflict
                        ->whereNull('sample_entries.lab_id')
                        ->orWhere('sample_entries.lab_id', '!=', $labId));
            });
    }

    /** @return Builder<LabCode> */
    public function labCodesForLaboratory(int $labId): Builder
    {
        return LabCode::query()->forLaboratory($labId)
            ->whereIn('lab_codes.collection_id', CollectionProduct::query()
                ->whereHas('sampleEntry', function (Builder $entry) use ($labId): void {
                    $entry->where('lab_id', $labId)->whereHas('lab')
                        ->where(fn (Builder $customer): Builder => $customer
                            ->whereNull('collection_product.customer_id')
                            ->orWhereColumn('sample_entries.customer_id', 'collection_product.customer_id'));
                })->select('collection_product.id'));
    }

    /** @return Builder<Sample> */
    public function samplesForLaboratory(int $labId): Builder
    {
        return Sample::query()->whereIn('samples.cl_id', $this->labCodesForLaboratory($labId)->select('lab_codes.id'));
    }

    /** @return Builder<Analysis> */
    public function analysesForLaboratory(int $labId): Builder
    {
        return Analysis::query()->whereIn('analysis.sample_id', $this->samplesForLaboratory($labId)
            ->whereColumn('samples.cl_id', 'analysis.cl_id')->select('samples.id'));
    }

    /** @return Builder<Result> */
    public function resultsForLaboratory(int $labId): Builder
    {
        return Result::query()->whereIn('results.sample_id', $this->samplesForLaboratory($labId)->select('samples.id'));
    }

    /** @return Builder<CounterAnalysis> */
    public function counterAnalysesForLaboratory(int $labId): Builder
    {
        return CounterAnalysis::query()
            ->whereIn('counter_analysis.sample_id', $this->samplesForLaboratory($labId)
                ->whereColumn('samples.cl_id', 'counter_analysis.cl_id')->select('samples.id'))
            ->whereIn('counter_analysis.analysis_id', $this->analysesForLaboratory($labId)->select('analysis.id'))
            ->whereIn('counter_analysis.result_id', $this->resultsForLaboratory($labId)
                ->whereHas('sample.analysis', fn (Builder $analysis): Builder => $analysis
                    ->whereColumn('analysis.id', 'counter_analysis.analysis_id'))
                ->whereHas('sample.collection', fn (Builder $code): Builder => $code
                    ->whereIn('lab_codes.collection_id', LabCode::query()
                        ->whereColumn('lab_codes.id', 'counter_analysis.cl_id')->select('lab_codes.collection_id')))
                ->select('results.id'));
    }

    /** @param array<string, mixed> $context */
    public function resolve(array $context): ?VAPSampleEntry
    {
        [$type, $id] = $this->source($context);

        $entry = match ($type) {
            'sample_entry' => $this->sampleEntry($id),
            'collection_product' => $this->collectionProduct($id),
            'lab_code' => $this->labCode($id),
            'result' => $this->result($id),
            'counter_analysis' => $this->counterAnalysis($id),
            default => null,
        };

        if (! $entry) {
            return null;
        }

        foreach (['lab_id' => $entry->lab_id, 'sample_entry_id' => $entry->id, 'collection_product_id' => $entry->collection_product_id] as $key => $value) {
            if (isset($context[$key]) && (int) $context[$key] !== (int) $value) {
                return null;
            }
        }

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, scalar|null>
     */
    public function context(array $context, VAPSampleEntry $entry): array
    {
        [$type, $id] = $this->source($context);

        return [
            'notification_source_type' => $type,
            'notification_source_id' => $id,
            'sample_entry_id' => $entry->id,
            'lab_id' => $entry->lab_id,
            'lab_name' => $entry->lab->name,
        ];
    }

    /** @return Builder<User> */
    public function eligibleUsers(int $labId): Builder
    {
        return User::query()
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->whereIn('users.id', DB::table('lab_user')->where('lab_id', $labId)->select('user_id'));
    }

    public function canReceive(object $recipient, VAPSampleEntry $entry): bool
    {
        if ($recipient instanceof User) {
            return $this->eligibleUsers((int) $entry->lab_id)->whereKey($recipient->getKey())->exists();
        }

        if ($recipient instanceof Warehouse && (int) $entry->warehouse_id === (int) $recipient->getKey()) {
            return Warehouse::query()->whereKey($recipient->getKey())
                ->where('customer_id', $entry->customer_id)->exists();
        }

        return false;
    }

    /**
     * Stored notices retain their issued laboratory ownership, including after source archiving.
     * Unowned legacy laboratory notices stay persisted but must not expose private details.
     *
     * @param  Builder<DatabaseNotification>  $query
     */
    public function scopeStoredNotifications(Builder $query, User $viewer): Builder
    {
        $labIds = $viewer->is_active && $viewer->email_verified_at
            ? VAPLab::query()->whereIn('id', DB::table('lab_user')->where('user_id', $viewer->id)->select('lab_id'))
                ->pluck('id')->map(fn (int $id): string => (string) $id)->all()
            : [];

        $key = DB::raw("notifications.data::jsonb ->> 'key'");
        $labId = DB::raw("notifications.data::jsonb ->> 'lab_id'");
        $contextLabId = DB::raw("notifications.data::jsonb -> 'context' ->> 'lab_id'");

        return $query->whereNotIn('notifications.type', [SampleTrackingNotification::class, SampleRetentionDeadlineNotification::class])
            ->where(fn (Builder $visibility): Builder => $visibility->whereNull($labId)->orWhereIn($labId, $labIds))
            ->where(fn (Builder $visibility): Builder => $visibility->whereNull($contextLabId)->orWhereIn($contextLabId, $labIds))
            ->where(fn (Builder $visibility): Builder => $visibility->whereNull($key)
                ->orWhere($key, 'not like', 'lab.%')->orWhereIn($contextLabId, $labIds));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{string|null, int}
     */
    private function source(array $context): array
    {
        if (isset($context['notification_source_type'], $context['notification_source_id'])) {
            return [(string) $context['notification_source_type'], (int) $context['notification_source_id']];
        }

        foreach ([
            'counter_analysis_id' => 'counter_analysis',
            'result_id' => 'result',
            'lab_code_id' => 'lab_code',
            'sample_entry_id' => 'sample_entry',
            'sample_id' => 'sample_entry',
            'collection_product_id' => 'collection_product',
        ] as $key => $type) {
            if (isset($context[$key])) {
                return [$type, (int) $context[$key]];
            }
        }

        return [null, 0];
    }

    private function sampleEntry(int $id): ?VAPSampleEntry
    {
        $entry = VAPSampleEntry::query()->with('lab')->whereHas('lab')->find($id);

        if (! $entry || ! $entry->collection_product_id) {
            return $entry;
        }

        $product = CollectionProduct::query()->find($entry->collection_product_id);

        if (! $product || ($product->customer_id !== null && (int) $product->customer_id !== (int) $entry->customer_id)) {
            return null;
        }

        $hasConflictingOwnership = VAPSampleEntry::withTrashed()
            ->where('collection_product_id', $entry->collection_product_id)
            ->where(fn (Builder $query): Builder => $query->whereNull('lab_id')->orWhere('lab_id', '!=', $entry->lab_id))
            ->exists();

        return $hasConflictingOwnership ? null : $entry;
    }

    private function collectionProduct(int $id): ?VAPSampleEntry
    {
        $entryId = VAPSampleEntry::query()->where('collection_product_id', $id)->value('id');

        return $entryId ? $this->sampleEntry((int) $entryId) : null;
    }

    private function labCode(int $id): ?VAPSampleEntry
    {
        $code = LabCode::query()->find($id);
        $entry = $code ? $this->collectionProduct((int) $code->collection_id) : null;

        if (! $entry || ! LabCode::query()->forLaboratory((int) $entry->lab_id)->whereKey($id)->exists()) {
            return null;
        }

        return $entry;
    }

    private function result(int $id): ?VAPSampleEntry
    {
        $result = Result::query()->with('sample')->find($id);

        return $result?->sample ? $this->labCode((int) $result->sample->cl_id) : null;
    }

    private function counterAnalysis(int $id): ?VAPSampleEntry
    {
        $counterAnalysis = CounterAnalysis::query()->with('sample')->find($id);

        if (! $counterAnalysis?->sample || (int) $counterAnalysis->sample->cl_id !== (int) $counterAnalysis->cl_id) {
            return null;
        }

        $entry = $this->result((int) $counterAnalysis->result_id);
        $counterEntry = $this->labCode((int) $counterAnalysis->cl_id);

        return $entry && $counterEntry && $entry->is($counterEntry) ? $entry : null;
    }
}
