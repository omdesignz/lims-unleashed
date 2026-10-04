<?php

namespace App\Services;

use App\Models\LabNetwork;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LabNetworkStock
{
    public function __construct(private readonly LabNetworkAccess $access) {}

    /** @return list<int> */
    public function laboratoryIds(User $user, LabNetwork $network): array
    {
        $network = $network->fresh();
        abort_unless($network && $user->fresh()?->is_active, 403);
        $ids = $this->access->visibleLabs($user, $network)->pluck('labs.id')->map(fn (int|string $id): int => (int) $id)->all();
        abort_if($ids === [], 403);

        return $ids;
    }

    /** @param list<int> $labIds */
    private function positions(array $labIds): Builder
    {
        return DB::table('inventory as stock')
            ->join('i_warehouses as warehouse', 'warehouse.id', '=', 'stock.warehouse_id')
            ->join('labs as lab', 'lab.id', '=', 'stock.lab_id')
            ->join('i_items as item', 'item.id', '=', 'stock.item_id')
            ->leftJoin('i_units as unit', 'unit.id', '=', 'item.unit_id')
            ->whereIn('stock.lab_id', $labIds)
            ->whereColumn('warehouse.lab_id', 'stock.lab_id')->whereColumn('item.lab_id', 'stock.lab_id')
            ->whereNull('stock.deleted_at')->whereNull('warehouse.deleted_at')
            ->whereNull('item.deleted_at')->whereNull('lab.deleted_at');
    }

    /**
     * Batches partition the recorded balance; outgoing transfers have already left that balance.
     *
     * @param  list<int>  $labIds
     */
    public function rows(array $labIds): Builder
    {
        $batchTotals = DB::table('i_inventory_batches')->whereIn('lab_id', $labIds)
            ->selectRaw('inventory_id, lab_id, SUM(qty_remaining) as quantity')->groupBy('inventory_id', 'lab_id');
        $outgoing = DB::table('i_transfers')->whereIn('lab_id', $labIds)->whereNull('deleted_at')->whereNull('received_date')
            ->selectRaw('lab_id, item_id, source_id, SUM(qty) as quantity')->groupBy('lab_id', 'item_id', 'source_id');
        $base = $this->positions($labIds)->leftJoinSub($batchTotals, 'batches', function (JoinClause $join): void {
            $join->on('batches.inventory_id', '=', 'stock.id')->on('batches.lab_id', '=', 'stock.lab_id');
        })->leftJoinSub($outgoing, 'outgoing', function (JoinClause $join): void {
            $join->on('outgoing.lab_id', '=', 'stock.lab_id')->on('outgoing.item_id', '=', 'stock.item_id')->on('outgoing.source_id', '=', 'stock.warehouse_id');
        });
        $columns = ['stock.id as position_id', 'stock.lab_id', 'stock.warehouse_id', 'item.name', 'item.code',
            'lab.name as lab_name', 'warehouse.name as warehouse_name', 'unit.code as unit', 'stock.status', 'stock.reorder_point', 'stock.updated_at'];
        $consistent = 'COALESCE(batches.quantity, 0) <= stock.qty_available';
        $unbatched = (clone $base)->select($columns)
            ->selectRaw("CONCAT(stock.id, '-free') as id, item.lot, item.reagent_expiry_date as expiry_date, (CASE WHEN $consistent THEN stock.qty_available - COALESCE(batches.quantity, 0) ELSE stock.qty_available END)::numeric(18,4) as physical_quantity, COALESCE(outgoing.quantity, 0)::numeric(18,4) as outgoing_quantity, ($consistent) as balance_consistent")
            ->whereRaw("(stock.qty_available > COALESCE(batches.quantity, 0) OR COALESCE(batches.quantity, 0) = 0 OR COALESCE(outgoing.quantity, 0) > 0 OR NOT ($consistent))");
        $batched = (clone $base)->join('i_inventory_batches as batch', function (JoinClause $join): void {
            $join->on('batch.inventory_id', '=', 'stock.id')->on('batch.lab_id', '=', 'stock.lab_id');
        })->where('batch.qty_remaining', '>', 0)->whereRaw($consistent)->select($columns)
            ->selectRaw("CONCAT(stock.id, '-batch-', batch.id) as id, batch.batch_number as lot, LEAST(batch.expiry_date, item.reagent_expiry_date) as expiry_date, batch.qty_remaining as physical_quantity, 0::numeric(18,4) as outgoing_quantity, ($consistent) as balance_consistent");
        $parts = DB::query()->fromSub($unbatched->unionAll($batched), 'parts')->select('parts.*')
            ->selectRaw("CASE WHEN NOT balance_consistent THEN 'inconsistent' WHEN expiry_date < ? THEN 'expired' WHEN status = 'COMMITED' THEN 'reserved' WHEN status IN ('AVAILABLE', 'ON_HAND', 'RECEIVED') THEN 'available' ELSE 'blocked' END as availability_state", [now()->toDateString()]);

        return DB::query()->fromSub($parts, 'availability')->select('availability.*')
            ->selectRaw("CASE WHEN availability_state = 'available' THEN physical_quantity ELSE 0::numeric(18,4) END as available_quantity")
            ->selectRaw("CASE WHEN availability_state = 'reserved' THEN physical_quantity ELSE 0::numeric(18,4) END as reserved_quantity")
            ->selectRaw("CASE WHEN availability_state IN ('expired', 'blocked', 'inconsistent') THEN physical_quantity ELSE 0::numeric(18,4) END as blocked_quantity");
    }

    /** @param list<int> $labIds @param array<string, mixed> $filters */
    public function search(array $labIds, array $filters): Builder
    {
        $query = DB::query()->fromSub($this->rows($labIds), 'materials')->select('materials.*');
        foreach (['lab_id', 'warehouse_id'] as $key) {
            $query->when($filters[$key] ?? null, fn (Builder $query, int|string $id): Builder => $query->where($key, $id));
        }
        $query->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
            $pattern = '%'.addcslashes($filters['search'], '%_\\').'%';
            $query->where(fn (Builder $query): Builder => $query->whereLike('name', $pattern)->orWhereLike('code', $pattern)->orWhereLike('lot', $pattern));
        })->when(filled($filters['lot'] ?? null), fn (Builder $query): Builder => $query->whereLike('lot', '%'.addcslashes($filters['lot'], '%_\\').'%'))
            ->when($filters['expiry_from'] ?? null, fn (Builder $query, string $date): Builder => $query->where('expiry_date', '>=', $date))
            ->when($filters['expiry_to'] ?? null, fn (Builder $query, string $date): Builder => $query->where('expiry_date', '<=', $date))
            ->when($filters['available'] ?? false, fn (Builder $query): Builder => $query->where('available_quantity', '>', 0));

        return $query->orderBy('name')->orderBy('lab_name')->orderBy('warehouse_name')->orderBy('id');
    }

    /** @param list<int> $labIds @return Collection<int, object> */
    public function warehouses(array $labIds): Collection
    {
        return DB::table('i_warehouses')->whereIn('lab_id', $labIds)->whereNull('deleted_at')->orderBy('name')->orderBy('id')->get(['id', 'lab_id', 'name']);
    }

    /** @param list<int> $labIds @return Collection<int, array<string, mixed>> */
    public function summaries(array $labIds): Collection
    {
        $positions = DB::query()->fromSub($this->rows($labIds), 'parts')
            ->selectRaw('lab_id, position_id, MAX(reorder_point) as reorder_point, SUM(available_quantity) as available_quantity')
            ->groupBy('lab_id', 'position_id');
        $summary = DB::query()->fromSub($positions, 'positions')
            ->selectRaw('lab_id, COUNT(*) as positions, SUM(CASE WHEN available_quantity <= reorder_point THEN 1 ELSE 0 END) as stock_alerts')
            ->groupBy('lab_id')->get()->keyBy('lab_id');
        $samples = DB::table('sample_entries')->whereIn('lab_id', $labIds)->whereNull('deleted_at')
            ->whereIn('status', ['POR_INICIAR', 'EN_PROGRESO', 'EN_PAUSA'])
            ->selectRaw('lab_id, COUNT(*) as quantity')->groupBy('lab_id')->pluck('quantity', 'lab_id');

        return DB::table('labs')->whereIn('id', $labIds)->whereNull('deleted_at')->orderBy('name')->get(['id', 'name'])
            ->map(fn (object $lab): array => ['id' => $lab->id, 'name' => $lab->name,
                'positions' => (int) ($summary[$lab->id]->positions ?? 0),
                'stock_alerts' => (int) ($summary[$lab->id]->stock_alerts ?? 0),
                'active_samples' => $this->sampleBand((int) ($samples[$lab->id] ?? 0)),
            ]);
    }

    private function sampleBand(int $count): string
    {
        return match (true) {
            $count === 0 => '0', $count < 5 => '1–4', $count < 10 => '5–9',
            $count < 20 => '10–19', $count < 50 => '20–49', default => '50+',
        };
    }
}
