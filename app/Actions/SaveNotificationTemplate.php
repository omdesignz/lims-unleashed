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

            $template = NotificationTemplate::query()->where('lab_id', $labId)->where('key', $key)
                ->lockForUpdate()->first() ?? new NotificationTemplate(['lab_id' => $labId, 'key' => $key]);
            $template->fill([...$validated, 'updated_by_id' => $operator->id]);
            $expected = $template->only([...NotificationTemplate::EDITABLE_FIELDS, 'lab_id', 'key', 'updated_by_id']);

            if (! $template->exists || $template->isDirty()) {
                abort_unless($template->save(), 409, 'Não foi possível guardar o modelo de notificação.');
            }

            $persisted = $template->fresh();
            abort_unless($persisted && $persisted->only(array_keys($expected)) === $expected, 409,
                'Não foi possível confirmar o modelo de notificação guardado.');
            $this->access->operator($userId, $labId, 'edit_settings');

            return $persisted;
        }, 3);
    }
}
