<?php

namespace App\Http\Controllers;

use App\Models\InventoryBatch;
use App\Services\SampleLaboratoryAccess;
use App\Support\PdfResponse;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class InventoryBatchController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    public function lookup(string $id): JsonResponse
    {
        $cleanId = str_starts_with($id, 'BATCH:') ? substr($id, 6) : $id;
        abort_unless(ctype_digit($cleanId), 404);

        $batch = $this->localBatches()->with('inventory.item.unit')->findOrFail((int) $cleanId);
        $item = $batch->inventory?->item;

        return response()->json([
            'id' => $batch->id,
            'item_name' => $item?->name,
            'batch_number' => $batch->batch_number,
            'qty_remaining' => $batch->qty_remaining,
            'expiry_date' => $batch->expiry_date?->format('d M Y'),
            'is_expired' => (bool) $batch->expiry_date?->isPast(),
            'unit' => $item?->unit?->code ?? 'unidades',
        ]);
    }

    public function printBatchLabels(Request $request): Response
    {
        $ids = collect(explode(',', (string) $request->query('ids')))->filter()->unique()->values();
        abort_if($ids->isEmpty() || $ids->count() > 100 || $ids->contains(fn (string $id): bool => ! ctype_digit($id)), 422);

        $batches = $this->localBatches()->with('inventory.item')->whereIn('id', $ids)->get();
        abort_unless($batches->count() === $ids->count(), 404);

        foreach ($batches as $batch) {
            $batch->qr_code = (new PngWriter)->write(new QrCode(
                data: 'BATCH:'.$batch->id,
                size: 100,
                margin: 0,
            ))->getDataUri();
        }

        $pdf = PDF::loadView('reports.labels.batch_sheet', ['batches' => $batches], [], [
            'format' => [50, 25],
            'margin_top' => 2, 'margin_bottom' => 2, 'margin_left' => 2, 'margin_right' => 2,
        ]);

        return PdfResponse::inline($pdf, 'labels.pdf');
    }

    public function handleMobileAction(): never
    {
        abort(410, 'Use the controlled stock and reagent workflows.');
    }

    public function performAudit(): never
    {
        abort(410, 'Use the controlled stock adjustment workflow.');
    }

    private function localBatches(): EloquentBuilder
    {
        return InventoryBatch::query()->whereHas('inventory', fn (EloquentBuilder $inventory) => $inventory
            ->forLaboratory($this->laboratoryAccess->activeLabId()));
    }
}
