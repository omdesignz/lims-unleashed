<?php

namespace App\Console\Commands;

use App\Actions\CreateLaboratoryDemoData;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use LogicException;

#[Signature('app:demo-laboratory')]
#[Description('Create isolated local demonstration access and canonical intake fixtures without resetting existing data')]
class CreateLaboratoryDemo extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CreateLaboratoryDemoData $createDemoData): int
    {
        try {
            $demo = $createDemoData->execute();
        } catch (LogicException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($demo['password'] === null ? 'Existing demo data reused. Password unchanged.' : 'Local demo data created. No notifications were sent.');
        $this->line('Email: '.$demo['user']->email);

        if ($demo['password'] !== null) {
            $this->line('Password: '.$demo['password']);
            $this->warn('Store this generated demo password now. It will not be displayed or reset on later runs.');
        }

        $this->line('Labs: '.$demo['main_lab']->name.' / '.$demo['peer_lab']->name);
        $this->line('Canonical sample entries: '.count($demo['entries']));

        return self::SUCCESS;
    }
}
