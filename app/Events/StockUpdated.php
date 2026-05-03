<?php

namespace App\Events;

use App\Models\InventoryTransaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;

    public function __construct(InventoryTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function broadcastOn()
    {
        return new Channel('inventory');
    }

    public function broadcastAs()
    {
        return 'StockUpdated';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->transaction->id,
            'item' => $this->transaction->item->name,
            'quantity' => $this->transaction->qty,
            'type' => $this->transaction->type->name,
            'user' => $this->transaction->user->name,
            'timestamp' => $this->transaction->created_at->toISOString(),
        ];
    }
}