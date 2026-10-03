<?php

namespace App\Actions;

use App\Models\VAPSampleEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListLaboratorySamples
{
    /**
     * @param  array{search?: ?string, status?: ?string, page?: int|string, per_page?: int|string}  $filters
     * @return LengthAwarePaginator<int, VAPSampleEntry>
     */
    public function execute(int $labId, array $filters): LengthAwarePaginator
    {
        return VAPSampleEntry::query()
            ->where('lab_id', $labId)
            ->select(['id', 'name', 'code', 'sample_type', 'status', 'customer_id', 'received_at', 'retention_due_at', 'created_at'])
            ->with('customer:id,name')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $pattern = '%'.addcslashes($search, '%_\\\\').'%';
                $query->where(fn (Builder $matches) => $matches
                    ->whereLike('name', $pattern)->orWhereLike('code', $pattern));
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 25))
            ->withQueryString();
    }
}
