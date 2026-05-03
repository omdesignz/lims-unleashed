<?php

namespace App\Events;

use App\Models\Result;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalysisResultsValidated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $result;
    public $user_id;

    /**
     * Create a new event instance.
     */
    public function __construct(Result $result, $user_id)
    {
        $this->result = $result;
        $this->user_id = $user_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->user_id),
        ];
    }
}
