<?php

// app/Http/Controllers/SampleDiscardController.php

namespace App\Http\Controllers;

use App\Models\VAPSampleDiscard;
use App\Models\VAPSampleEntry;
use App\Settings\GeneralSettings;
use App\Support\PdfResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class VAPSampleDiscardController extends Controller
{
    /**
     * Store a newly created sample discard record
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sample_id' => 'required|exists:sample_entries,id',
            'discard_method' => 'required|string|max:255',
            'qty' => 'required|string|max:255',
            'discarded_at' => 'nullable|date',
            'lab_id' => 'nullable|exists:labs,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        DB::beginTransaction();
        try {
            $sample = VAPSampleEntry::findOrFail($validated['sample_id']);

            // Check if sample can be discarded
            if (! in_array($sample->status, ['COMPLETADO', 'CANCELADO'])) {
                return redirect()->back()->with([
                    'message' => 'Only completed or canceled samples can be discarded.',
                    'type' => 'error',
                ]);
            }

            // Create discard record
            $discard = VAPSampleDiscard::create(array_merge($validated, [
                'discarded_by_id' => auth()->id(),
                'discarded_at' => $validated['discarded_at'] ?? now(),
            ]));

            $sample->forceFill([
                'retention_status' => 'discarded',
                'discard_scheduled_at' => now()->toDateString(),
            ])->save();

            // Soft delete the sample (optional - depends on your business logic)
            // $sample->delete();

            DB::commit();

            return redirect()->back()->with([
                'message' => 'Sample discard recorded successfully.',
                'type' => 'success',
                'discard_id' => $discard->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with([
                'message' => 'Error recording sample discard: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Generate discard certificate PDF
     */
    public function generatePdf(VAPSampleDiscard $sampleDiscard)
    {
        $sampleDiscard->load(['sample.customer', 'sample.lab', 'sample.department', 'discardedBy']);

        $pdf = PDF::loadView('PDFs.sample-discard', [
            'discard' => $sampleDiscard,
            'settings' => app(GeneralSettings::class),
            'date' => now()->format('d/m/Y'),
            'time' => now()->format('H:i:s'),
        ]);

        $filename = "discard-certificate-{$sampleDiscard->sample->code}-".now()->format('Ymd-His').'.pdf';

        return PdfResponse::download($pdf, $filename);
    }

    /**
     * Get recent discards
     */
    public function recent(Request $request)
    {
        $days = $request->get('days', 7);

        $discards = VAPSampleDiscard::with(['sample', 'discardedBy'])
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($discard) {
                return [
                    'id' => $discard->id,
                    'sample_id' => $discard->sample_id,
                    'sample' => $discard->sample ? $discard->sample->only(['id', 'name', 'code']) : null,
                    'discard_method' => $discard->discard_method,
                    'qty' => $discard->qty,
                    'discarded_at' => $discard->discarded_at,
                    'discarded_by' => $discard->discardedBy ? $discard->discardedBy->only(['id', 'name']) : null,
                ];
            });

        return response()->json($discards);
    }

    /**
     * Get discard statistics
     */
    public function stats()
    {
        $stats = [
            'total_discards' => VAPSampleDiscard::count(),
            'discards_this_month' => VAPSampleDiscard::whereMonth('created_at', now()->month)->count(),
            'by_method' => VAPSampleDiscard::select('discard_method', DB::raw('count(*) as total'))
                ->groupBy('discard_method')
                ->get(),
            'by_lab' => VAPSampleDiscard::select('lab_id', DB::raw('count(*) as total'))
                ->with('lab')
                ->groupBy('lab_id')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Export discards to CSV
     */
    public function export(Request $request)
    {
        $discards = VAPSampleDiscard::with(['sample', 'discardedBy', 'lab', 'department'])
            ->when($request->has('start_date'), function ($query) use ($request) {
                $query->where('created_at', '>=', $request->start_date);
            })
            ->when($request->has('end_date'), function ($query) use ($request) {
                $query->where('created_at', '<=', $request->end_date);
            })
            ->when($request->has('method'), function ($query) use ($request) {
                $query->where('discard_method', $request->method);
            })
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="discards-'.now()->format('Ymd-His').'.csv"',
        ];

        $callback = function () use ($discards) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'ID',
                'Sample Code',
                'Sample Name',
                'Discard Method',
                'Quantity',
                'Discarded At',
                'Discarded By',
                'Lab',
                'Department',
                'Created At',
            ]);

            // CSV data
            foreach ($discards as $discard) {
                fputcsv($file, [
                    $discard->id,
                    $discard->sample->code ?? 'N/A',
                    $discard->sample->name ?? 'N/A',
                    $discard->discard_method,
                    $discard->qty,
                    $discard->discarded_at->format('Y-m-d H:i:s'),
                    $discard->discardedBy->name ?? 'N/A',
                    $discard->lab->name ?? 'N/A',
                    $discard->department->name ?? 'N/A',
                    $discard->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
