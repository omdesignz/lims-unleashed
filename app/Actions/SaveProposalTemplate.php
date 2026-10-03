<?php

namespace App\Actions;

use App\Models\ISOActivityLog;
use App\Models\ProposalTemplate;
use App\Models\User;
use App\Models\VAPProposalTemplate;
use App\Support\ProposalTemplateValidation;
use Illuminate\Support\Facades\DB;

class SaveProposalTemplate
{
    public function __construct(private ProposalTemplateValidation $validation) {}

    /** @param array<string,mixed> $data */
    public function execute(int $userId, array $data, ?int $templateId = null): VAPProposalTemplate
    {
        return $this->save($userId, $data, $templateId, $templateId === null ? 'add_proposal_templates' : 'edit_proposal_templates');
    }

    /** @param array<string,mixed> $data */
    public function executeImport(int $userId, array $data, ?int $templateId = null): VAPProposalTemplate
    {
        return $this->save($userId, $data, $templateId, 'import_proposal_templates');
    }

    /** @param array<string,mixed> $data */
    private function save(int $userId, array $data, ?int $templateId, string $permission): VAPProposalTemplate
    {
        return DB::transaction(function () use ($userId, $data, $templateId, $permission): VAPProposalTemplate {
            $creating = $templateId === null;
            $operator = $this->operator($userId, $permission);
            $template = $creating ? new VAPProposalTemplate : VAPProposalTemplate::query()->lockForUpdate()->findOrFail($templateId);
            $importing = $permission === 'import_proposal_templates';
            $validated = $importing ? $this->validation->validateImport($data) : $this->validation->validate($data, $creating ? null : $template);
            $history = $creating ? [] : $this->history($template);
            $previous = $template->only(array_keys($validated));
            if ($creating) {
                $template->fill([
                    'name' => $validated['name'], 'content' => $validated['content'], 'user_id' => $userId,
                    'category' => $validated['category'] ?? 'general', 'description' => $validated['description'] ?? null,
                    'theme_preset' => $validated['theme_preset'] ?? null, 'is_active' => $validated['is_active'] ?? true,
                    'layout_schema' => $validated['layout_schema'] ?? [], 'export_settings' => $validated['export_settings'] ?? [],
                ]);
                $template->deleted_at = null;
                $template->setCreatedAt($template->freshTimestamp());
            } else {
                $template->fill($validated);
                if (! $template->isDirty()) {
                    $this->operator($userId, $permission);

                    return $template;
                }
            }

            $template->setUpdatedAt($template->freshTimestamp());
            $expected = $template->getAttributes();
            $properties = ['old' => $creating ? [] : $previous,
                'attributes' => $template->only($creating ? $template->getFillable() : array_keys($validated))];
            if ($importing) {
                $properties['origin'] = 'import';
            }
            abort_unless(VAPProposalTemplate::withoutTimestamps(fn (): bool => $template->save()),
                409, 'Não foi possível guardar o modelo de proposta.');
            if ($creating) {
                abort_unless($template->exists && $template->wasRecentlyCreated && (int) $template->getKey() > 0,
                    409, 'Não foi possível identificar o modelo criado.');
                $expected[$template->getKeyName()] = $template->getKey();
            }

            $event = $creating ? 'created' : 'updated';
            $description = $creating ? 'criou o modelo de proposta' : 'actualizou o modelo de proposta';
            if ($importing) {
                $description = $creating ? 'importou um novo modelo de proposta' : 'importou uma revisão do modelo de proposta';
            }
            $auditExpected = ['log_name' => config('activitylog.default_log_name'), 'event' => $event,
                'description' => $description, 'subject_type' => $template->getMorphClass(), 'subject_id' => (int) $template->id,
                'causer_type' => $operator->getMorphClass(), 'causer_id' => $userId, 'properties' => $properties];
            $audit = activity()->performedOn($template)->causedBy($operator)->event($event)->withProperties($properties)->log($description);
            abort_unless($audit?->exists, 409, 'Não foi possível registar o histórico do modelo.');

            $this->operator($userId, $permission);
            $stored = VAPProposalTemplate::withTrashed()->find($template->id);
            abort_unless($stored && $this->orderedAttributes($stored->getAttributes()) === $this->orderedAttributes($expected),
                409, 'O modelo guardado não corresponde à operação solicitada.');
            $storedAudit = ISOActivityLog::withoutGlobalScopes()->find($audit->id);
            abort_unless($storedAudit, 409, 'O histórico do modelo não foi preservado.');
            $actual = $storedAudit->only(array_keys($auditExpected));
            $actual['subject_id'] = (int) $storedAudit->subject_id;
            $actual['causer_id'] = (int) $storedAudit->causer_id;
            $actual['properties'] = $storedAudit->properties->all();
            abort_unless($actual === $auditExpected, 409, 'O histórico guardado não corresponde à operação.');
            $retained = $this->history($stored);
            unset($retained[$audit->id]);
            abort_unless($retained === $history, 409, 'O histórico anterior do modelo não foi preservado.');

            return $stored;
        }, 3);
    }

    private function operator(int $userId, string $permission): User
    {
        $operator = User::query()->where('is_active', true)->whereNotNull('email_verified_at')->lockForUpdate()->find($userId);
        abort_unless($operator?->can($permission), 403);

        return $operator;
    }

    /** @return array<int,array<string,mixed>> */
    private function history(VAPProposalTemplate $template): array
    {
        return ISOActivityLog::withoutGlobalScopes()->where('subject_id', $template->id)
            ->whereIn('subject_type', [$template->getMorphClass(), ProposalTemplate::class, VAPProposalTemplate::class])
            ->orderBy('id')->lockForUpdate()->get()->mapWithKeys(fn (ISOActivityLog $audit): array => [$audit->id => $audit->getAttributes()])->all();
    }

    /** @param array<string,mixed> $attributes
     * @return array<string,mixed>
     */
    private function orderedAttributes(array $attributes): array
    {
        ksort($attributes);

        return $attributes;
    }
}
