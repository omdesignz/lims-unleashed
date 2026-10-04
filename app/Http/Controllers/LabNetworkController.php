<?php

namespace App\Http\Controllers;

use App\Actions\ExportLabNetworkStock;
use App\Http\Requests\LabNetworkStockRequest;
use App\Http\Requests\UpdateLabBrandingRequest;
use App\Http\Resources\LabNetworkStockResource;
use App\Models\LabNetwork;
use App\Models\VAPLab;
use App\Services\LabNetworkAccess;
use App\Services\LabNetworkStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LabNetworkController extends Controller
{
    public function __construct(private readonly LabNetworkAccess $access) {}

    public function index(LabNetworkStockRequest $request, LabNetwork $network, LabNetworkStock $stock): HttpResponse
    {
        $labIds = $stock->laboratoryIds($request->user(), $network);
        $filters = $request->validated();
        $rows = $stock->search($labIds, $filters)->paginate($filters['per_page'] ?? 25)->appends($filters);
        $rows->through(fn (object $row): array => (new LabNetworkStockResource($row))->resolve($request));
        $response = Inertia::render('LabNetwork/Index', [
            'network' => $network->only('id', 'name', 'main_lab_id', 'primary_color'),
            'labs' => $stock->summaries($labIds), 'warehouses' => $stock->warehouses($labIds),
            'stock' => $rows, 'filters' => $filters, 'asOf' => now()->toIso8601String(),
            'networkOverview' => $this->access->canViewNetwork($request->user(), $network),
        ])->toResponse($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function export(LabNetworkStockRequest $request, LabNetwork $network, LabNetworkStock $stock, ExportLabNetworkStock $export): StreamedResponse
    {
        $stock->laboratoryIds($request->user(), $network);

        return response()->streamDownload(
            fn () => $export->write($request->user(), $network, $request->validated()),
            'disponibilidade-rede-'.$network->id.'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store'],
        );
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
