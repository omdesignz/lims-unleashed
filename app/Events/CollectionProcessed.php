<?php

namespace App\Events;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CollectionProcessed implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    public $customer;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Customer $customer)
    {
        $this->user = $user;
        $this->customer = $customer;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'laboratory.collection.processed';
    }

    /** @return array<string, int|string|null> */
    public function broadcastWith(): array
    {
        return ['customer_id' => $this->customer->id, 'customer_name' => $this->customer->name];
    }
}
