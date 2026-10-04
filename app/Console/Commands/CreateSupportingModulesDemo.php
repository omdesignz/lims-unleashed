<?php

namespace App\Console\Commands;

use App\Actions\CreateSupportingModulesDemo as CreateDemo;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:demo-supporting-modules {--json : Output the new credentials and fixture identifiers as JSON}')]
#[Description('Create fresh local-only supporting-module fixtures without modifying existing accounts')]
class CreateSupportingModulesDemo extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CreateDemo $createDemo): int
    {
        $demo = $createDemo->execute();
        if ($this->option('json')) {
            $this->line(json_encode($demo, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        } else {
            $this->info('Fresh demonstration records created: '.$demo['marker']);
            foreach (['staff', 'reviewer', 'lab_manager', 'qualification_editor', 'peer_target', 'portal'] as $kind) {
                $this->line($kind.': '.$demo[$kind]['email'].' / '.$demo[$kind]['password']);
            }
            $this->warn('Save these newly generated credentials securely. Existing accounts were not changed. Demo lab notifications use the database channel only.');
        }

        return self::SUCCESS;
    }
}
