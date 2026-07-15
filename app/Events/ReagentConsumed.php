<?php

namespace App\Events;

use App\Models\ReagentConsumption;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReagentConsumed implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $consumption;

    public function __construct(ReagentConsumption $consumption)
    {
        $this->consumption = $consumption;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('inventory');
    }

    public function broadcastAs(): string
    {
        return 'ReagentConsumed';
    }

    /** @return array<string, int|float|string|null> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->consumption->id,
            'reagent' => $this->consumption->reagent_name,
            'quantity' => $this->consumption->quantity_used,
            'user' => $this->consumption->user->name ?? 'System',
            'timestamp' => $this->consumption->created_at->toISOString(),
        ];
    }
}
