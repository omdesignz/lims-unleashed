<?php

namespace Tests\Feature;

use App\Enums\Orders\InventoryOrderTrackingStatus;
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
use App\Models\Customer;
use App\Models\InventoryOrder;
use App\Models\LabCode;
use App\Models\Result;
use App\Models\User;
use App\Notifications\OperationalNotification;
use App\Notifications\PortalPasswordResetNotification;
use App\Notifications\PortalVerifyEmailNotification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Tests\TestCase;

class OperationalBroadcastNotificationTest extends TestCase
{
    public function test_operational_notification_exposes_consistent_database_and_broadcast_payloads(): void
    {
        $payload = [
            'key' => 'lab.analysis.approved',
            'category' => 'laboratory',
            'priority' => 'high',
            'title' => 'Resultados aprovados',
            'message' => 'Os resultados de LC-2026-17 foram aprovados.',
            'action_label' => 'Abrir análise',
            'action_url' => '/analysis/17',
            'channels' => ['database', 'broadcast'],
            'sender_id' => 1,
            'sender_name' => 'Direcção laboratorial',
            'context' => ['analysis_id' => 17],
        ];
        $notification = new OperationalNotification($payload);

        $this->assertSame(['database', 'broadcast'], $notification->via(new \stdClass));
        $this->assertSame('lab.analysis.approved', $notification->toDatabase(new \stdClass)['key']);

        $broadcast = $notification->toBroadcast(new \stdClass);
        $this->assertInstanceOf(BroadcastMessage::class, $broadcast);
        $this->assertSame($notification->toDatabase(new \stdClass), $broadcast->data);
        $this->assertSame('broadcasts', $broadcast->queue);
    }

    public function test_user_notifications_use_the_authorized_private_user_channel(): void
    {
        $user = new User;
        $user->id = 42;

        $this->assertSame('users.42', $user->receivesBroadcastNotificationsOn());
    }

    public function test_laboratory_lifecycle_events_broadcast_after_commit_on_private_user_channels(): void
    {
        $user = new User;
        $user->id = 42;
        $labCode = new LabCode;
        $labCode->id = 17;
        $labCode->code = 'LC-2026-17';
        $result = new Result;
        $result->id = 91;
        $result->code_label = 'LC-2026-17 / pH';
        $customer = new Customer;
        $customer->id = 8;
        $customer->name = 'Laboratório Central';

        $events = [
            new AnalysisResultsInserted($user, $labCode),
            new AnalysisResultsVerified($user, $labCode),
            new AnalysisResultsApproved($user, $labCode),
            new AnalysisResultsValidated($result, $user->id),
            new CounterAnalysisResultsInserted($user, $labCode),
            new CounterAnalysisResultsVerified($user, $labCode),
            new CounterAnalysisResultsApproved($user, $labCode),
            new CollectionProcessed($user, $customer),
        ];

        foreach ($events as $event) {
            $this->assertInstanceOf(ShouldBroadcast::class, $event);
            $this->assertInstanceOf(ShouldDispatchAfterCommit::class, $event);
            $this->assertContainsOnlyInstancesOf(PrivateChannel::class, $event->broadcastOn());
            $this->assertSame('private-users.42', $event->broadcastOn()[0]->name);
            $this->assertNotEmpty($event->broadcastAs());
            $this->assertNotEmpty($event->broadcastWith());
        }
    }

    public function test_inventory_lifecycle_events_broadcast_after_commit_with_minimal_payloads(): void
    {
        $user = new User;
        $user->id = 42;
        $order = new InventoryOrder;
        $order->id = 17;
        $order->user_id = 42;
        $order->reference = 'PO-2026-17';
        $order->status = InventoryOrderTrackingStatus::DELIVERED;
        $order->updated_at = now();

        $events = [
            new InventoryOrderUpdatedEvent($order),
            new OrderDeliveredEvent($user, $order),
        ];

        foreach ($events as $event) {
            $this->assertInstanceOf(ShouldBroadcast::class, $event);
            $this->assertInstanceOf(ShouldDispatchAfterCommit::class, $event);
            $this->assertContainsOnlyInstancesOf(PrivateChannel::class, $event->broadcastOn());
            $this->assertSame(17, $event->broadcastWith()['id']);
            $this->assertArrayNotHasKey('items', $event->broadcastWith());
        }

        $this->assertTrue(is_subclass_of(StockUpdated::class, ShouldDispatchAfterCommit::class));
        $this->assertTrue(is_subclass_of(ReagentConsumed::class, ShouldDispatchAfterCommit::class));
        $this->assertFileDoesNotExist(app_path('Events/TestEvent.php'));
    }

    public function test_portal_security_notifications_are_queued_after_commit(): void
    {
        $this->assertTrue(is_subclass_of(PortalPasswordResetNotification::class, ShouldQueue::class));
        $this->assertTrue(is_subclass_of(PortalVerifyEmailNotification::class, ShouldQueue::class));

        $reset = new PortalPasswordResetNotification('https://lims.test/reset');
        $verification = new PortalVerifyEmailNotification;

        $this->assertSame('notifications', $reset->queue);
        $this->assertSame('notifications', $verification->queue);
        $this->assertTrue($reset->afterCommit);
        $this->assertTrue($verification->afterCommit);
    }
}
