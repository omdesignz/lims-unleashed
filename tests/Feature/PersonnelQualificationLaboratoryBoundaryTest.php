<?php

namespace Tests\Feature;

use App\Models\PersonnelQualification;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Support\PersonnelQualificationGate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class PersonnelQualificationLaboratoryBoundaryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_scientific_authorization_and_qms_monitoring_use_only_owning_lab(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $operator = $this->member($lab);
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $operator->id]);
        $localQualification = $this->qualification($lab, $operator, 'verify_results', true);
        $peerQualification = $this->qualification($peer, $operator, 'verify_results', false);

        $gate = app(PersonnelQualificationGate::class);
        $this->assertTrue($gate->allows($operator, 'verify_results', null, $lab->id));
        $this->assertFalse($gate->allows($operator, 'verify_results', null, $peer->id));

        $this->actingAs($operator)->withSession(['active_lab_id' => $lab->id]);
        $dashboard = $this->get(route('qms.index'));
        $dashboard->assertOk();
        $this->assertSame($localQualification->id, data_get($dashboard->viewData('page'), 'props.expiringQualifications.0.id'));
        $this->assertNotSame($peerQualification->id, data_get($dashboard->viewData('page'), 'props.expiringQualifications.0.id'));
    }

    public function test_user_edit_replaces_only_active_lab_qualifications(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $admin = $this->member($lab);
        $target = User::factory()->create(['gender' => 'O']);
        DB::table('lab_user')->insert([
            ['lab_id' => $lab->id, 'user_id' => $target->id],
            ['lab_id' => $peer->id, 'user_id' => $target->id],
        ]);
        $localQualification = $this->qualification($lab, $target, 'sample_intake_validation', true);
        $peerQualification = $this->qualification($peer, $target, 'approve_results', true);

        $this->actingAs($admin)->withSession(['active_lab_id' => $lab->id]);
        $edit = $this->get(route('users.edit', $target));
        $edit->assertOk();
        $this->assertCount(1, data_get($edit->viewData('page'), 'props.record.personnel_qualifications'));
        $this->assertSame($localQualification->id, data_get($edit->viewData('page'), 'props.record.personnel_qualifications.0.id'));

        $this->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'username' => $target->username,
            'gender' => 'O',
            'departments' => [],
            'roles' => [],
            'permissions' => [],
            'personnel_qualifications' => [[
                'lab_id' => $peer->id,
                'capability' => 'sample_intake_validation',
                'authorized_from' => now()->subDay()->toDateString(),
                'authorized_until' => now()->addMonths(2)->toDateString(),
                'training_reference' => 'REVISED-LOCAL',
                'is_active' => true,
            ]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('personnel_qualifications', ['id' => $peerQualification->id, 'lab_id' => $peer->id]);
        $this->assertDatabaseMissing('personnel_qualifications', ['id' => $localQualification->id]);
        $this->assertDatabaseHas('personnel_qualifications', [
            'user_id' => $target->id,
            'lab_id' => $lab->id,
            'training_reference' => 'REVISED-LOCAL',
        ]);
    }

    public function test_qualification_owner_cannot_be_changed(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $qualification = $this->qualification($lab, $this->member($lab), 'verify_results', true);

        $this->expectException(LogicException::class);
        $qualification->update(['lab_id' => $peer->id]);
    }

    private function member(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    private function qualification(VAPLab $lab, User $user, string $capability, bool $active): PersonnelQualification
    {
        return PersonnelQualification::query()->create([
            'lab_id' => $lab->id,
            'user_id' => $user->id,
            'qualified_by_id' => $user->id,
            'capability' => $capability,
            'authorized_from' => now()->subDay()->toDateString(),
            'authorized_until' => now()->addDays(15)->toDateString(),
            'training_completed_at' => now()->subDay()->toDateString(),
            'training_reference' => 'QUAL-'.$lab->id,
            'is_active' => $active,
        ]);
    }
}
