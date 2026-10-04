<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * The laboratory's own address (where its activities are performed, which
     * can differ from the organisation's registered address) and its
     * accreditation, which test reports state (ISO/IEC 17025:2017, 7.8.2.1 b).
     */
    public function up(): void
    {
        foreach (['app_client_lab_address', 'app_client_lab_accreditation_body', 'app_client_lab_accreditation_number'] as $name) {
            if (! $this->migrator->exists('general.'.$name)) {
                $this->migrator->add('general.'.$name, null);
            }
        }
    }
};
