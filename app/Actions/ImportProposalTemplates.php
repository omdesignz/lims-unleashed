<?php

namespace App\Actions;

use App\Models\ISOActivityLog;
use App\Models\ProposalTemplate;
use App\Models\User;
use App\Models\VAPProposalTemplate;
use App\Support\ProposalTemplateValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportProposalTemplates
{
    public function __construct(private ProposalTemplateValidation $validation, private SaveProposalTemplate $save) {}

    /** @param list<array<string,mixed>> $rows
     * @return array{created:int,updated:int,unchanged:int}
     */
    public function execute(int $userId, array $rows): array
    {
        Validator::make(['rows' => $rows], ['rows' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'rows.*' => ['required', 'array'], 'rows.*.name' => ['required', 'string', 'distinct:strict']])->validate();
        $payloads = [];
        foreach ($rows as $index => $row) {
            try {
                $validated = $this->validation->validateImport($row);
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn (array $messages, string $field): array => ['rows.'.$index.'.'.$field => $messages])->all());
            }
            $payloads[] = ['name' => $validated['name'], 'content' => $validated['content'],
                'category' => $validated['category'] ?? 'general', 'description' => $validated['description'] ?? null,
                'theme_preset' => $validated['theme_preset'] ?? null, 'is_active' => $validated['is_active'] ?? true,
                'layout_schema' => $validated['layout_schema'] ?? [], 'export_settings' => $validated['export_settings'] ?? []];
        }

        return DB::transaction(function () use ($userId, $payloads): array {
            $this->operator($userId);
            $names = array_column($payloads, 'name');
            $records = VAPProposalTemplate::query()->whereIn('name', $names)->orderBy('id')->lockForUpdate()->get();
            $groups = $records->groupBy('name');
            foreach ($groups as $group) {
                if ($group->count() !== 1) {
                    throw ValidationException::withMessages(['template_file' => 'Existem nomes de modelos activos ambíguos. Corrija-os antes de importar.']);
                }
            }
            $before = [];
            foreach ($records as $record) {
                $before[$record->id] = ['root' => $record->getAttributes(), 'history' => $this->history($record)];
            }
            $expected = [];
            $result = ['created' => 0, 'updated' => 0, 'unchanged' => 0];
            foreach ($payloads as $payload) {
                $target = $groups->get($payload['name'])?->first();
                if ($target) {
                    $current = VAPProposalTemplate::withTrashed()->find($target->id);
                    abort_unless($current && $current->getAttributes() === $before[$target->id]['root']
                        && $this->history($current) === $before[$target->id]['history'],
                        409, 'Um modelo da importação foi alterado durante a operação.');
                    $intended = clone $target;
                    $intended->fill($payload);
                    $changed = $intended->isDirty();
                } else {
                    abort_if(VAPProposalTemplate::query()->where('name', $payload['name'])->exists(),
                        409, 'Um nome da importação foi alterado durante a operação.');
                    $changed = true;
                }
                $stored = $this->save->executeImport($userId, $payload, $target?->id);
                $expected[$stored->id] = ['root' => $stored->getAttributes(), 'history' => $this->history($stored)];
                $result[$target ? ($changed ? 'updated' : 'unchanged') : 'created']++;
            }
            $this->operator($userId);
            $final = VAPProposalTemplate::query()->whereIn('name', $names)->orderBy('id')->get();
            abort_unless($final->count() === count($payloads)
                && $final->modelKeys() === collect(array_keys($expected))->sort()->values()->all(),
                409, 'Os modelos guardados não correspondem à importação.');
            foreach ($final as $stored) {
                abort_unless($stored->getAttributes() === $expected[$stored->id]['root']
                    && $this->history($stored) === $expected[$stored->id]['history'],
                    409, 'A importação ou o seu histórico não foram preservados.');
            }

            return $result;
        }, 3);
    }

    private function operator(int $userId): User
    {
        $operator = User::query()->where('is_active', true)->whereNotNull('email_verified_at')->lockForUpdate()->find($userId);
        abort_unless($operator?->can('import_proposal_templates'), 403);

        return $operator;
    }

    /** @return array<int,array<string,mixed>> */
    private function history(VAPProposalTemplate $template): array
    {
        return ISOActivityLog::withoutGlobalScopes()->where('subject_id', $template->id)
            ->whereIn('subject_type', [$template->getMorphClass(), ProposalTemplate::class, VAPProposalTemplate::class])
            ->orderBy('id')->lockForUpdate()->get()->mapWithKeys(fn (ISOActivityLog $audit): array => [$audit->id => $audit->getAttributes()])->all();
    }
}
