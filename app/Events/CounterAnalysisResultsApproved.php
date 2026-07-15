<?php

namespace App\Events;

use App\Models\LabCode;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CounterAnalysisResultsApproved implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    public $code;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, LabCode $code)
    {
        $this->user = $user;
        $this->code = $code;
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
        return 'laboratory.counter-results.approved';
    }

    /** @return array<string, int|string|null> */
    public function broadcastWith(): array
    {
        return ['lab_code_id' => $this->code->id, 'sample_code' => $this->code->code];
    }
}
