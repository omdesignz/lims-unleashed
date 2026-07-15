<?php

namespace App\Events;

use App\Models\Result;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalysisResultsValidated implements ShouldBroadcast, ShouldDispatchAfterCommit
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
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'laboratory.results.validated';
    }

    /** @return array<string, int|string|null> */
    public function broadcastWith(): array
    {
        return ['result_id' => $this->result->id, 'result_code' => $this->result->code_label];
    }
}
