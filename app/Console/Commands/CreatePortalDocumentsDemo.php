<?php

namespace App\Console\Commands;

use App\Actions\CreatePortalDocumentsDemo as CreateDemo;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:demo-portal-documents')]
#[Description('Create isolated local-only, non-fiscal portal documents and output fresh credentials as JSON')]
class CreatePortalDocumentsDemo extends Command
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
