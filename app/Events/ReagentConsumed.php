<?php

namespace App\Events;

use App\Models\ReagentConsumption;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReagentConsumed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $consumption;

    public function __construct(ReagentConsumption $consumption)
    {
        $this->consumption = $consumption;
    }

    public function broadcastOn()
    {
        return new Channel('inventory');
    }

    public function broadcastAs()
    {
        return 'ReagentConsumed';
    }

    public function broadcastWith()
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