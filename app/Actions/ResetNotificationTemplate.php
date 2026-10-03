<?php

namespace App\Actions;

use App\Models\NotificationTemplate;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Support\NotificationTemplateCatalog;
use Illuminate\Support\Facades\DB;

class ResetNotificationTemplate
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly NotificationTemplateCatalog $catalog,
    ) {}

    public function handle(int $userId, int $labId, string $key): void
    {
        abort_unless(isset($this->catalog->definitions()[$key]), 404);
        DB::transaction(function () use ($userId, $labId, $key): void {
            $this->access->operator($userId, $labId, 'edit_settings');
            NotificationTemplate::query()->where('lab_id', $labId)->where('key', $key)->delete();
        }, 3);
    }
}
