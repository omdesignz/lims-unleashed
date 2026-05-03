<?php

namespace App\Jobs;

use App\Models\CounterAnalysis;
use App\Models\Result;
use App\Models\User;
use App\Models\VAPSampleEntry;
use App\Support\LaboratoryWorkflowNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckLaboratoryWorkflowStaleness implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(LaboratoryWorkflowNotifier $workflowNotifier): void
    {
        $systemUser = User::query()
            ->role('admin')
            ->whereNotNull('email_verified_at')
            ->first() ?? User::query()->whereNotNull('email_verified_at')->first();

        if (! $systemUser) {
            return;
        }

        VAPSampleEntry::query()
            ->whereIn('status', ['POR_INICIAR', 'EN_PROGRESO'])
            ->where('updated_at', '<=', now()->subDays(3))
            ->with(['warehouse', 'receivedBy'])
            ->get()
            ->each(fn (VAPSampleEntry $sampleEntry) => $workflowNotifier->notifyStaleSample($sampleEntry, $systemUser));

        Result::query()
            ->whereNotNull('inserted_date')
            ->whereNull('verified_date')
            ->where('updated_at', '<=', now()->subDays(2))
            ->with('sample.collection.collection.warehouse')
            ->get()
            ->each(fn (Result $result) => $workflowNotifier->notifyStaleResult($result, 'verify', $systemUser));

        Result::query()
            ->whereNotNull('verified_date')
            ->whereNull('approved_date')
            ->where('updated_at', '<=', now()->subDays(2))
            ->with('sample.collection.collection.warehouse')
            ->get()
            ->each(fn (Result $result) => $workflowNotifier->notifyStaleResult($result, 'approve', $systemUser));

        CounterAnalysis::query()
            ->whereNull('end_date')
            ->where('updated_at', '<=', now()->subDays(2))
            ->with('requested_result.sample.collection.collection.warehouse')
            ->get()
            ->each(fn (CounterAnalysis $counterAnalysis) => $workflowNotifier->notifyStaleCounterAnalysis($counterAnalysis, $systemUser));
    }
}
