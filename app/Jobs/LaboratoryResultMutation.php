<?php

namespace App\Jobs;

use App\Actions\ProcessLaboratoryResults;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class LaboratoryResultMutation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected const WORKFLOW = 'analysis';

    protected const STAGE = 'analyze';

    protected const INDIVIDUAL = false;

    /** @var array<int|string, mixed> */
    public array $results = [];

    public int $analysis_id = 0;

    public int $user_id = 0;

    public int $lab_id = 0;

    public ?string $signature = null;

    /** @param array<int|string, mixed> $results */
    public function __construct(array $results, int $analysis_id, User|int $user, int $lab_id, ?string $signature = null)
    {
        $this->results = $results;
        $this->analysis_id = $analysis_id;
        $this->user_id = $user instanceof User ? (int) $user->id : $user;
        $this->lab_id = $lab_id;
        $this->signature = $signature;
        $this->afterCommit();
    }

    public function handle(ProcessLaboratoryResults $action): void
    {
        $action->execute($this->lab_id, $this->analysis_id, $this->user_id, static::WORKFLOW, static::STAGE,
            static::INDIVIDUAL ? [$this->results] : $this->results, $this->signature, static::INDIVIDUAL);
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Laboratory result mutation failed.', ['lab_id' => $this->lab_id, 'analysis_id' => $this->analysis_id,
            'user_id' => $this->user_id, 'workflow' => static::WORKFLOW, 'stage' => static::STAGE, 'exception' => $exception::class]);
    }
}
