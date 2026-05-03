<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.app_secondary_color', '#0f172a');
        $this->migrator->add('general.app_accent_color', '#14b8a6');
        $this->migrator->add('general.app_theme_preset', 'corporate');
        $this->migrator->add('general.app_operation_mode', 'client_only');
    }
};
