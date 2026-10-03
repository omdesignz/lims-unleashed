<?php

namespace App\Http\Controllers;

use App\Models\InventoryItemTransfer;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class InventoryItemTransferController extends Controller
{
    public function __construct(
        private readonly VAPInventoryTransferController $canonical,
        private readonly SampleLaboratoryAccess $laboratoryAccess,
    ) {}

    public function index(Request $request): Response
    {
        return $this->canonical->index($request);
    }

    public function create(Request $request): Response
    {
        return $this->canonical->create($request);
    }

    public function edit(Request $request, int $itransfer): Response
    {
        $transfer = InventoryItemTransfer::query()->where('lab_id', $this->laboratoryAccess->activeLabId())
            ->findOrFail($itransfer);

        return $this->canonical->show($request, $transfer);
    }

    public function getInventoryItemTransfer(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view_itransfers'), 403);
        $search = $request->string('q')->trim()->toString();
        $transfers = InventoryItemTransfer::query()->where('lab_id', $this->laboratoryAccess->activeLabId())
            ->when($search !== '', fn ($query) => $query->where('qty', ctype_digit($search) ? (int) $search : -1))
            ->latest()->limit(50)->get();

        return response()->json($transfers);
    }

    public function store(): never
    {
        abort(410, 'Use the laboratory transfer workflow.');
    }

    public function update(): never
    {
        abort(410, 'Issued transfers cannot be edited.');
    }

    public function destroy(): never
    {
        abort(410, 'Use the laboratory transfer cancellation workflow.');
    }

    public function restore(): never
    {
        abort(410, 'Cancelled transfers cannot be restored.');
    }
}
