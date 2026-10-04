<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * The laboratory's mark as an uploaded file, printed on the letterhead of
     * every generated document. A path on the public disk.
     */
    public function up(): void
    {
        if (! $this->migrator->exists('general.app_document_logo')) {
            $this->migrator->add('general.app_document_logo', null);
        }
    }
};
