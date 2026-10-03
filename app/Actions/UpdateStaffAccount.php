<?php

namespace App\Actions;

use App\Models\PersonnelQualification;
use App\Models\User;
use App\Models\VAPLab;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\StaffAccountAccess;
use App\Services\StaffAccountHistory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class UpdateStaffAccount
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $laboratoryAccess, private readonly StaffAccountAccess $accounts, private readonly StaffAccountHistory $history) {}

    /** @param array<string,mixed> $data */
    public function execute(int $actorId, int $labId, int $targetId, array $data): void
    {
        $unexpected = array_diff(array_keys($data), [...StaffAccountAccess::PROFILE_FIELDS, ...StaffAccountAccess::GLOBAL_ACCESS_FIELDS, 'personnel_qualifications']);
        if ($unexpected !== []) {
            throw ValidationException::withMessages(array_fill_keys($unexpected, 'Este campo não pode ser alterado neste formulário.'));
        }
        DB::transaction(function () use ($actorId, $labId, $targetId, $data): void {
            $lab = VAPLab::query()->lockForUpdate()->findOrFail($labId);
            DB::table('lab_user')->where('lab_id', $labId)->whereIn('user_id', [$actorId, $targetId])->orderBy('user_id')->lockForUpdate()->get();
            User::query()->whereKey([$actorId, $targetId])->orderBy('id')->lockForUpdate()->get();
            $actor = $this->laboratoryAccess->operator($actorId, $labId, 'edit_users');
            $this->accounts->authorizeUpdate($actor, $targetId, $data);
            $membership = DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $targetId)->lockForUpdate()->first();
            abort_unless($membership, 404);
            $target = User::query()->lockForUpdate()->findOrFail($targetId);
            $intendedMemberships = DB::table('lab_user')->where('user_id', $targetId)->orderBy('id')->get()->toArray();
            $beforeHistory = $this->history->snapshot($targetId);
            $intendedQualifications = $target->personnelQualifications()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $intendedAccess = [];
            foreach (StaffAccountAccess::GLOBAL_ACCESS_FIELDS as $field) {
                $intendedAccess[$field] = $target->{$field}()->pluck($field.'.id')->map(fn ($id): int => (int) $id)->sort()->values()->all();
            }
            $beforeAccount = [...$this->historyFields($target, [...StaffAccountAccess::PROFILE_FIELDS, 'email_verified_at']), ...$intendedAccess];
            $beforeQualifications = $this->qualificationHistory($intendedQualifications->where('lab_id', $labId)->values()->all());
            $profile = Arr::only($data, StaffAccountAccess::PROFILE_FIELDS);
            if (isset($profile['email']) && $profile['email'] !== $target->email) {
                $profile['email_verified_at'] = null;
            }
            $target->fill($profile);
            $intended = clone $target;
            if (array_key_exists('dob', $profile)) {
                $intended->birthday = $intended->dob?->format('m-d');
            }
            if ($target->isDirty() && ! $target->save()) {
                throw new LogicException('Staff account changes were not persisted.');
            }
            foreach (['departments', 'roles', 'permissions'] as $field) {
                if (! array_key_exists($field, $data)) {
                    continue;
                }
                if ($field === 'departments') {
                    $ids = collect($data[$field])->pluck('department_id')->unique()->all();
                    $target->departments()->sync($ids);
                } elseif ($field === 'roles') {
                    $ids = $data[$field];
                    $target->syncRoles($data[$field]);
                } else {
                    $ids = $data[$field];
                    $target->syncPermissions($data[$field]);
                }
                $intendedAccess[$field] = collect($ids)->map(fn ($id): int => (int) $id)->sort()->values()->all();
            }
            if (array_key_exists('personnel_qualifications', $data)) {
                foreach ($target->personnelQualifications()->where('lab_id', $labId)->lockForUpdate()->get() as $qualification) {
                    if (! $qualification->delete()) {
                        throw new LogicException('Staff qualification removal was not persisted.');
                    }
                    $intendedQualifications->forget($qualification->id);
                }
                foreach ($data['personnel_qualifications'] as $attributes) {
                    $qualification = new PersonnelQualification([...$attributes, 'lab_id' => $labId, 'user_id' => $target->id, 'qualified_by_id' => $actorId]);
                    $intendedQualification = clone $qualification;
                    if (! $qualification->save()) {
                        throw new LogicException('Staff qualification changes were not persisted.');
                    }
                    $intendedQualification->id = $qualification->id;
                    $intendedQualifications[$qualification->id] = $intendedQualification;
                }
            }
            $afterAccount = [...$this->historyFields($intended, [...StaffAccountAccess::PROFILE_FIELDS, 'email_verified_at']), ...$intendedAccess];
            $changes = [];
            foreach ($beforeAccount as $field => $value) {
                if ($value !== $afterAccount[$field]) {
                    $changes[$field] = ['old' => $value, 'new' => $afterAccount[$field]];
                }
            }
            $audits = [];
            if ($changes !== []) {
                $audits[] = $this->history->record('staff_account', $actor, $target,
                    ['origin_lab_id' => $labId, 'target_user_id' => $targetId, 'changes' => $changes]);
            }
            $afterQualifications = $this->qualificationHistory($intendedQualifications->where('lab_id', $labId)->sortKeys()->values()->all());
            if ($beforeQualifications !== $afterQualifications) {
                $audits[] = $this->history->record('personnel_qualifications', $actor, $lab,
                    ['lab_id' => $labId, 'target_user_id' => $targetId, 'old' => $beforeQualifications, 'new' => $afterQualifications]);
            }
            $stored = $target->fresh();
            if (! $stored || ! $this->sameEvidence($intended, $stored, array_keys(Arr::except($intended->getAttributes(), ['updated_at'])))) {
                throw new LogicException('Persisted staff account differs from the intended account.');
            }
            foreach ($intendedAccess as $field => $ids) {
                if ($ids !== $stored->{$field}()->pluck($field.'.id')->map(fn ($id): int => (int) $id)->sort()->values()->all()) {
                    throw new LogicException('Persisted staff access differs from the intended access.');
                }
            }
            $qualifications = $target->personnelQualifications()->get()->keyBy('id');
            if ($qualifications->keys()->sort()->values()->all() !== $intendedQualifications->keys()->sort()->values()->all()) {
                throw new LogicException('Persisted staff qualifications differ from the intended qualifications.');
            }
            foreach ($intendedQualifications as $id => $qualification) {
                if (! $this->sameEvidence($qualification, $qualifications[$id], $qualification->getFillable())) {
                    throw new LogicException('Persisted staff qualifications differ from the intended qualifications.');
                }
            }
            $resetOwnVerification = $actorId === $targetId && array_key_exists('email_verified_at', $profile);
            if ($resetOwnVerification) {
                VAPLab::query()->lockForUpdate()->findOrFail($labId);
                $actor = User::query()->where('is_active', true)->findOrFail($actorId);
                abort_unless($actor->can('edit_users') && DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $actorId)->exists(), 403);
            } else {
                $actor = $this->laboratoryAccess->operator($actorId, $labId, 'edit_users');
            }
            $this->accounts->authorizeUpdate($actor, $targetId, $data, $resetOwnVerification);
            abort_unless(DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $targetId)->exists(), 404);
            if (DB::table('lab_user')->where('user_id', $targetId)->orderBy('id')->get()->toArray() != $intendedMemberships) {
                throw new LogicException('Persisted staff memberships differ from the intended memberships.');
            }
            $this->history->assertPersisted($audits, $targetId, $beforeHistory);
        });
    }

    /** @param list<PersonnelQualification> $qualifications
     * @return list<array<string,mixed>>
     */
    private function qualificationHistory(array $qualifications): array
    {
        return array_map(fn (PersonnelQualification $qualification): array => ['id' => $qualification->id,
            ...$this->historyFields($qualification, $qualification->getFillable())], $qualifications);
    }

    /** @param list<string> $fields
     * @return array<string,mixed>
     */
    private function historyFields(Model $model, array $fields): array
    {
        $values = [];
        foreach ($fields as $field) {
            $value = $model->getAttribute($field);
            $values[$field] = $value instanceof DateTimeInterface ? $value->format('Y-m-d H:i:s.uP') : $value;
        }

        return $values;
    }

    /** @param array<int,string> $fields */
    private function sameEvidence(Model $intended, Model $stored, array $fields): bool
    {
        foreach ($fields as $field) {
            $left = $intended->getAttribute($field);
            $right = $stored->getAttribute($field);
            if ($left instanceof DateTimeInterface && $right instanceof DateTimeInterface) {
                if ($left->format('Y-m-d H:i:s.uP') !== $right->format('Y-m-d H:i:s.uP')) {
                    return false;
                }
            } elseif ($left !== $right) {
                return false;
            }
        }

        return true;
    }
}
