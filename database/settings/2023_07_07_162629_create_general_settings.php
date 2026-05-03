<?php

use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.app_name', 'GESTLAB');
        $this->migrator->add('general.app_version', '3.0');
        $this->migrator->add('general.app_slogan', 'Fazemos o certo quando ninguém está olhando.');
        $this->migrator->add('general.app_contact', '924577457');
        $this->migrator->add('general.app_private_key', Storage::disk('local')->get('private.txt'));
        $this->migrator->add('general.app_public_key', Storage::disk('local')->get('public.txt'));
        $this->migrator->add('general.app_nif', '5417150010');
        $this->migrator->add('general.app_agt_valid_name', 'GESTLAB/GESTLAB-GESTAO DE LABORATORIOS CLINICOS, LDA');
        $this->migrator->add('general.app_agt_validation_number', '264/AGT/2020');
        $this->migrator->add('general.app_client_name', null);
        $this->migrator->add('general.app_client_nif', null);
        $this->migrator->add('general.app_client_address', null);
        $this->migrator->add('general.app_client_contact', null);
        $this->migrator->add('general.app_client_email', null);
        $this->migrator->add('general.app_client_lab_name', null);
        $this->migrator->add('general.app_client_lab_slogan', null);
        $this->migrator->add('general.app_email', null);
    }
};