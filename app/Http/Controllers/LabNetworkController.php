<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLabBrandingRequest;
use App\Models\LabNetwork;
use App\Models\VAPLab;
use App\Services\LabNetworkAccess;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LabNetworkController extends Controller
{
    public function __construct(private readonly LabNetworkAccess $access) {}

    public function index(Request $request, LabNetwork $network): Response
    {
        $labIds = $this->access->visibleLabs($request->user(), $network)->pluck('labs.id');
        abort_if($labIds->isEmpty(), 403);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'lab_id' => ['nullable', 'integer', Rule::in($labIds->all())],
            'available' => ['nullable', 'boolean'],
        ]);

        $base = DB::table('inventory as stock')
            ->join('i_warehouses as warehouse', 'warehouse.id', '=', 'stock.warehouse_id')
            ->join('labs as lab', 'lab.id', '=', 'warehouse.lab_id')
            ->join('i_items as item', 'item.id', '=', 'stock.item_id')
            ->leftJoin('i_units as unit', 'unit.id', '=', 'item.unit_id')
            ->whereIn('lab.id', $labIds)
            ->whereNull('stock.deleted_at')->whereNull('warehouse.deleted_at')
            ->whereNull('item.deleted_at')->whereNull('lab.deleted_at');

        $available = "CASE WHEN stock.status IN ('AVAILABLE', 'ON_HAND', 'RECEIVED') AND (item.reagent_expiry_date IS NULL OR item.reagent_expiry_date >= CURRENT_DATE) THEN GREATEST(stock.qty_available, 0) ELSE 0 END";
        $summary = (clone $base)->selectRaw('lab.id, COUNT(*) as positions, SUM(CASE WHEN stock.qty_available <= stock.reorder_point THEN 1 ELSE 0 END) as stock_alerts')
            ->groupBy('lab.id')->get()->keyBy('id');
        $samples = DB::table('sample_entries')->whereIn('lab_id', $labIds)->whereNull('deleted_at')
            ->selectRaw("lab_id, SUM(CASE WHEN status IN ('POR_INICIAR', 'EN_PROGRESO', 'EN_PAUSA') THEN 1 ELSE 0 END) as active_samples")
            ->groupBy('lab_id')->pluck('active_samples', 'lab_id');

        $stock = $base->when($filters['lab_id'] ?? null, fn (Builder $query, int $id) => $query->where('lab.id', $id))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $pattern = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function (Builder $query) use ($pattern): void {
                    $query->whereLike('item.name', $pattern)->orWhereLike('item.code', $pattern)->orWhereLike('item.lot', $pattern);
                });
            })
            ->when($request->boolean('available'), fn (Builder $query) => $query->whereRaw("($available) > 0"))
            ->select(['stock.id', 'item.name', 'item.code', 'item.lot', 'item.reagent_expiry_date as expiry_date', 'lab.name as lab_name', 'warehouse.name as warehouse_name', 'unit.code as unit', 'stock.status', 'stock.qty_available as recorded_quantity'])
            ->selectRaw("$available as available_quantity")
            ->orderBy('item.name')->orderBy('lab.name')->orderBy('stock.id')
            ->paginate(25)->withQueryString();

        return Inertia::render('LabNetwork/Index', [
            'network' => $network->only('id', 'name', 'primary_color'),
            'labs' => VAPLab::query()->whereIn('id', $labIds)->orderBy('name')->get(['id', 'name'])->map(fn (VAPLab $lab): array => [
                'id' => $lab->id, 'name' => $lab->name,
                'positions' => (int) ($summary[$lab->id]->positions ?? 0),
                'stock_alerts' => (int) ($summary[$lab->id]->stock_alerts ?? 0),
                'active_samples' => (int) ($samples[$lab->id] ?? 0),
            ]),
            'stock' => $stock,
            'filters' => $filters,
            'networkOverview' => $this->access->canViewNetwork($request->user(), $network),
        ]);
    }

    public function switchLab(Request $request, VAPLab $lab): RedirectResponse
    {
        abort_unless($this->access->memberships($request->user())->contains('id', $lab->id), 403);
        $request->session()->put('active_lab_id', $lab->id);

        return $lab->network_id
            ? redirect()->route('lab-network.index', $lab->network_id)
            : redirect()->route('dashboard');
    }

    public function updateBranding(UpdateLabBrandingRequest $request, VAPLab $lab): RedirectResponse
    {
        $lab->update($request->validated());

        return back()->with('toast', ['type' => 'success', 'message' => 'Identidade visual guardada.']);
    }
}
