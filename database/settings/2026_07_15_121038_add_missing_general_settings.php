<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.app_primary_color', null);
        $this->migrator->add('general.app_client_lab_province', null);
        $this->migrator->add('general.app_client_lab_director', null);
    }
};
