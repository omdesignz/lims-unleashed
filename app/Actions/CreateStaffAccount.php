<?php

namespace App\Actions;

use App\Models\User;
use App\Models\VAPLab;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\StaffAccountAccess;
use App\Services\StaffAccountHistory;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use LogicException;

class CreateStaffAccount
{
    private const NULLABLE_FIELDS = ['email_verified_at', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
        'profile_photo_path', 'username', 'password_changed_at', 'primary_phone', 'secondary_phone', 'id_number', 'dob', 'birthday', 'gender', 'deleted_at',
        'google_id', 'github_id', 'microsoft_id', 'x_id', 'microsoft_data', 'last_login_at', 'last_activity_at'];

    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access, private readonly StaffAccountAccess $accounts, private readonly StaffAccountHistory $history) {}

    /** @param array{name:string,email:string,gender:string,password:string,username?:?string,departments?:list<array{department_id:int}>,password_confirmation?:string} $data */
    public function execute(int $actorId, int $labId, array $data): User
    {
        $unexpected = array_diff(array_keys($data), ['name', 'email', 'username', 'gender', 'password', 'password_confirmation', 'departments']);
        if ($unexpected !== []) {
            throw ValidationException::withMessages(array_fill_keys($unexpected, 'Este campo não pode ser alterado neste formulário.'));
        }
        try {
            return DB::transaction(function () use ($actorId, $labId, $data): User {
                $lab = VAPLab::query()->lockForUpdate()->findOrFail($labId);
                DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $actorId)->lockForUpdate()->get();
                $actor = $this->access->operator($actorId, $labId, 'add_users');
                $this->accounts->authorizeSystem($actor, 'add_users');
                $departmentIds = collect($data['departments'] ?? [])->pluck('department_id')->map(fn ($id): int => (int) $id)->unique()->sort()->values()->all();
                if (DB::table('departments')->whereIn('id', $departmentIds)->count() !== count($departmentIds)) {
                    throw ValidationException::withMessages(['departments' => 'Confirme os departamentos seleccionados.']);
                }
                $target = new User([...Arr::only($data, ['name', 'email', 'username', 'gender']),
                    'password' => Hash::make($data['password']), 'is_active' => true, 'password_changed_by_user' => false, 'theme' => 'light']);
                $target->updateTimestamps();
                $intended = [...array_fill_keys(self::NULLABLE_FIELDS, null), ...$target->getAttributes()];
                if (! $target->save() || ! $target->exists || ! $target->id) {
                    throw new LogicException('New staff account was not persisted.');
                }
                $intended['id'] = $target->id;
                $target->departments()->sync($departmentIds);
                $joinedAt = now()->toDateTimeString();
                $membership = ['lab_id' => $labId, 'user_id' => $target->id, 'can_view_network' => false, 'can_manage_branding' => false,
                    'created_at' => $joinedAt, 'updated_at' => $joinedAt];
                $membership['id'] = DB::table('lab_user')->insertGetId($membership);
                $departments = DB::table('department_user')->where('user_id', $target->id)->orderBy('department_id')->get()->map(fn (object $row): array => (array) $row)->all();
                if (array_column($departments, 'department_id') !== $departmentIds) {
                    throw new LogicException('New staff departments differ from the intended departments.');
                }
                foreach ($departments as $row) {
                    if ($this->canonical(Arr::except($row, ['id'])) !== $this->canonical(['department_id' => $row['department_id'], 'user_id' => $target->id, 'deleted_at' => null, 'created_at' => null, 'updated_at' => null])) {
                        throw new LogicException('New staff department assignment evidence differs from the intended evidence.');
                    }
                }
                $audits = [$this->history->record('staff_account', $actor, $target,
                    ['origin_lab_id' => $labId, 'target_user_id' => $target->id,
                        'attributes' => [...Arr::only($data, ['name', 'email', 'username', 'gender']), 'departments' => $departmentIds, 'is_active' => true, 'email_verified' => false]], 'created')];
                $audits[] = $this->history->record('laboratory_membership', $actor, $lab,
                    ['lab_id' => $labId, 'target_user_id' => $target->id, 'joined' => true], 'membership_added');
                $actor = $this->access->operator($actorId, $labId, 'add_users');
                $this->accounts->authorizeSystem($actor, 'add_users');
                $stored = User::withTrashed()->find($target->id);
                if (! $stored || $this->canonical($stored->getAttributes()) !== $this->canonical($intended)
                    || $this->canonical(DB::table('lab_user')->where('user_id', $target->id)->get()->map(fn (object $row): array => (array) $row)->all()) !== $this->canonical([$membership])
                    || DB::table('department_user')->where('user_id', $target->id)->orderBy('department_id')->get()->map(fn (object $row): array => (array) $row)->all() !== $departments) {
                    throw new LogicException('Persisted new staff evidence differs from the intended evidence.');
                }
                $this->assertEmptyAccessAndCredentials($target);
                $this->history->assertPersisted($audits, $target->id, []);

                return $stored;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $field = match ($exception->index) {
                'users_email_unique' => 'email',
                'users_username_unique' => 'username',
                default => null,
            };
            if (! $field) {
                throw $exception;
            }
            throw ValidationException::withMessages([$field => 'Já existe uma conta com este valor. Utilize a adesão para contas existentes.']);
        }
    }

    private function assertEmptyAccessAndCredentials(User $target): void
    {
        foreach (['model_has_roles', 'model_has_permissions'] as $table) {
            if (DB::table($table)->where('model_id', $target->id)->whereIn('model_type', [$target->getMorphClass(), User::class])->exists()) {
                throw new LogicException('New staff account received unintended global access.');
            }
        }
        if (DB::table('personnel_qualifications')->where('user_id', $target->id)->exists()) {
            throw new LogicException('New staff account received unintended qualifications.');
        }
        foreach (['passkeys' => ['authenticatable_id', 'authenticatable_type'], 'personal_access_tokens' => ['tokenable_id', 'tokenable_type'], 'media' => ['model_id', 'model_type']] as $table => [$id, $type]) {
            if (DB::table($table)->where($id, $target->id)->whereIn($type, [$target->getMorphClass(), User::class])->exists()) {
                throw new LogicException('New staff account received unintended credentials or media.');
            }
        }
    }

    /** @param array<mixed> $values
     * @return array<mixed>
     */
    private function canonical(array $values): array
    {
        foreach ($values as &$value) {
            if (is_array($value)) {
                $value = $this->canonical($value);
            }
        }
        unset($value);
        if (! array_is_list($values)) {
            ksort($values);
        }

        return $values;
    }
}
