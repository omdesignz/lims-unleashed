<?php

namespace App\Actions;

use App\Http\Requests\UpdateNotificationTemplateRequest;
use App\Models\NotificationTemplate;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaveNotificationTemplate
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /** @param array<string, mixed> $data */
    public function handle(int $userId, int $labId, string $key, array $data): NotificationTemplate
    {
        return DB::transaction(function () use ($userId, $labId, $key, $data): NotificationTemplate {
            $operator = $this->access->operator($userId, $labId, 'edit_settings');
            $request = new UpdateNotificationTemplateRequest;
            $validated = collect(Validator::make($data, $request->rulesForKey($key))->validate())
                ->only(NotificationTemplate::EDITABLE_FIELDS)->all();

            return NotificationTemplate::query()->updateOrCreate(
                ['lab_id' => $labId, 'key' => $key],
                [...$validated, 'updated_by_id' => $operator->id],
            );
        }, 3);
    }
}
