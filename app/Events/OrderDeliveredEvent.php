<?php

namespace App\Events;

use App\Models\InventoryOrder;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderDeliveredEvent implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public User $user, public InventoryOrder $order) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->user->id),
            new PrivateChannel('orders.'.$this->order->id),

        ];
    }

    public function broadcastAs(): string
    {
        return 'inventory.order.delivered';
    }

    /** @return array<string, int|string|null> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'reference' => $this->order->reference,
            'status' => is_object($this->order->status) ? ($this->order->status->value ?? null) : $this->order->status,
            'delivered_to_user_id' => $this->user->id,
        ];
    }
}
