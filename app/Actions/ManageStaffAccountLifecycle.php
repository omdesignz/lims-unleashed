<?php

namespace App\Actions;

use App\Models\User;
use App\Models\VAPLab;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\StaffAccountAccess;
use App\Services\StaffAccountHistory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;

class ManageStaffAccountLifecycle
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $laboratoryAccess, private readonly StaffAccountAccess $accounts, private readonly StaffAccountHistory $history) {}

    /** @param list<int> $targetIds */
    public function archive(int $actorId, int $labId, array $targetIds, bool $archived): void
    {
        $this->execute($actorId, $labId, $targetIds, $archived ? 'archive' : 'restore');
    }

    public function setStatus(int $actorId, int $labId, int $targetId, bool $active): void
    {
        $this->execute($actorId, $labId, [$targetId], 'status', $active);
    }

    public function resetPassword(int $actorId, int $labId, int $targetId, string $password): void
    {
        $this->execute($actorId, $labId, [$targetId], 'password', $password);
    }

    /** @param list<int> $targetIds */
    private function execute(int $actorId, int $labId, array $targetIds, string $operation, bool|string|null $value = null): void
    {
        $targetIds = array_map(intval(...), $targetIds);
        sort($targetIds);
        abort_unless($targetIds !== [] && count($targetIds) === count(array_unique($targetIds)), 422);
        abort_if($operation !== 'password' && in_array($actorId, $targetIds, true), 403);
        $permission = match ($operation) {
            'archive' => 'delete_users', 'restore' => 'restore_users', 'status' => 'ban_users', 'password' => 'reset-password_users',
            default => throw new LogicException('Unsupported staff lifecycle operation.'),
        };
        DB::transaction(function () use ($actorId, $labId, $targetIds, $operation, $value, $permission): void {
            VAPLab::query()->lockForUpdate()->findOrFail($labId);
            DB::table('lab_user')->where('lab_id', $labId)->whereIn('user_id', [$actorId, ...$targetIds])->orderBy('user_id')->lockForUpdate()->get();
            User::withTrashed()->whereKey([$actorId, ...$targetIds])->orderBy('id')->lockForUpdate()->get();
            $actor = $this->laboratoryAccess->operator($actorId, $labId, $permission);
            $this->accounts->authorizeSystem($actor, $permission);
            $targets = User::withTrashed()->whereKey($targetIds)->orderBy('id')->get();
            abort_unless($targets->count() === count($targetIds)
                && DB::table('lab_user')->where('lab_id', $labId)->whereIn('user_id', $targetIds)->count() === count($targetIds), 404);
            if (in_array($operation, ['status', 'password'], true)) {
                abort_if($targets->contains(fn (User $target): bool => $target->trashed()), 404);
            }
            $expected = [];
            $historyBefore = [];
            $audits = [];
            foreach ($targets as $target) {
                $expected[$target->id] = $this->evidence($target);
                $historyBefore[$target->id] = $this->history->snapshot($target->id);
                $audits[$target->id] = [];
            }
            foreach ($targets as $target) {
                $changed = match ($operation) {
                    'archive' => ! $target->trashed(), 'restore' => $target->trashed(), 'status' => $target->is_active !== $value,
                    'password' => ! Hash::check((string) $value, $target->password),
                };
                if (! $changed) {
                    continue;
                }
                $beforeActive = $target->is_active;
                $intended = clone $target;
                if ($operation === 'archive') {
                    $startedAt = $target->freshTimestampString();
                    $persisted = $target->delete();
                    $deletedAt = $target->getAttributes()['deleted_at'] ?? null;
                    if (! is_string($deletedAt) || $deletedAt < $startedAt || $deletedAt > $target->freshTimestampString()) {
                        throw new LogicException('Staff archive timestamp differs from the intended operation.');
                    }
                    $intended->deleted_at = $deletedAt;
                    $event = 'archived';
                } elseif ($operation === 'restore') {
                    $intended->deleted_at = null;
                    $persisted = $target->restore();
                    $event = 'restored';
                } elseif ($operation === 'status') {
                    $intended->is_active = $value;
                    $target->is_active = $value;
                    $persisted = $target->save();
                    $event = $value ? 'activated' : 'deactivated';
                } else {
                    $hash = Hash::make((string) $value);
                    $intended->password = $hash;
                    $target->password = $hash;
                    $persisted = $target->save();
                    $event = 'password_reset';
                }
                if (! $persisted) {
                    throw new LogicException('Staff lifecycle change was not persisted.');
                }
                $expected[$target->id]['user'] = Arr::except($intended->getAttributes(), ['updated_at']);
                $properties = ['origin_lab_id' => $labId, 'target_user_id' => $target->id];
                $properties['change'] = match ($operation) {
                    'archive', 'restore' => ['archived' => $operation === 'archive'],
                    'status' => ['old_active' => $beforeActive, 'new_active' => $value],
                    'password' => ['credential_reset' => true],
                };
                $audits[$target->id][] = $this->history->record('staff_account', $actor, $target, $properties, $event);
            }
            $actor = $this->laboratoryAccess->operator($actorId, $labId, $permission);
            $this->accounts->authorizeSystem($actor, $permission);
            foreach ($targets as $target) {
                $stored = User::withTrashed()->find($target->id);
                if (! $stored || $this->evidence($stored) !== $expected[$target->id]) {
                    throw new LogicException('Persisted staff lifecycle evidence differs from the intended evidence.');
                }
                $this->history->assertPersisted($audits[$target->id], $target->id, $historyBefore[$target->id]);
            }
        });
    }

    /** @return array<string,mixed> */
    private function evidence(User $target): array
    {
        $evidence = ['user' => Arr::except($target->getAttributes(), ['updated_at'])];
        foreach (['personnelQualifications', 'media', 'tokens'] as $relation) {
            $evidence[$relation] = $target->{$relation}()->get()->map(fn ($model): array => $model->getAttributes())
                ->sortBy('id')->values()->all();
        }
        foreach (['lab_user', 'department_user'] as $table) {
            $evidence[$table] = DB::table($table)->where('user_id', $target->id)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
        }
        $evidence['passkeys'] = DB::table('passkeys')->where('authenticatable_id', $target->id)
            ->whereIn('authenticatable_type', [$target->getMorphClass(), User::class])->orderBy('id')->get()
            ->map(fn (object $row): array => (array) $row)->all();
        foreach (['model_has_roles' => 'role_id', 'model_has_permissions' => 'permission_id'] as $table => $key) {
            $evidence[$table] = DB::table($table)->where('model_type', $target->getMorphClass())->where('model_id', $target->id)
                ->orderBy($key)->get()->map(fn (object $row): array => (array) $row)->all();
        }

        return $evidence;
    }
}
