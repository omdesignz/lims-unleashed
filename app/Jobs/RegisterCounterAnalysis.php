<?php

namespace App\Jobs;

use App\Actions\RequestLaboratoryCounterAnalysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RegisterCounterAnalysis implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $lab_id = 0;

    public int $uniqueFor = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $result_id, public int $user_id, int $lab_id)
    {
        $this->lab_id = $lab_id;
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(RequestLaboratoryCounterAnalysis $action): void
    {
        $action->execute($this->lab_id, $this->result_id, $this->user_id);
    }

    public function uniqueId(): string
    {
        return 'counter-analysis-result:'.$this->lab_id.':'.$this->result_id;
    }
}
