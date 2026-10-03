<?php

// app/Http/Controllers/SampleDiscardController.php

namespace App\Http\Controllers;

use App\Actions\DiscardLaboratorySample;
use App\Http\Requests\VAP\StoreSampleDiscardRequest;
use App\Models\VAPSampleDiscard;
use App\Services\SampleLaboratoryAccess;
use App\Settings\GeneralSettings;
use App\Support\PdfResponse;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class VAPSampleDiscardController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    /**
     * Store a newly created sample discard record
     */
    public function store(StoreSampleDiscardRequest $request, DiscardLaboratorySample $action): RedirectResponse
    {
        $discard = $action->execute($this->laboratoryAccess->activeLabId(), $request->user(), $request->validated());

        return redirect()->back()->with([
            'message' => 'Descarte da amostra registado com êxito.',
            'type' => 'success',
            'discard_id' => $discard->id,
        ]);
    }

    /**
     * Generate discard certificate PDF
     */
    public function generatePdf(VAPSampleDiscard $sampleDiscard)
    {
        $sampleDiscard->load([
            'sample' => fn (BelongsTo $query) => $query->withTrashed()->with(['customer', 'lab', 'department']),
            'discardedBy',
        ]);

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
        $validated = $request->validate(['days' => ['sometimes', 'integer', 'min:1', 'max:365']]);
        $days = $validated['days'] ?? 7;

        $discards = $this->laboratoryAccess->discards()->with(['sample' => fn (BelongsTo $query) => $query->withTrashed(), 'discardedBy'])
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
            'total_discards' => $this->laboratoryAccess->discards()->count(),
            'discards_this_month' => $this->laboratoryAccess->discards()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'by_method' => $this->laboratoryAccess->discards()->select('discard_method', DB::raw('count(*) as total'))
                ->groupBy('discard_method')
                ->get(),
            'by_lab' => $this->laboratoryAccess->discards()->select('lab_id', DB::raw('count(*) as total'))
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
        $discards = $this->laboratoryAccess->discards()->with(['sample' => fn (BelongsTo $query) => $query->withTrashed(), 'discardedBy', 'lab', 'department'])
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
