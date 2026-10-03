<?php

namespace App\Http\Middleware;

use App\Models\VAPSampleDiscard;
use App\Models\VAPSampleEntry;
use App\Services\SampleLaboratoryAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSampleLaboratoryAccess
{
    public function __construct(private readonly SampleLaboratoryAccess $access) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->access->activeLabId();

        $permission = match ($request->route()->getName()) {
            'vap_samples.samples.store', 'vap_samples.samples.bulk-store',
            'vap_samples.samples.import', 'vap_samples.samples.import-template' => 'add_samples',
            'vap_samples.samples.update', 'vap_samples.samples.internal-quality-control-decision' => 'edit_samples',
            'vap_samples.samples.destroy', 'vap_samples.discards.store' => 'delete_samples',
            default => 'view_samples',
        };
        abort_unless($request->user()->can($permission), 403);

        $sample = $request->route('sampleEntry');
        if ($sample instanceof VAPSampleEntry) {
            abort_unless((int) $sample->lab_id === $this->access->activeLabId(), 404);
        }

        $discard = $request->route('sampleDiscard');
        if ($discard instanceof VAPSampleDiscard) {
            abort_unless($this->access->discards()->whereKey($discard->id)->exists(), 404);
        }

        return $next($request);
    }
}
