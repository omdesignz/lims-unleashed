<?php

namespace App\Providers;

use App\Events\AnalysisResultsApproved;
use App\Events\AnalysisResultsInserted;
use App\Events\AnalysisResultsValidated;
use App\Events\AnalysisResultsVerified;
use App\Events\CollectionProcessed;
use App\Events\CounterAnalysisResultsApproved;
use App\Events\CounterAnalysisResultsInserted;
use App\Events\CounterAnalysisResultsVerified;
use App\Events\InventoryOrderUpdatedEvent;
use App\Events\OrderDeliveredEvent;
use App\Events\ReagentConsumed;
use App\Events\StockUpdated;
use App\Listeners\GenerateAnalysisReportDocument;
use App\Listeners\PublishValidatedResultIntegrations;
use App\Listeners\SendOperationalEventNotification;
use App\Listeners\UpdateLastLoginTime;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        AnalysisResultsValidated::class => [
            GenerateAnalysisReportDocument::class,
            PublishValidatedResultIntegrations::class,
            SendOperationalEventNotification::class,
        ],
        AnalysisResultsInserted::class => [SendOperationalEventNotification::class],
        AnalysisResultsVerified::class => [SendOperationalEventNotification::class],
        AnalysisResultsApproved::class => [SendOperationalEventNotification::class],
        CounterAnalysisResultsInserted::class => [SendOperationalEventNotification::class],
        CounterAnalysisResultsVerified::class => [SendOperationalEventNotification::class],
        CounterAnalysisResultsApproved::class => [SendOperationalEventNotification::class],
        CollectionProcessed::class => [SendOperationalEventNotification::class],
        InventoryOrderUpdatedEvent::class => [SendOperationalEventNotification::class],
        OrderDeliveredEvent::class => [SendOperationalEventNotification::class],
        StockUpdated::class => [SendOperationalEventNotification::class],
        ReagentConsumed::class => [SendOperationalEventNotification::class],
        Login::class => [
            UpdateLastLoginTime::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
