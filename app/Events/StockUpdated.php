<?php

namespace App\Events;

use App\Models\InventoryTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;

    public function __construct(InventoryTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('inventory');
    }

    public function broadcastAs(): string
    {
        return 'StockUpdated';
    }

    /** @return array<string, int|float|string|null> */
    public function broadcastWith(): array
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
