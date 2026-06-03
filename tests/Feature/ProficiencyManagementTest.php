<?php

namespace Tests\Feature;

use App\Jobs\CheckProficiencyTestDeadlines;
use App\Models\ProficiencyTest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ProficiencyTestWorkflowNotification;
use App\Support\ProficiencyTestNotifier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
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
            ProficiencyTestWorkflowNotification::class,
            fn (ProficiencyTestWorkflowNotification $notification) => $notification->title === 'Novo ensaio de proficiência registado'
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
            ProficiencyTestWorkflowNotification::class,
            fn (ProficiencyTestWorkflowNotification $notification) => $notification->title === 'Ensaio de proficiência atualizado'
        );

        $programme->refresh();

        $this->assertSame('reviewed', $programme->status);
        $this->assertSame('questionable', $programme->outcome);
        $this->assertSame('2.75', (string) $programme->z_score);
    }

    public function test_admin_can_open_show_page_with_chart_data(): void
    {
        $programme = ProficiencyTest::query()->create([
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

        $this->actingAs($this->verifiedAdmin())
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
            ProficiencyTestWorkflowNotification::class,
            fn (ProficiencyTestWorkflowNotification $notification) => $notification->title === 'Resultado insatisfatório em ensaio de proficiência'
        );
    }

    public function test_admin_can_download_and_import_results_spreadsheet(): void
    {
        Notification::fake();

        $user = $this->verifiedAdmin();
        $programme = ProficiencyTest::query()->create([
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
            ProficiencyTestWorkflowNotification::class,
            fn (ProficiencyTestWorkflowNotification $notification) => $notification->proficiencyTest->is($dueSoon)
                && $notification->title === 'Ensaio de proficiência próximo do prazo'
        );

        Notification::assertSentTo(
            $user,
            ProficiencyTestWorkflowNotification::class,
            fn (ProficiencyTestWorkflowNotification $notification) => $notification->proficiencyTest->is($overdue)
                && $notification->title === 'Ensaio de proficiência vencido'
        );
    }
}
