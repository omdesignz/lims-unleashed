<?php

namespace App\Listeners;

use App\Events\AnalysisResultsValidated;
use App\Services\Integrations\IntegrationPublisher;
use Illuminate\Contracts\Queue\ShouldQueue;

class PublishValidatedResultIntegrations implements ShouldQueue
{
    public function __construct(private readonly IntegrationPublisher $publisher) {}

    /**
     * Handle the event.
     */
    public function handle(AnalysisResultsValidated $event): void
    {
        $this->publisher->publishValidatedAnalysis($event->result);
    }
}
