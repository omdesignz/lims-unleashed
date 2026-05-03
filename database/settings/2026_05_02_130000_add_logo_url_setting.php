<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('general.app_logo_url')) {
            $this->migrator->add('general.app_logo_url', null);
        }
    }
};
