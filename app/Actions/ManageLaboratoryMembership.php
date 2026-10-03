<?php

namespace App\Actions;

use App\Models\ISOActivityLog;
use App\Models\User;
use App\Models\VAPLab;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\StaffAccountHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageLaboratoryMembership
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    public function join(int $actorId, int $labId, string $email): void
    {
        $this->change($actorId, $labId, $email, true);
    }

    public function remove(int $actorId, int $labId, int $targetId): void
    {
        $this->change($actorId, $labId, $targetId, false);
    }

    private function change(int $actorId, int $labId, string|int $identity, bool $joining): void
    {
        abort_if(request()->session()->has('impersonate'), 403);
        DB::transaction(function () use ($actorId, $labId, $identity, $joining): void {
            $labRow = VAPLab::query()->lockForUpdate()->toBase()->find($labId);
            abort_unless($labRow, 404);
            $lab = new VAPLab;
            $lab->setRawAttributes((array) $labRow, true);
            $lab->exists = true;
            $labAttributes = $lab->getAttributes();
            $permission = $joining ? 'add_users' : 'delete_users';
            $targetId = $joining ? User::query()->where('email', $identity)->toBase()->value('id') : User::withTrashed()->whereKey($identity)->toBase()->value('id');
            if (! $targetId) {
                $this->access->operator($actorId, $labId, $permission);
                $this->unavailable($joining);
            }
            $targetId = (int) $targetId;
            DB::table('lab_user')->where('lab_id', $labId)->whereIn('user_id', [$actorId, $targetId])->orderBy('user_id')->lockForUpdate()->get();
            User::withTrashed()->whereKey([$actorId, $targetId])->orderBy('id')->lockForUpdate()->toBase()->get();
            $operatorEvidence = $this->operatorEvidence($actorId, $labId);
            $actor = $this->access->operator($actorId, $labId, $permission);
            $targetRow = User::withTrashed()->toBase()->find($targetId);
            abort_unless($targetRow, 404);
            $target = new User;
            $target->setRawAttributes((array) $targetRow, true);
            $target->exists = true;
            abort_if($actorId === $target->id, 422, 'Não pode remover ou alterar a sua própria adesão neste formulário.');
            if ($joining && ($target->trashed() || ! $target->is_active || ! $target->hasVerifiedEmail() || $target->email !== $identity)) {
                $this->unavailable(true);
            }
            $before = $this->evidence($target->id);
            $history = $this->history($targetId);
            $memberships = $before['lab_user'];
            $local = collect($memberships)->first(fn (array $row): bool => (int) $row['lab_id'] === $labId);
            if (! $joining && ! $local) {
                if (ISOActivityLog::withoutGlobalScopes()->where('log_name', 'laboratory_membership')->where('event', 'membership_removed')
                    ->where('subject_type', $lab->getMorphClass())->where('subject_id', $labId)
                    ->where('properties->lab_id', $labId)->where('properties->target_user_id', $target->id)->toBase()->exists()) {
                    $this->assertFinalEvidence($actorId, $labId, $permission, $targetId, $labAttributes, $operatorEvidence, $before, $history);

                    return;
                }
                abort(404);
            }
            if ($joining && $local) {
                $this->assertFinalEvidence($actorId, $labId, $permission, $targetId, $labAttributes, $operatorEvidence, $before, $history);

                return;
            }
            if ($joining) {
                $attributes = ['lab_id' => $labId, 'user_id' => $target->id, 'can_view_network' => false,
                    'can_manage_branding' => false, 'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString()];
                $id = DB::table('lab_user')->insertGetId($attributes);
                $memberships[] = ['id' => $id, ...$attributes];
            } else {
                abort_unless(DB::table('lab_user')->where('id', $local['id'])->where('lab_id', $labId)->delete() === 1, 409);
                $memberships = array_values(array_filter($memberships, fn (array $row): bool => (int) $row['lab_id'] !== $labId));
            }
            foreach ($memberships as &$row) {
                ksort($row);
            }
            unset($row);
            $before['lab_user'] = $memberships;
            $properties = ['lab_id' => $labId, 'target_user_id' => $target->id, 'joined' => $joining];
            $event = $joining ? 'membership_added' : 'membership_removed';
            $description = $joining ? 'adicionou um membro ao laboratório' : 'removeu um membro do laboratório';
            $auditExpected = [];
            $audit = activity('laboratory_membership')->causedBy($actor)->performedOn($lab)
                ->event($event)->withProperties($properties)->tap(function (ISOActivityLog $entry) use ($description, &$auditExpected): void {
                    $entry->description = $description;
                    $entry->setCreatedAt($entry->freshTimestamp());
                    $entry->setUpdatedAt($entry->created_at);
                    $auditExpected = $entry->getAttributes();
                })->log($description);
            abort_unless($audit?->exists && $audit->id && ! isset($history[$audit->id]), 409, 'Não foi possível registar a adesão ao laboratório.');
            $auditExpected['id'] = $audit->id;
            $this->assertFinalEvidence($actorId, $labId, $permission, $targetId, $labAttributes, $operatorEvidence, $before, $history, $auditExpected);
        });
    }

    /**
     * @param  array<string,mixed>  $labAttributes
     * @param  array{user:array<string,mixed>,membership:array<string,mixed>}  $operatorEvidence
     * @param  array<string,list<array<string,mixed>>>  $evidence
     * @param  array<int,array<string,mixed>>  $history
     * @param  array<string,mixed>|null  $newAudit
     */
    private function assertFinalEvidence(int $actorId, int $labId, string $permission, int $targetId, array $labAttributes, array $operatorEvidence, array $evidence, array $history, ?array $newAudit = null): void
    {
        $this->access->operator($actorId, $labId, $permission);
        abort_if(request()->session()->has('impersonate'), 403);
        $storedLab = VAPLab::withTrashed()->toBase()->find($labId);
        abort_unless($storedLab && $storedLab->deleted_at === null
            && User::query()->whereKey($actorId)->where('is_active', true)->whereNotNull('email_verified_at')
                ->whereIn('id', DB::table('lab_user')->where('lab_id', $labId)->select('user_id'))->toBase()->exists(), 403);
        abort_unless($storedLab && (array) $storedLab === $labAttributes, 409, 'Os dados do laboratório mudaram durante a operação.');
        abort_unless($this->operatorEvidence($actorId, $labId) === $operatorEvidence, 409, 'Os dados ou a adesão do operador mudaram durante a operação.');
        abort_unless($this->evidence($targetId) === $evidence, 409, 'A adesão ou os dados partilhados mudaram durante a operação.');
        $retained = $this->history($targetId, array_keys($history));
        if ($newAudit !== null) {
            $storedAudit = ISOActivityLog::withoutGlobalScopes()->toBase()->find($newAudit['id']);
            abort_unless($storedAudit && $this->canonicalAudit((array) $storedAudit) === $this->canonicalAudit($newAudit),
                409, 'Não foi possível registar a adesão ao laboratório.');
            unset($retained[$newAudit['id']]);
        }
        abort_unless($retained === $history, 409, 'O histórico anterior da conta não foi preservado.');
    }

    /** @return array{user:array<string,mixed>,membership:array<string,mixed>} */
    private function operatorEvidence(int $actorId, int $labId): array
    {
        return [
            'user' => (array) User::withTrashed()->toBase()->find($actorId),
            'membership' => (array) DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $actorId)->first(),
        ];
    }

    /** @param list<int> $retainedIds
     * @return array<int,array<string,mixed>>
     */
    private function history(int $targetId, array $retainedIds = []): array
    {
        return ISOActivityLog::withoutGlobalScopes()->where(function (Builder $query) use ($targetId, $retainedIds): void {
            $query->where(function (Builder $query) use ($targetId): void {
                $query->whereIn('log_name', StaffAccountHistory::RETAINED_LOGS)->where('properties->target_user_id', $targetId);
            })->orWhereIn('id', $retainedIds);
        })->orderBy('id')->lockForUpdate()->toBase()->get()->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
    }

    /** @param array<string,mixed> $attributes
     * @return array<string,mixed>
     */
    private function canonicalAudit(array $attributes): array
    {
        $attributes['properties'] = is_string($attributes['properties']) ? json_decode($attributes['properties'], true, flags: JSON_THROW_ON_ERROR) : $attributes['properties'];
        if (is_array($attributes['properties'])) {
            ksort($attributes['properties']);
        }
        ksort($attributes);

        return $attributes;
    }

    /** @return array<string,list<array<string,mixed>>> */
    private function evidence(int $targetId): array
    {
        $evidence = [];
        foreach (['users' => 'id', 'lab_user' => 'user_id', 'personnel_qualifications' => 'user_id', 'department_user' => 'user_id'] as $table => $key) {
            $evidence[$table] = DB::table($table)->where($key, $targetId)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
        }
        foreach (['model_has_roles' => 'role_id', 'model_has_permissions' => 'permission_id'] as $table => $key) {
            $evidence[$table] = DB::table($table)->where('model_type', (new User)->getMorphClass())->where('model_id', $targetId)
                ->orderBy($key)->get()->map(fn (object $row): array => (array) $row)->all();
        }

        foreach (['media' => ['model_id', 'model_type'], 'passkeys' => ['authenticatable_id', 'authenticatable_type'],
            'personal_access_tokens' => ['tokenable_id', 'tokenable_type']] as $table => [$key, $type]) {
            $evidence[$table] = DB::table($table)->where($key, $targetId)->whereIn($type, [(new User)->getMorphClass(), User::class])
                ->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
        }

        foreach ($evidence as &$rows) {
            foreach ($rows as &$row) {
                ksort($row);
            }
            unset($row);
        }
        unset($rows);

        return $evidence;
    }

    private function unavailable(bool $joining): never
    {
        if ($joining) {
            throw ValidationException::withMessages(['email' => 'Não foi possível adicionar esta conta. Confirme o email registado e contacte o administrador do sistema.']);
        }
        abort(404);
    }
}
