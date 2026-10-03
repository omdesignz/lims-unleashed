<?php

namespace Tests\Feature;

use App\Actions\ImportOccurrences;
use App\Actions\SaveOccurrence;
use App\Jobs\CheckPastDueOccurrences;
use App\Models\LabNetwork;
use App\Models\Occurrence;
use App\Models\OccurrenceCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Notifications\OperationalNotification;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Support\ExportHubQuery;
use App\Support\NotificationTemplateService;
use App\Support\OccurrenceCsv;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use LogicException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class OccurrenceLaboratoryOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private VAPLab $peer;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lab = VAPLab::factory()->create();
        $this->peer = VAPLab::factory()->create();
        $this->operator = $this->member($this->lab);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    public function test_reads_search_and_archived_filter_cannot_escape_the_active_lab(): void
    {
        $local = Occurrence::factory()->create(['lab_id' => $this->lab->id, 'issue_description' => 'Local evidence']);
        $foreign = Occurrence::factory()->create(['lab_id' => $this->peer->id, 'issue_description' => 'Peer evidence']);
        $archived = Occurrence::factory()->create(['lab_id' => $this->lab->id]);
        $archived->delete();

        $page = $this->get(route('occurrences.index'))->assertOk()->viewData('page');
        $this->assertSame([$local->id], array_column(data_get($page, 'props.record.data'), 'id'));
        $this->assertSame([], data_get($this->get(route('occurrences.index', ['search' => $foreign->occurrence_no]))
            ->assertOk()->viewData('page'), 'props.record.data'));
        $page = $this->get(route('occurrences.index', ['filter' => 'trashed']))->assertOk()->viewData('page');
        $this->assertEqualsCanonicalizing([$local->id, $archived->id], array_column(data_get($page, 'props.record.data'), 'id'));

        $this->get(route('occurrences.show', $local))->assertOk();
        $this->get(route('occurrences.edit', $local))->assertOk();
        $this->get(route('occurrences.show', $foreign))->assertNotFound();
        $this->get(route('occurrences.edit', $foreign))->assertNotFound();
        $this->put(route('occurrences.update', $foreign), $this->payload())->assertNotFound();
        $this->assertSame('Peer evidence', $foreign->fresh()->issue_description);
    }

    public function test_local_create_update_and_pending_review_fields_are_usable(): void
    {
        $category = OccurrenceCategory::query()->create(['name' => 'Occurrence test category']);
        $payload = $this->payload() + [
            'category_id' => ['value' => $category->id, 'label' => $category->name],
            'user_id' => ['value' => $this->operator->id, 'label' => $this->operator->name],
            'client_acceptance' => null,
            'was_effective' => null,
            'has_risk_correction_budget' => null,
        ];

        $this->post(route('occurrences.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $record = Occurrence::query()->where('lab_id', $this->lab->id)->sole();
        $this->assertSame($category->id, $record->category_id);
        $this->assertSame($this->operator->id, $record->user_id);
        $this->assertNull($record->client_acceptance);
        $this->assertNull($record->was_effective);
        $this->assertFalse($record->has_risk_correction_budget);

        $number = $record->occurrence_no;
        $this->put(route('occurrences.update', $record), array_replace($payload, [
            'issue_description' => 'Corrected metadata', 'was_effective' => true,
            'client_process_close_notification_date' => today()->toDateString(),
        ]))->assertSessionHasNoErrors()->assertRedirect(route('occurrences.show', $record));
        $record->refresh();
        $this->assertSame($number, $record->occurrence_no);
        $this->assertSame('Corrected metadata', $record->issue_description);
        $this->assertTrue($record->was_effective);
        $this->assertSame(today()->toDateString(), data_get($this->get(route('occurrences.show', $record))
            ->assertOk()->viewData('page'), 'props.record.data.client_process_close_notification_date'));
    }

    public function test_numbering_is_per_lab_and_year_and_issued_identity_is_immutable(): void
    {
        $local = Occurrence::factory()->create(['lab_id' => $this->lab->id]);
        $second = Occurrence::factory()->create(['lab_id' => $this->lab->id]);
        $foreign = Occurrence::factory()->create(['lab_id' => $this->peer->id]);
        $nextYear = Occurrence::factory()->create(['lab_id' => $this->lab->id, 'occurrence_year' => (string) (now()->year + 1)]);
        $this->assertSame(1, (int) $local->seq);
        $this->assertSame(2, (int) $second->seq);
        $this->assertSame(1, (int) $foreign->seq);
        $this->assertSame(1, (int) $nextYear->seq);
        $this->assertNotSame($local->occurrence_no, $foreign->occurrence_no);
        $this->assertSame(now()->year.'/L'.$this->lab->id.'/001', $local->occurrence_no);

        foreach (['lab_id' => $this->peer->id, 'occurrence_year' => '1999', 'seq' => 88, 'occurrence_no' => 'Forged'] as $field => $value) {
            try {
                $local->update([$field => $value]);
                $this->fail('Issued identity must not change.');
            } catch (LogicException) {
                $local->refresh();
                $this->assertSame($this->lab->id, $local->lab_id);
                $this->assertSame(1, (int) $local->seq);
            }
        }
    }

    public function test_forged_identity_and_foreign_or_ineligible_assignees_are_rejected(): void
    {
        foreach (['lab_id' => $this->peer->id, 'occurrence_no' => 'Forged', 'occurrence_year' => '1999', 'seq' => 99] as $field => $value) {
            $this->post(route('occurrences.store'), $this->payload() + [$field => $value])->assertSessionHasErrors($field);
        }
        $foreign = $this->member($this->peer);
        $inactive = $this->member($this->lab);
        $inactive->update(['is_active' => false]);
        $unverified = $this->member($this->lab);
        $unverified->update(['email_verified_at' => null]);

        foreach ([$foreign, $inactive, $unverified] as $assignee) {
            $this->post(route('occurrences.store'), $this->payload() + ['user_id' => $assignee->id])
                ->assertSessionHasErrors('user_id');
        }

        $category = OccurrenceCategory::query()->create(['name' => 'Archived category']);
        $category->delete();
        $this->post(route('occurrences.store'), $this->payload() + ['category_id' => $category->id])
            ->assertSessionHasErrors('category_id');
        $this->post(route('occurrences.store'), $this->payload() + ['origin_id' => 999999999])
            ->assertSessionHasErrors('origin_id');
        $this->post(route('occurrences.store'), $this->payload() + ['department_id' => ['label' => 'Malformed']])
            ->assertSessionHasErrors('department_id');
        $this->assertSame(0, Occurrence::query()->where('lab_id', $this->lab->id)->count());
    }

    public function test_bulk_archive_restore_is_post_only_and_atomic_for_mixed_lab_ids(): void
    {
        $local = Occurrence::factory()->create(['lab_id' => $this->lab->id]);
        $foreign = Occurrence::factory()->create(['lab_id' => $this->peer->id]);

        $this->get(route('occurrences.destroy', ['recordIds' => [$local->id]]))->assertStatus(405);
        $this->get(route('occurrences.restore', ['recordIds' => [$local->id]]))->assertStatus(405);
        $this->post(route('occurrences.destroy'), ['recordIds' => [$local->id, $foreign->id]])->assertNotFound();
        $this->assertNotSoftDeleted($local);
        $this->assertNotSoftDeleted($foreign);
        $this->post(route('occurrences.destroy'), ['recordIds' => 'not-an-array'])->assertSessionHasErrors('recordIds');
        $this->post(route('occurrences.destroy'), ['recordIds' => [$local->id, $local->id]])->assertSessionHasErrors('recordIds.0');

        $this->post(route('occurrences.destroy'), ['recordIds' => [$local->id]])->assertSessionHasNoErrors();
        $this->assertSoftDeleted($local);
        $this->post(route('occurrences.restore'), ['recordIds' => [$local->id, $foreign->id]])->assertNotFound();
        $this->assertSoftDeleted($local);
        $this->post(route('occurrences.restore'), ['recordIds' => [$local->id]])->assertSessionHasNoErrors();
        $this->assertNotSoftDeleted($local);
        $local->refresh()->delete();
        $next = Occurrence::factory()->create(['lab_id' => $this->lab->id]);
        $this->assertSame(2, (int) $next->seq, 'Archival must not recycle issued identifiers.');
    }

    public function test_permission_and_membership_are_required_for_every_entrypoint(): void
    {
        $local = Occurrence::factory()->create(['lab_id' => $this->lab->id]);
        $viewer = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $viewer->id]);
        $this->actingAs($viewer);
        foreach (['occurrences.index', 'occurrences.create', 'occurrences.import.template'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->get(route('occurrences.show', $local))->assertForbidden();
        $this->get(route('occurrences.edit', $local))->assertForbidden();
        $this->post(route('occurrences.store'), $this->payload())->assertForbidden();
        $this->put(route('occurrences.update', $local), $this->payload())->assertForbidden();
        $this->post(route('occurrences.import.upload'), ['file' => $this->csv()])->assertForbidden();
        foreach (['destroy', 'restore'] as $action) {
            $this->post(route('occurrences.'.$action), ['recordIds' => [$local->id]])->assertForbidden();
        }
        $this->actingAs($this->operator);
        DB::table('lab_user')->where('user_id', $this->operator->id)->delete();
        $this->get(route('occurrences.index'))->assertForbidden();
        $this->post(route('occurrences.store'), $this->payload())->assertForbidden();
    }

    public function test_canonical_csv_import_uses_server_numbering_and_all_rows_commit(): void
    {
        $this->post(route('occurrences.import.upload'), ['file' => $this->csv(
            "date_reported;issue_description;user_id;client_acceptance\n".today()->toDateString().';First import;'.$this->operator->id.";1\n".today()->toDateString().";Second import;;\n"
        )])->assertSessionHasNoErrors()->assertRedirect(route('occurrences.index'));
        $records = Occurrence::query()->where('lab_id', $this->lab->id)->orderBy('seq')->get();
        $this->assertSame(['First import', 'Second import'], $records->pluck('issue_description')->all());
        $this->assertSame([1, 2], $records->pluck('seq')->map(fn ($seq) => (int) $seq)->all());
        $this->assertTrue($records[0]->client_acceptance);
        $this->assertNull($records[1]->client_acceptance);
        $this->assertDatabaseMissing('occurrences', ['lab_id' => $this->peer->id]);
        $this->assertSame(2, DB::table('activity_log')->where('subject_type', $records[0]->getMorphClass())->whereIn('subject_id', $records->modelKeys())->count());
    }

    public function test_invalid_csv_is_atomic_and_rejects_numbering_ownership_and_bad_headers(): void
    {
        $foreign = $this->member($this->peer);
        foreach ([
            "date_reported;issue_description\n".today()->toDateString().";Valid row\nnot-a-date;Invalid row",
            "date_reported;issue_description;user_id\n".today()->toDateString().';Foreign assignee;'.$foreign->id,
            "date_reported;issue_description;lab_id\n".today()->toDateString().';Forged;'.$this->peer->id,
            "date_reported;issue_description;seq\n".today()->toDateString().';Forged;123',
            "date_reported;date_reported;issue_description\n2026-10-01;2026-10-01;Duplicate",
            "date_reported;issue_description\n2026-10-01;Extra;cell",
            "date_reported;issue_description\n",
            "date_reported;issue_description\n".str_repeat("2026-10-01;Too many\n", 501),
        ] as $content) {
            $this->post(route('occurrences.import.upload'), ['file' => $this->csv($content)])->assertSessionHasErrors('file');
            $this->assertSame(0, Occurrence::query()->where('lab_id', $this->lab->id)->count());
        }
        $this->post(route('occurrences.store'), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(1, (int) Occurrence::query()->where('lab_id', $this->lab->id)->sole()->seq);
    }

    public function test_import_rolls_back_previous_rows_on_a_late_save_failure(): void
    {
        $file = $this->csv("date_reported;issue_description\n2026-10-01;First\n2026-10-01;Second\n");
        $save = new class(app(LaboratoryWorkflowMutationAccess::class)) extends SaveOccurrence
        {
            private int $calls = 0;

            public function execute(int $labId, int $userId, array $data, ?int $occurrenceId = null): Occurrence
            {
                if (++$this->calls === 2) {
                    throw new \RuntimeException('Simulated write failure');
                }

                return parent::execute($labId, $userId, $data, $occurrenceId);
            }
        };
        try {
            (new ImportOccurrences(app(OccurrenceCsv::class), $save))->execute($this->lab->id, $this->operator->id, $file->getRealPath());
            $this->fail('Late failure must abort the batch.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated write failure', $exception->getMessage());
            $this->assertSame(0, Occurrence::query()->where('lab_id', $this->lab->id)->count());
        }
        $record = app(SaveOccurrence::class)->execute($this->lab->id, $this->operator->id, $this->payload());
        $this->assertSame(1, (int) $record->seq);
    }

    public function test_csv_template_is_downloadable_and_does_not_accept_client_identity(): void
    {
        $response = $this->get(route('occurrences.import.template'))->assertOk()->assertDownload('modelo-ocorrencias.csv');
        $header = trim($response->streamedContent());
        $this->assertStringContainsString('date_reported;issue_description', $header);
        foreach (['lab_id', 'occurrence_no', 'occurrence_year', 'seq'] as $field) {
            $this->assertNotContains($field, explode(';', $header));
        }
        $cells = array_fill(0, count(explode(';', $header)), '');
        $cells[0] = today()->toDateString();
        $cells[1] = 'Template roundtrip';
        $this->post(route('occurrences.import.upload'), ['file' => $this->csv($header."\n".implode(';', $cells))])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('occurrences', ['lab_id' => $this->lab->id, 'issue_description' => 'Template roundtrip']);
    }

    public function test_exports_and_export_summary_are_lab_private(): void
    {
        $local = Occurrence::factory()->create(['lab_id' => $this->lab->id]);
        Occurrence::factory()->create(['lab_id' => $this->peer->id]);
        $page = $this->get(route('exports.index', ['dataset' => 'occurrences']))->assertOk()->viewData('page');
        $this->assertSame(1, data_get($page, 'props.selectedCount'));
        $datasets = collect(data_get($page, 'props.datasets'));
        $this->assertSame(1, $datasets->firstWhere('key', 'occurrences')['count']);
        $this->assertSame([$local->id], app(ExportHubQuery::class)->occurrences([])->pluck('occurrences.id')->all());
        $this->get(route('exports.download', ['dataset' => 'occurrences']))->assertOk()->assertDownload();
    }

    public function test_overdue_reminders_only_reach_owner_lab_and_skip_closed_resolved_and_archived(): void
    {
        Notification::fake();
        $peerUser = $this->member($this->peer);
        $local = Occurrence::factory()->create(['lab_id' => $this->lab->id, 'implementation_date' => today()->subDay()]);
        foreach (['date_closed', 'date_resolved'] as $field) {
            Occurrence::factory()->create(['lab_id' => $this->lab->id, 'implementation_date' => today()->subDay(), $field => today()]);
        }
        Occurrence::factory()->create(['lab_id' => $this->lab->id, 'implementation_date' => today()]);
        Occurrence::factory()->create(['lab_id' => $this->lab->id, 'implementation_date' => today()->subDay()])->delete();
        (new CheckPastDueOccurrences)->handle(app(NotificationTemplateService::class));
        Notification::assertSentTo($this->operator, OperationalNotification::class,
            fn (OperationalNotification $notification) => $notification->payload['context']['lab_id'] === $this->lab->id
                && $notification->payload['context']['document_number'] === $local->occurrence_no);
        Notification::assertNotSentTo($peerUser, OperationalNotification::class);
        Notification::assertCount(1);
    }

    public function test_schema_requires_owner_and_preserves_evidence_on_parent_deletion(): void
    {
        $category = OccurrenceCategory::query()->create(['name' => 'Protected category']);
        $local = Occurrence::factory()->create(['lab_id' => $this->lab->id, 'category_id' => $category->id]);
        foreach ([
            fn () => DB::table('labs')->where('id', $this->lab->id)->delete(),
            fn () => DB::table('occurrence_categories')->where('id', $category->id)->delete(),
            fn () => DB::table('occurrences')->where('id', $local->id)->update(['lab_id' => null]),
        ] as $mutation) {
            try {
                DB::transaction($mutation);
                $this->fail('Database must enforce ownership and evidence retention.');
            } catch (QueryException) {
                $this->assertDatabaseHas('occurrences', ['id' => $local->id, 'lab_id' => $this->lab->id]);
            }
        }
        $migration = require database_path('migrations/2026_10_01_125620_add_laboratory_ownership_to_occurrences_table.php');
        foreach (['up', 'down'] as $method) {
            try {
                $migration->{$method}();
                $this->fail('Retained records must block ownership removal or guessed reassignment.');
            } catch (\RuntimeException) {
                $this->assertTrue(Schema::hasColumn('occurrences', 'lab_id'));
            }
        }
    }

    public function test_occurrence_audit_logs_are_private_in_list_direct_access_and_export(): void
    {
        $local = Occurrence::factory()->create(['lab_id' => $this->lab->id]);
        $foreign = Occurrence::factory()->create(['lab_id' => $this->peer->id]);
        foreach ([$local, $foreign] as $record) {
            activity()->causedBy($this->operator)->performedOn($record)->log('Occurrence audit boundary '.$record->lab_id);
        }
        $localId = DB::table('activity_log')->where('subject_type', 'occurrence')->where('subject_id', $local->id)->value('id');
        $foreignId = DB::table('activity_log')->where('subject_type', 'occurrence')->where('subject_id', $foreign->id)->value('id');
        $page = $this->get(route('systemactivity.index', ['search' => 'Occurrence audit boundary']))->assertOk()->viewData('page');
        $this->assertSame([$localId], array_column(data_get($page, 'props.record.data'), 'id'));
        $this->getJson(route('systemactivity.show', $localId))->assertOk()->assertJsonPath('activity.subject.id', $local->id);
        $response = $this->get(route('exports.download', ['dataset' => 'activity_log', 'search' => 'Occurrence audit boundary']))->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $sheet = IOFactory::load($path)->getActiveSheet()->toArray();
            $this->assertCount(2, $sheet);
            $this->assertSame((string) $localId, (string) $sheet[1][0]);
            $this->assertStringNotContainsString('Occurrence audit boundary '.$this->peer->id, json_encode($sheet, JSON_THROW_ON_ERROR));
        } finally {
            @unlink($path);
        }
        $this->getJson(route('systemactivity.show', $foreignId))->assertNotFound();
        $local->delete();
        $this->getJson(route('systemactivity.show', $localId))->assertOk();

        $viewer = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $viewer->givePermissionTo(Permission::findOrCreate('view_activity_log', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $viewer->id]);
        $this->actingAs($viewer)->getJson(route('systemactivity.show', $localId))->assertNotFound();
    }

    public function test_malformed_ids_and_filter_shapes_fail_without_postgresql_errors(): void
    {
        foreach (['invalid', '0', '-1', '99999999999999999999'] as $id) {
            $this->getJson(route('occurrences.show', $id))->assertNotFound();
            $this->getJson(route('occurrences.edit', $id))->assertNotFound();
            $this->putJson(route('occurrences.update', $id), $this->payload())->assertNotFound();
        }
        foreach ([['search' => ['bad']], ['search' => str_repeat('x', 256)], ['date' => 'invalid'], ['date' => ['start' => 'invalid', 'end' => 'invalid']]] as $filters) {
            $this->getJson(route('occurrences.index', $filters))->assertUnprocessable();
        }
        $this->postJson(route('occurrences.destroy'), ['recordIds' => ['99999999999999999999']])
            ->assertUnprocessable()->assertJsonValidationErrors('recordIds.0');
    }

    public function test_network_overview_grant_does_not_open_peer_occurrence_records(): void
    {
        $network = LabNetwork::factory()->create();
        $this->lab->update(['network_id' => $network->id]);
        $this->peer->update(['network_id' => $network->id]);
        $network->update(['main_lab_id' => $this->lab->id]);
        DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->operator->id)
            ->update(['can_view_network' => true]);
        $foreign = Occurrence::factory()->create(['lab_id' => $this->peer->id]);
        $this->get(route('lab-network.index', $network))->assertOk();
        $this->get(route('occurrences.show', $foreign))->assertNotFound();
        $this->put(route('occurrences.update', $foreign), $this->payload())->assertNotFound();
        $this->post(route('occurrences.destroy'), ['recordIds' => [$foreign->id]])->assertNotFound();
        $this->get(route('occurrences.index'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->has('record.data', 0));
    }

    private function member(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('view_occurrences', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    /** @return array<string, string> */
    private function payload(): array
    {
        return ['date_reported' => today()->toDateString(), 'issue_description' => 'Local occurrence'];
    }

    private function csv(?string $content = null): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('occurrences.csv', $content ?? "date_reported;issue_description\n2026-10-01;Imported occurrence\n");
    }
}
