<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ReagentConsumption;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class InventoryCatalogueRead
{
    public function __construct(private readonly InventoryCatalogueAccess $access) {}

    /** @return list<string> */
    public function allowedTypes(?User $user, string $ability = 'view'): array
    {
        $types = $this->access->allowedTypes($user, 'view');

        return $ability === 'view' ? $types : array_values(array_intersect($types, $this->access->allowedTypes($user, $ability)));
    }

    /** @param Builder<InventoryItem> $query @return Builder<InventoryItem> */
    public function constrainItems(Builder $query, int $labId, ?User $user, string $ability = 'view'): Builder
    {
        return $query->where($query->qualifyColumn('lab_id'), $labId)
            ->whereHas('category', fn (Builder $categories): Builder => $categories->withTrashed()
                ->whereIn('inventory_type', $this->allowedTypes($user, $ability)));
    }

    /** @return Builder<InventoryItem> */
    public function items(int $labId, ?User $user, string $ability = 'view'): Builder
    {
        return $this->constrainItems(InventoryItem::query(), $labId, $user, $ability);
    }

    /** @return Builder<Inventory> */
    public function stock(int $labId, ?User $user, string $ability = 'view'): Builder
    {
        return $this->positions(Inventory::forLaboratory($labId), $labId, $user, $ability);
    }

    /** @return Builder<InventoryTransaction> */
    public function transactions(int $labId, ?User $user, string $ability = 'view'): Builder
    {
        return $this->positions(InventoryTransaction::forLaboratory($labId), $labId, $user, $ability);
    }

    /** @return Builder<ReagentConsumption> */
    public function consumptions(int $labId, ?User $user, string $ability = 'view'): Builder
    {
        return $this->positions(ReagentConsumption::forLaboratory($labId), $labId, $user, $ability, true);
    }

    /** @template T of \Illuminate\Database\Eloquent\Model @param Builder<T> $query @param array<string,mixed> $filters @return Builder<T> */
    public function filter(Builder $query, array $filters, string $kind): Builder
    {
        if ($kind !== 'stock') {
            $date = $query->qualifyColumn($kind === 'consumption' ? 'date' : 'created_at');
            $query->when($filters['date_from'] ?? null, fn (Builder $query, string $value): Builder => $query->whereDate($date, '>=', $value))
                ->when($filters['date_to'] ?? null, fn (Builder $query, string $value): Builder => $query->whereDate($date, '<=', $value));
        }
        foreach (['item_id' => $kind === 'consumption' ? 'reagent_id' : 'item_id', 'warehouse_id' => 'warehouse_id'] as $filter => $column) {
            $query->when($filters[$filter] ?? null, fn (Builder $query, $value): Builder => $query->where($query->qualifyColumn($column), $value));
        }
        if ($kind === 'transaction') {
            $query->when($filters['type_id'] ?? null, fn (Builder $query, $value): Builder => $query->where($query->qualifyColumn('type_id'), $value));
        }
        if ($kind === 'consumption') {
            $query->when($filters['user_id'] ?? null, fn (Builder $query, $value): Builder => $query->where($query->qualifyColumn('user_id'), $value));
        }
        $query->when($filters['category_id'] ?? null, fn (Builder $query, $value): Builder => $query->whereHas('item',
            fn (Builder $items): Builder => $items->withTrashed()->where('category_id', $value)));

        return $query->when($filters['search'] ?? null, function (Builder $query, string $search) use ($kind): void {
            $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($search)).'%';
            $query->where(function (Builder $query) use ($kind, $pattern): void {
                if ($kind === 'consumption') {
                    $query->whereLike('reagent_name', $pattern)->orWhereLike('used_by', $pattern)->orWhereLike('remarks', $pattern);
                } else {
                    $query->whereHas('item', fn (Builder $items): Builder => $items->withTrashed()
                        ->where(fn (Builder $items): Builder => $items->whereLike('name', $pattern)->orWhereLike('code', $pattern)));
                    if ($kind === 'transaction') {
                        $query->orWhereHas('user', fn (Builder $users): Builder => $users->withTrashed()->whereLike('name', $pattern));
                    }
                }
            });
        });
    }

    /** @template T of \Illuminate\Database\Eloquent\Model @param Builder<T> $query @param array<string,mixed> $filters */
    public function dailyAverage(Builder $query, array $filters, string $dateColumn, ?string $quantityColumn = null, int $precision = 4): float
    {
        $column = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($dateColumn));
        $window = (clone $query)->toBase()->selectRaw('MIN('.$column.') as first_date, MAX('.$column.') as last_date')->first();
        if ($window->first_date === null) {
            return 0.0;
        }
        $from = Carbon::parse($filters['date_from'] ?? $window->first_date)->startOfDay();
        $to = Carbon::parse($filters['date_to'] ?? $window->last_date)->startOfDay();
        $total = $quantityColumn === null ? (clone $query)->count() : (float) (clone $query)->sum($quantityColumn);

        return round($total / ((int) $from->diffInDays($to, true) + 1), $precision);
    }

    /** @template T of \Illuminate\Database\Eloquent\Model @param Builder<T> $query @return Builder<T> */
    private function positions(Builder $query, int $labId, ?User $user, string $ability, bool $nullableWarehouse = false): Builder
    {
        return $query->whereHas('item', fn (Builder $items): Builder => $this->constrainItems($items->withTrashed(), $labId, $user, $ability))
            ->where(function (Builder $positions) use ($labId, $nullableWarehouse): void {
                $positions->whereHas('warehouse', fn (Builder $warehouses): Builder => $warehouses->withTrashed()->where('lab_id', $labId));
                if ($nullableWarehouse) {
                    $positions->orWhereNull($positions->qualifyColumn('warehouse_id'));
                }
            });
    }
}
