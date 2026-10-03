<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportEquipmentsChunk implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /** @param list<array<mixed>> $rows */
    public function __construct(public array $rows, public readonly int $labId, public readonly int $userId) {}

    /** Fail closed for payloads queued before the unsafe duplicate writer was retired. */
    public function handle(): never
    {
        abort(410, 'A importação antiga foi retirada. Utilize a criação canónica do catálogo.');
    }
}
