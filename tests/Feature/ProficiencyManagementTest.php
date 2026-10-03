<?php

namespace Tests\Feature;

use App\Jobs\CheckProficiencyTestDeadlines;
use App\Models\ProficiencyTest;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Notifications\OperationalNotification;
use App\Support\ProficiencyTestNotifier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProficiencyManagementTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $admin->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        return $admin;
    }

    private function labIdFor(User $user): int
    {
        return (int) DB::table('lab_user')->where('user_id', $user->id)->value('lab_id');
    }

    private function programme(int $labId, string $name, string $status = 'planned'): ProficiencyTest
    {
        return ProficiencyTest::query()->create([
            'lab_id' => $labId,
            'name' => $name,
            'date' => now()->toDateString(),
            'provider_name' => 'Reference Network',
            'round_reference' => $name,
            'status' => $status,
            'results' => [],
        ]);
    }

    private function minimalPayload(string $name): array
    {
        return [
            'name' => $name,
            'scheme_type' => 'proficiency',
            'provider_name' => 'Reference Network',
            'round_reference' => $name,
            'status' => 'planned',
            'date' => now()->toDateString(),
        ];
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
        Notification::fake();

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

        Notification::assertSentTo(
            $user,
            OperationalNotification::class,
            fn (OperationalNotification $notification): bool => $notification->payload['key'] === 'quality.proficiency_test.updated'
                && $notification->payload['context']['document_number'] === 'ILC-2026-04'
        );

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

        Notification::assertSentTo(
            $user,
            OperationalNotification::class,
            fn (OperationalNotification $notification): bool => $notification->payload['key'] === 'quality.proficiency_test.updated'
                && $notification->payload['context']['status'] === 'reviewed'
        );

        $programme->refresh();

        $this->assertSame('reviewed', $programme->status);
        $this->assertSame('questionable', $programme->outcome);
        $this->assertSame('2.75', (string) $programme->z_score);
    }

    public function test_admin_can_open_show_page_with_chart_data(): void
    {
        $user = $this->verifiedAdmin();
        $programme = ProficiencyTest::query()->create([
            'lab_id' => $this->labIdFor($user),
            'name' => 'Organizer Show Round',
            'scheme_type' => 'proficiency',
            'role' => 'organizer',
            'provider_name' => 'Internal Quality Unit',
            'organizer_name' => 'Reference Laboratory',
            'participants' => [
                ['code' => 'LAB-01', 'name' => 'Alpha Lab', 'status' => 'submitted'],
            ],
            'parameters' => [
                ['code' => 'PH', 'name' => 'pH', 'unit' => 'pH', 'assigned_value' => 7.1],
            ],
            'participant_results' => [
                [
                    'code' => 'LAB-01',
                    'name' => 'Alpha Lab',
                    'results' => [
                        ['parameter_code' => 'PH', 'parameter' => 'pH', 'value' => 7.2, 'unit' => 'pH', 'z_score' => 0.4, 'outcome' => 'satisfactory'],
                    ],
                ],
            ],
            'round_reference' => 'PT-SHOW-2026',
            'status' => 'in_progress',
            'date' => now()->toDateString(),
            'submission_deadline_at' => now()->addDays(10)->toDateString(),
            'outcome' => 'pending',
            'results' => [],
        ]);

        $programme->performance_summary = $programme->calculatePerformanceSummary();
        $programme->save();

        $this->actingAs($user)
            ->get(route('proficiency_tests.show', $programme))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ProficiencyTest/Show')
                ->where('test.role', 'organizer')
                ->has('charts.z_scores.series.0.data.0')
                ->has('charts.performance.series')
                ->has('charts.participant_status.series')
            );
    }

    public function test_admin_can_register_organizer_results_and_performance_summary(): void
    {
        Notification::fake();

        $user = $this->verifiedAdmin();
        $programme = ProficiencyTest::query()->create([
            'lab_id' => $this->labIdFor($user),
            'name' => 'Organizer Results Round',
            'scheme_type' => 'interlaboratory',
            'role' => 'organizer',
            'provider_name' => 'Internal Quality Unit',
            'organizer_name' => 'Reference Laboratory',
            'round_reference' => 'PT-RESULTS-2026',
            'status' => 'in_progress',
            'date' => now()->toDateString(),
            'outcome' => 'pending',
            'results' => [],
        ]);

        $payload = [
            'participants' => [
                ['code' => 'LAB-01', 'name' => 'Alpha Lab', 'contact' => 'qa@example.test', 'status' => 'submitted'],
                ['code' => 'LAB-02', 'name' => 'Beta Lab', 'contact' => null, 'status' => 'requires_action'],
            ],
            'parameters' => [
                ['code' => 'ASH', 'name' => 'Ash', 'unit' => '%', 'assigned_value' => 1.2, 'standard_deviation' => 0.1],
            ],
            'participant_results' => [
                [
                    'code' => 'LAB-01',
                    'name' => 'Alpha Lab',
                    'results' => [
                        ['parameter_code' => 'ASH', 'parameter' => 'Ash', 'value' => 1.21, 'unit' => '%', 'assigned_value' => 1.2, 'z_score' => 0.1, 'outcome' => 'satisfactory'],
                    ],
                ],
                [
                    'code' => 'LAB-02',
                    'name' => 'Beta Lab',
                    'results' => [
                        ['parameter_code' => 'ASH', 'parameter' => 'Ash', 'value' => 1.65, 'unit' => '%', 'assigned_value' => 1.2, 'z_score' => 3.4, 'outcome' => 'unsatisfactory'],
                    ],
                ],
            ],
            'results' => [],
            'z_score' => 3.4,
            'outcome' => 'unsatisfactory',
            'corrective_actions' => 'Request investigation from LAB-02 and review assigned value evidence.',
            'notes' => 'Round reviewed by the quality manager.',
        ];

        $this->actingAs($user)
            ->put(route('proficiency_tests.results.update', $programme), $payload)
            ->assertRedirect(route('proficiency_tests.show', $programme));

        $programme->refresh();

        $this->assertCount(2, $programme->participants);
        $this->assertSame('requires_action', $programme->participants[1]['status']);
        $this->assertSame(1, $programme->parameterCount());
        $this->assertSame(2, $programme->resultCount());
        $this->assertSame(1, $programme->performance_summary['unsatisfactory']);
        $this->assertSame('unsatisfactory', $programme->outcome);

        Notification::assertSentTo(
            $user,
            OperationalNotification::class,
            fn (OperationalNotification $notification): bool => $notification->payload['key'] === 'quality.proficiency_test.updated'
                && $notification->payload['context']['outcome'] === 'unsatisfactory'
        );
    }

    public function test_admin_can_download_and_import_results_spreadsheet(): void
    {
        Notification::fake();

        $user = $this->verifiedAdmin();
        $programme = ProficiencyTest::query()->create([
            'lab_id' => $this->labIdFor($user),
            'name' => 'Spreadsheet Organizer Round',
            'scheme_type' => 'proficiency',
            'role' => 'organizer',
            'provider_name' => 'Internal Quality Unit',
            'round_reference' => 'PT-SPREADSHEET-2026',
            'status' => 'in_progress',
            'date' => now()->toDateString(),
            'outcome' => 'pending',
            'participants' => [
                ['code' => 'LAB-01', 'name' => 'Alpha Lab', 'contact' => null],
            ],
            'parameters' => [
                ['code' => 'MOI', 'name' => 'Moisture', 'unit' => '%', 'assigned_value' => 12.5, 'standard_deviation' => 0.5],
            ],
            'results' => [],
        ]);

        $this->actingAs($user)
            ->get(route('proficiency_tests.results.template', $programme))
            ->assertSuccessful()
            ->assertHeader('content-disposition');

        $path = tempnam(sys_get_temp_dir(), 'pt-results-');
        file_put_contents($path, implode("\n", [
            'participant_code,participant_name,participant_contact,participant_status,parameter_code,parameter_name,unit,assigned_value,standard_deviation,value,z_score,outcome,notes',
            'LAB-01,Alpha Lab,qa@example.test,submitted,MOI,Moisture,%,12.5,0.5,13.8,2.6,,Check drying oven traceability',
        ]));

        $file = new UploadedFile($path, 'pt-results.csv', 'text/csv', null, true);

        $this->actingAs($user)
            ->post(route('proficiency_tests.results.import', $programme), [
                'file' => $file,
            ])
            ->assertRedirect(route('proficiency_tests.show', $programme));

        $programme->refresh();

        $this->assertSame('qa@example.test', $programme->participants[0]['contact']);
        $this->assertSame('submitted', $programme->participants[0]['status']);
        $this->assertSame(1, $programme->resultCount());
        $this->assertSame('questionable', $programme->participant_results[0]['results'][0]['outcome']);
        $this->assertSame(1, $programme->performance_summary['questionable']);

        @unlink($path);
    }

    public function test_proficiency_deadline_job_sends_due_soon_and_overdue_reminders(): void
    {
        Notification::fake();

        $user = $this->verifiedAdmin();

        $dueSoon = ProficiencyTest::query()->create([
            'lab_id' => $this->labIdFor($user),
            'name' => 'PT Due Soon',
            'scheme_type' => 'proficiency',
            'provider_name' => 'External Provider',
            'round_reference' => 'PT-DUE-SOON',
            'status' => 'planned',
            'date' => now()->addDays(7)->toDateString(),
            'scheduled_at' => now()->addDays(7)->toDateString(),
            'outcome' => 'pending',
            'results' => [],
        ]);

        $overdue = ProficiencyTest::query()->create([
            'lab_id' => $this->labIdFor($user),
            'name' => 'PT Overdue',
            'scheme_type' => 'interlaboratory',
            'provider_name' => 'External Provider',
            'round_reference' => 'PT-OVERDUE',
            'status' => 'in_progress',
            'date' => now()->subDays(2)->toDateString(),
            'scheduled_at' => now()->subDays(2)->toDateString(),
            'outcome' => 'pending',
            'results' => [],
        ]);

        app(CheckProficiencyTestDeadlines::class)->handle(app(ProficiencyTestNotifier::class));

        Notification::assertSentTo(
            $user,
            OperationalNotification::class,
            fn (OperationalNotification $notification): bool => $notification->payload['key'] === 'quality.proficiency_test.updated'
                && $notification->payload['context']['document_number'] === $dueSoon->round_reference
        );

        Notification::assertSentTo(
            $user,
            OperationalNotification::class,
            fn (OperationalNotification $notification): bool => $notification->payload['key'] === 'quality.proficiency_test.updated'
                && $notification->payload['context']['document_number'] === $overdue->round_reference
        );
    }

    public function test_proficiency_listing_charts_and_record_routes_are_lab_private(): void
    {
        $admin = $this->verifiedAdmin();
        $own = $this->programme($this->labIdFor($admin), 'Own PT Round');
        $foreign = $this->programme(VAPLab::factory()->create()->id, 'Foreign PT Round', 'completed');

        $this->actingAs($admin)
            ->get(route('proficiency_tests.index'))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ProficiencyTest/Index')
                ->has('record.data', 1)
                ->where('record.data.0.id', $own->id)
                ->where('charts.status.series.0', 1)
                ->where('charts.status.series.2', 0)
            );

        $this->get(route('proficiency_tests.index', ['search' => $foreign->name]))
            ->assertInertia(fn (Assert $page) => $page->has('record.data', 0));

        $this->get(route('proficiency_tests.show', $foreign))->assertNotFound();
        $this->get(route('proficiency_tests.edit', $foreign))->assertNotFound();
        $this->get(route('proficiency_tests.results.template', $foreign))->assertNotFound();
        $this->put(route('proficiency_tests.update', $foreign), $this->minimalPayload('Changed PT Round'))->assertNotFound();
        $this->put(route('proficiency_tests.results.update', $foreign), [])->assertNotFound();
        $this->post(route('proficiency_tests.results.import', $foreign), [])->assertNotFound();

        $this->assertSame('Foreign PT Round', $foreign->fresh()->name);
    }

    public function test_bulk_archive_and_restore_reject_cross_lab_ids_without_partial_changes(): void
    {
        $admin = $this->verifiedAdmin();
        $own = $this->programme($this->labIdFor($admin), 'Own Bulk Round');
        $foreign = $this->programme(VAPLab::factory()->create()->id, 'Foreign Bulk Round');

        $this->actingAs($admin)
            ->post(route('proficiency_tests.destroy'), ['recordIds' => [$own->id, $foreign->id]])
            ->assertNotFound();

        $this->assertNull($own->fresh()->deleted_at);
        $this->assertNull($foreign->fresh()->deleted_at);

        $this->get(route('proficiency_tests.destroy', ['recordIds' => [$own->id]]))->assertMethodNotAllowed();
        $this->assertNull($own->fresh()->deleted_at);

        $this->post(route('proficiency_tests.destroy'), ['recordIds' => [$own->id]])->assertRedirect();
        $this->assertNotNull($own->fresh()->deleted_at);
        $this->get(route('proficiency_tests.index', ['filter' => 'trashed']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('record.data', 1)
                ->where('record.data.0.id', $own->id)
            );

        $foreign->delete();
        $this->post(route('proficiency_tests.restore'), ['recordIds' => [$own->id, $foreign->id]])->assertNotFound();
        $this->assertNotNull($own->fresh()->deleted_at);
        $this->assertNotNull($foreign->fresh()->deleted_at);

        $this->post(route('proficiency_tests.restore'), ['recordIds' => [$own->id]])->assertRedirect();
        $this->assertNull($own->fresh()->deleted_at);
        $this->get(route('proficiency_tests.index', ['filter' => 'trashed']))
            ->assertInertia(fn (Assert $page) => $page->has('record.data', 0));
    }

    public function test_proficiency_names_are_unique_per_lab_and_owner_cannot_be_forged(): void
    {
        Notification::fake();
        $admin = $this->verifiedAdmin();
        $ownLabId = $this->labIdFor($admin);
        $otherLab = VAPLab::factory()->create();
        $otherAdmin = User::factory()->create(['is_active' => true]);
        $otherAdmin->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $otherLab->id, 'user_id' => $otherAdmin->id]);

        $this->actingAs($admin)
            ->post(route('proficiency_tests.store'), $this->minimalPayload('Shared Round Name'))
            ->assertRedirect();

        $this->post(route('proficiency_tests.store'), $this->minimalPayload('Shared Round Name'))
            ->assertSessionHasErrors('name');

        $this->post(route('proficiency_tests.store'), [
            ...$this->minimalPayload('Forged Owner'),
            'lab_id' => $otherLab->id,
        ])->assertSessionHasErrors('lab_id');

        $this->withSession(['active_lab_id' => $otherLab->id]);
        $this->actingAs($otherAdmin)
            ->post(route('proficiency_tests.store'), $this->minimalPayload('Shared Round Name'))
            ->assertRedirect();

        $this->assertSame(1, ProficiencyTest::query()->where('lab_id', $ownLabId)->where('name', 'Shared Round Name')->count());
        $this->assertSame(1, ProficiencyTest::query()->where('lab_id', $otherLab->id)->where('name', 'Shared Round Name')->count());
        $this->assertFalse(ProficiencyTest::query()->where('name', 'Forged Owner')->exists());
    }

    public function test_proficiency_alerts_only_reach_members_of_the_owning_lab(): void
    {
        Notification::fake();
        $admin = $this->verifiedAdmin();
        $otherLab = VAPLab::factory()->create();
        $otherAdmin = User::factory()->create(['is_active' => true]);
        $otherAdmin->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $otherLab->id, 'user_id' => $otherAdmin->id]);

        $test = $this->programme($this->labIdFor($admin), 'Private Alert Round');
        app(ProficiencyTestNotifier::class)->notifyCreated($test);

        Notification::assertSentTo($admin, OperationalNotification::class);
        Notification::assertNotSentTo($otherAdmin, OperationalNotification::class);
    }
}
