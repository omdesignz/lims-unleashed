<?php

namespace App\Http\Controllers;

use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\ReagentConsumption;
use App\Support\PdfResponse;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class InventoryBatchController extends Controller
{
    // 1. LOOKUP FOR MOBILE SCANNER
    public function lookup($id)
    {
        // Handle both raw ID or a prefixed string like "BATCH:102"
        $cleanId = str_replace('BATCH:', '', $id);

        $batch = InventoryBatch::with('inventory.item.unit')->findOrFail($cleanId);
        $item = $batch->inventory?->item;

        return response()->json([
            'id' => $batch->id,
            'item_name' => $item?->name,
            'batch_number' => $batch->batch_number,
            'qty_remaining' => $batch->qty_remaining,
            'expiry_date' => $batch->expiry_date?->format('d M Y'),
            'is_expired' => (bool) $batch->expiry_date?->isPast(),
            'unit' => $item?->unit?->name ?? 'units',
        ]);
    }

    // 2. HANDLE MOBILE CONSUMPTION/TRANSFER
    public function handleMobileAction(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $batch = InventoryBatch::findOrFail($request->batch_id);

            // Log to itransactions (Your main ledger)
            InventoryTransaction::create([
                'inventory_id' => $batch->inventory_id,
                'user_id' => auth()->id(),
                'item_id' => $batch->inventory->item_id,
                'batch_id' => $batch->id,
                'warehouse_id' => $batch->inventory->warehouse_id,
                'type_id' => ($request->type === 'consumption') ? 1 : 2,
                'qty' => $request->qty,
                'reason' => 'Mobile Scan: '.$request->type,
            ]);

            // If consumption, also log to reagent_consumption
            if ($request->type === 'consumption') {
                ReagentConsumption::create([
                    'date' => now(),
                    'user_id' => auth()->id(),
                    'reagent_id' => $batch->inventory->item_id,
                    'reagent_name' => $batch->inventory->item->name,
                    'quantity_used' => $request->qty,
                    'batch_id' => $batch->id,
                    'warehouse_id' => $batch->inventory->warehouse_id,
                    'used_at' => now(),
                ]);
            }

            $batch->decrement('qty_remaining', $request->qty);

            return response()->json(['status' => 'success']);
        });
    }

    // 3. GENERATE PDF LABELS (Using Laravel-mPDF & Endroid)
    public function printBatchLabels(Request $request)
    {
        $ids = explode(',', $request->ids);
        $batches = InventoryBatch::with('inventory.item')->whereIn('id', $ids)->get();

        foreach ($batches as $batch) {
            // Generate QR Code as Data URI using Endroid
            $result = Builder::create()
                ->writer(new PngWriter)
                ->data('BATCH:'.$batch->id)
                ->size(100)
                ->margin(0)
                ->build();

            $batch->qr_code = $result->getDataUri();
        }

        $pdf = PDF::loadView('reports.labels.batch_sheet', ['batches' => $batches], [], [
            'format' => [50, 25], // Custom size: 50mm x 25mm
            'margin_top' => 2, 'margin_bottom' => 2, 'margin_left' => 2, 'margin_right' => 2,
        ]);

        return PdfResponse::inline($pdf, 'labels.pdf');
    }

    public function performAudit(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $batch = InventoryBatch::findOrFail($request->batch_id);
            $delta = $request->physical_qty - $request->system_qty;

            if ($delta == 0) {
                return response()->json(['message' => 'Inventory is accurate. No change needed.']);
            }

            // 1. Record the adjustment in itransactions
            InventoryTransaction::create([
                'inventory_id' => $batch->inventory_id,
                'user_id' => auth()->id(),
                'item_id' => $batch->inventory->item_id,
                'batch_id' => $batch->id,
                'warehouse_id' => $batch->inventory->warehouse_id,
                'type_id' => 3, // Assuming '3' is your "Inventory Adjustment" type
                'qty' => abs($delta),
                'reason' => $delta > 0 ? 'Audit: Physical Surplus' : 'Audit: Physical Shortage',
                'notes' => "System: {$request->system_qty}, Physical: {$request->physical_qty}",
            ]);

            // 2. Update the batch to reflect the truth
            $batch->update(['qty_remaining' => $request->physical_qty]);

            return response()->json(['status' => 'success', 'delta' => $delta]);
        });
    }

    public function generateGenealogyReport($batchId)
    {
        $batch = InventoryBatch::with(['inventory.item', 'transactions.user', 'consumptions'])
            ->findOrFail($batchId);

        $pdf = PDF::loadView('reports.batch_genealogy', compact('batch'));

        return $pdf->download("Genealogy-Batch-{$batch->batch_number}.pdf");
    }
}
