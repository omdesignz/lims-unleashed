<?php

namespace Tests\Feature;

use App\Models\ProficiencyTest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProficiencyManagementTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->first();

        $this->assertNotNull($admin, 'Expected at least one verified admin user for proficiency testing.');

        return $admin;
    }

    public function test_admin_can_open_the_proficiency_management_index(): void
    {
        $this->actingAs($this->verifiedAdmin())
            ->get(route('proficiency_tests.index'))
            ->assertSuccessful();
    }

    public function test_admin_can_create_and_update_interlaboratory_programmes(): void
    {
        $user = $this->verifiedAdmin();

        $payload = [
            'name' => 'Interlaboratory Wheat Round',
            'scheme_type' => 'interlaboratory',
            'provider_name' => 'Regional Reference Network',
            'round_reference' => 'ILC-2026-04',
            'status' => 'planned',
            'date' => now()->toDateString(),
            'scheduled_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'scope' => 'Mycotoxins and moisture in wheat flour',
            'outcome' => 'pending',
            'z_score' => null,
            'corrective_actions' => null,
            'notes' => 'Initial enrollment completed.',
            'results' => [],
        ];

        $this->actingAs($user)
            ->post(route('proficiency_tests.store'), $payload)
            ->assertRedirect();

        $programme = ProficiencyTest::query()->where('round_reference', 'ILC-2026-04')->first();

        $this->assertNotNull($programme, 'Expected the interlaboratory programme to be created.');
        $this->assertSame('interlaboratory', $programme->scheme_type);

        $this->actingAs($user)
            ->put(route('proficiency_tests.update', ['test' => $programme->id]), array_merge($payload, [
                'status' => 'reviewed',
                'outcome' => 'questionable',
                'z_score' => 2.75,
                'corrective_actions' => 'Repeat analyst review and check calibration records.',
            ]))
            ->assertRedirect();

        $programme->refresh();

        $this->assertSame('reviewed', $programme->status);
        $this->assertSame('questionable', $programme->outcome);
        $this->assertSame('2.75', (string) $programme->z_score);
    }
}
