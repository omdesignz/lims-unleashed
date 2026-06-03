<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('general.app_bank_name')) {
            $this->migrator->add('general.app_bank_name', null);
        }

        if (! $this->migrator->exists('general.app_bank_account_name')) {
            $this->migrator->add('general.app_bank_account_name', null);
        }

        if (! $this->migrator->exists('general.app_bank_account_number')) {
            $this->migrator->add('general.app_bank_account_number', null);
        }

        if (! $this->migrator->exists('general.app_bank_iban')) {
            $this->migrator->add('general.app_bank_iban', null);
        }

        if (! $this->migrator->exists('general.app_bank_swift')) {
            $this->migrator->add('general.app_bank_swift', null);
        }

        if (! $this->migrator->exists('general.app_bank_details')) {
            $this->migrator->add('general.app_bank_details', null);
        }

        if (! $this->migrator->exists('general.app_document_keywords')) {
            $this->migrator->add('general.app_document_keywords', 'ISO 17025; rastreabilidade; controlo documental; ensaios laboratoriais');
        }
    }
};
