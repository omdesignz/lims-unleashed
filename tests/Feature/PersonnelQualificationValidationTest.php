<?php

namespace Tests\Feature;

use App\Actions\UpdateStaffAccount;
use App\Models\Department;
use App\Models\ISOActivityLog;
use App\Models\PersonnelQualification;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PersonnelQualificationValidationTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('invalidQualifications')]
    public function test_invalid_qualifications_preserve_account_access_memberships_and_all_evidence(string $path, mixed $qualifications, string $error): void
    {
        [$lab, $actor, $target] = $this->fixture();
        $before = $this->snapshot($target);
        $payload = ['name' => 'Must not be persisted', 'roles' => [], 'personnel_qualifications' => $qualifications];

        if ($path === 'http') {
            $this->put(route('users.update', $target), $payload)->assertSessionHasErrors($error);
        } else {
            try {
                app(UpdateStaffAccount::class)->execute($actor->id, $lab->id, $target->id, $payload);
                $this->fail('Direct calls must validate qualification evidence before writing.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey($error, $exception->errors());
            }
        }

        $this->assertSame($before, $this->snapshot($target));
    }

    /** @return iterable<string,array{string,mixed,string}> */
    public static function invalidQualifications(): iterable
    {
        $invalid = [
            'null collection' => [null, 'personnel_qualifications'],
            'scalar collection' => ['invalid', 'personnel_qualifications'],
            'scalar row' => [['invalid'], 'personnel_qualifications.0'],
            'empty row' => [[[]], 'personnel_qualifications.0.capability'],
            'blank capability' => [[['capability' => '   ']], 'personnel_qualifications.0.capability'],
            'long capability' => [[['capability' => str_repeat('a', 256)]], 'personnel_qualifications.0.capability'],
            'false string' => [[['capability' => 'testing', 'is_active' => 'false']], 'personnel_qualifications.0.is_active'],
            'invalid date' => [[['capability' => 'testing', 'authorized_from' => 'not-a-date']], 'personnel_qualifications.0.authorized_from'],
            'reversed dates' => [[['capability' => 'testing', 'authorized_from' => '2026-05-02', 'authorized_until' => '2026-05-01']], 'personnel_qualifications.0.authorized_until'],
            'missing department' => [[['capability' => 'testing', 'department_id' => -1]], 'personnel_qualifications.0.department_id'],
            'invalid training date' => [[['capability' => 'testing', 'training_completed_at' => 'not-a-date']], 'personnel_qualifications.0.training_completed_at'],
            'long reference' => [[['capability' => 'testing', 'training_reference' => str_repeat('a', 256)]], 'personnel_qualifications.0.training_reference'],
            'array notes' => [[['capability' => 'testing', 'notes' => []]], 'personnel_qualifications.0.notes'],
        ];
        foreach (['http', 'direct'] as $path) {
            foreach ($invalid as $name => [$data, $error]) {
                yield $path.' '.$name => [$path, $data, $error];
            }
        }
    }

    #[DataProvider('paths')]
    public function test_both_paths_normalize_controls_and_keep_ownership_server_assigned(string $path): void
    {
        [$lab, $actor, $target, $foreign] = $this->fixture();
        $department = Department::factory()->create();
        $peerBefore = $foreign->fresh()->getRawOriginal();
        $this->update($path, $actor, $lab, $target, ['personnel_qualifications' => [7 => [
            'id' => $foreign->id, 'lab_id' => $foreign->lab_id, 'user_id' => $actor->id, 'qualified_by_id' => $target->id,
            'capability' => '  Free-text training  ', 'department_id' => ['value' => (string) $department->id],
            'training_reference' => '  TRAINING  ', 'notes' => ' 0 ', 'is_active' => false,
            'authorized_from' => '2026-01-01', 'authorized_until' => '2026-12-31',
        ]]]);
        $saved = PersonnelQualification::query()->where('user_id', $target->id)->where('lab_id', $lab->id)->sole();
        $this->assertSame('Free-text training', $saved->capability);
        $this->assertSame('TRAINING', $saved->training_reference);
        $this->assertSame('0', $saved->notes);
        $this->assertFalse($saved->is_active);
        $this->assertSame($department->id, $saved->department_id);
        $this->assertSame($actor->id, $saved->qualified_by_id);
        $this->assertNotSame($foreign->id, $saved->id);
        $this->assertSame($peerBefore, $foreign->fresh()->getRawOriginal());
    }

    #[DataProvider('paths')]
    public function test_omission_preserves_rows_empty_removes_only_local_rows_and_sparse_rows_have_matching_defaults(string $path): void
    {
        [$lab, $actor, $target, $foreign] = $this->fixture();
        $before = $this->snapshot($target);
        $this->update($path, $actor, $lab, $target, []);
        $this->assertSame($before, $this->snapshot($target));
        $this->update($path, $actor, $lab, $target, ['personnel_qualifications' => [
            ['capability' => 'Sparse training'], ['capability' => 'Sparse training', 'is_active' => null, 'notes' => '  ', 'department_id' => ['value' => null]],
        ]]);
        $saved = PersonnelQualification::query()->where('user_id', $target->id)->where('lab_id', $lab->id)->get();
        $this->assertCount(2, $saved);
        foreach ($saved as $row) {
            $this->assertTrue($row->is_active);
            $this->assertNull($row->notes);
            $this->assertNull($row->department_id);
            $this->assertNull($row->authorized_from);
            $this->assertNull($row->authorized_until);
            $this->assertNull($row->training_reference);
        }
        $this->update($path, $actor, $lab, $target, ['personnel_qualifications' => []]);
        $this->assertSame(0, PersonnelQualification::query()->where('user_id', $target->id)->where('lab_id', $lab->id)->count());
        $this->assertModelExists($foreign);
        $this->assertSame(2, ISOActivityLog::withoutGlobalScopes()->where('log_name', 'personnel_qualifications')->count());
    }

    /** @return array<string,array{string}> */
    public static function paths(): array
    {
        return ['http' => ['http'], 'direct' => ['direct']];
    }

    /** @param array<string,mixed> $data */
    private function update(string $path, User $actor, VAPLab $lab, User $target, array $data): void
    {
        if ($path === 'http') {
            $this->put(route('users.update', $target), $data)->assertRedirect()->assertSessionHasNoErrors();
        } else {
            app(UpdateStaffAccount::class)->execute($actor->id, $lab->id, $target->id, $data);
        }
    }

    /** @return array{VAPLab,User,User,PersonnelQualification} */
    private function fixture(): array
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $actor->assignRole(Role::findOrCreate('admin', 'web'));
        $target = User::factory()->create();
        $target->assignRole(Role::findOrCreate('qualification-test-role', 'web'));
        DB::table('lab_user')->insert([
            ['lab_id' => $lab->id, 'user_id' => $actor->id],
            ['lab_id' => $lab->id, 'user_id' => $target->id],
            ['lab_id' => $peer->id, 'user_id' => $target->id],
        ]);
        PersonnelQualification::query()->create(['lab_id' => $lab->id, 'user_id' => $target->id, 'capability' => 'verify_results', 'is_active' => true]);
        $foreign = PersonnelQualification::query()->create(['lab_id' => $peer->id, 'user_id' => $target->id, 'capability' => 'approve_results', 'is_active' => true]);
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        request()->setLaravelSession(app('session.store'));

        return [$lab, $actor, $target, $foreign];
    }

    /** @return array<string,mixed> */
    private function snapshot(User $target): array
    {
        return [
            'account' => $target->fresh()->getRawOriginal(),
            'qualifications' => $target->personnelQualifications()->orderBy('id')->get()->map->getRawOriginal()->all(),
            'roles' => $target->roles()->orderBy('roles.id')->pluck('roles.id')->all(),
            'permissions' => $target->permissions()->orderBy('permissions.id')->pluck('permissions.id')->all(),
            'departments' => $target->departments()->orderBy('departments.id')->pluck('departments.id')->all(),
            'memberships' => DB::table('lab_user')->where('user_id', $target->id)->orderBy('id')->get()->map(fn ($row): array => (array) $row)->all(),
            'history' => ISOActivityLog::withoutGlobalScopes()->whereIn('log_name', ['staff_account', 'personnel_qualifications'])->orderBy('id')->toBase()->get()->map(fn ($row): array => (array) $row)->all(),
        ];
    }
}
