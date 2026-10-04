<?php

namespace App\Console\Commands;

use App\Actions\CreateFinancialObservationDemo as CreateDemo;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:demo-financial-observations')]
#[Description('Create isolated local-only, non-fiscal documents and a narrowly permissioned editor; output fresh credentials as JSON')]
class CreateFinancialObservationDemo extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CreateDemo $createDemo): int
    {
        $this->line(json_encode($createDemo->execute(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
