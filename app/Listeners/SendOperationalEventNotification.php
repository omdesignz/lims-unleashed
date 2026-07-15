<?php

namespace App\Listeners;

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
use App\Support\NotificationTemplateService;

class SendOperationalEventNotification
{
    public function __construct(private readonly NotificationTemplateService $templates) {}

    public function handle(object $event): void
    {
        [$key, $context, $actorId] = match ($event::class) {
            CollectionProcessed::class => ['lab.collection.processed', $this->laboratoryContext($event), $event->user->id],
            AnalysisResultsInserted::class => ['lab.results.inserted', $this->laboratoryContext($event), $event->user->id],
            AnalysisResultsVerified::class => ['lab.results.verified', $this->laboratoryContext($event), $event->user->id],
            AnalysisResultsApproved::class => ['lab.results.approved', $this->laboratoryContext($event), $event->user->id],
            CounterAnalysisResultsInserted::class => ['lab.counter_results.inserted', $this->laboratoryContext($event), $event->user->id],
            CounterAnalysisResultsVerified::class => ['lab.counter_results.verified', $this->laboratoryContext($event), $event->user->id],
            CounterAnalysisResultsApproved::class => ['lab.counter_results.approved', $this->laboratoryContext($event), $event->user->id],
            AnalysisResultsValidated::class => ['lab.results.validated', $this->validationContext($event), $event->user_id],
            InventoryOrderUpdatedEvent::class => ['inventory.order.updated', $this->orderContext($event->order), auth()->id()],
            OrderDeliveredEvent::class => ['inventory.order.delivered', $this->orderContext($event->order), $event->user->id],
            StockUpdated::class => ['inventory.stock.updated', $this->stockContext($event), $event->transaction->user_id],
            ReagentConsumed::class => ['inventory.reagent.consumed', $this->consumptionContext($event), $event->consumption->user_id],
            default => [null, [], null],
        };

        if ($key) {
            $this->templates->notifyPermission($key, $context, $actorId);
        }
    }

    /** @return array<string, scalar|null> */
    private function laboratoryContext(object $event): array
    {
        $code = $event->code ?? null;

        return [
            'sample_code' => $code?->code ?? $event->customer?->code ?? $event->customer?->name ?? 'Amostra',
            'sample_url' => url('/analysis'),
            'results_url' => url('/analysis'),
            'actor_name' => $event->user?->name,
        ];
    }

    /** @return array<string, scalar|null> */
    private function validationContext(AnalysisResultsValidated $event): array
    {
        return [
            'document_number' => $event->result->code_label ?: ('Resultado #'.$event->result->id),
            'document_url' => url('/analysis'),
        ];
    }

    /** @return array<string, scalar|null> */
    private function orderContext(object $order): array
    {
        return [
            'order_number' => $order->reference ?: ('#'.$order->id),
            'status' => is_object($order->status) ? ($order->status->value ?? (string) $order->status) : $order->status,
            'order_url' => route('iorders.show', $order),
        ];
    }

    /** @return array<string, scalar|null> */
    private function stockContext(StockUpdated $event): array
    {
        return [
            'item_name' => $event->transaction->item?->name ?? 'Item',
            'quantity' => $event->transaction->qty,
            'unit' => $event->transaction->item?->unit?->code ?? '',
            'inventory_url' => url('/vap-inventory'),
        ];
    }

    /** @return array<string, scalar|null> */
    private function consumptionContext(ReagentConsumed $event): array
    {
        return [
            'item_name' => $event->consumption->reagent_name ?: 'Reagente',
            'quantity' => $event->consumption->quantity_used,
            'inventory_url' => url('/vap-inventory/consumption'),
        ];
    }
}
