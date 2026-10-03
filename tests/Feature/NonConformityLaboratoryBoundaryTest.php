<?php

namespace Tests\Feature;

use App\Exports\NonConformitiesExport;
use App\Models\ResponsibilityMatrixEntry;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPFile;
use App\Models\VAPLab;
use App\Models\VAPNonConformity;
use App\Models\VAPNonConformityAction;
use App\Notifications\OperationalNotification;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NonConformityLaboratoryBoundaryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_register_dashboard_exports_and_record_routes_are_lab_private(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->member($lab);
        $localRecord = $this->record($lab, $user, 'NC-LOCAL-001');
        $peerRecord = $this->record($peer, $user, 'NC-PEER-001');
        $localRecord->update(['occurrence_area' => 'procurement_receipt']);
        $peerRecord->update(['occurrence_area' => 'procurement_receipt']);
        $localDocument = $this->dueDocument($lab, $user);
        $peerDocument = $this->dueDocument($peer, $user);
        ResponsibilityMatrixEntry::query()->create([
            'lab_id' => $lab->id,
            'process_area' => 'Quality',
            'activity' => 'Local review',
        ]);
        ResponsibilityMatrixEntry::query()->create([
            'lab_id' => $peer->id,
            'process_area' => 'Quality',
            'activity' => 'Peer review',
        ]);

        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        $index = $this->get(route('vap_non_conformities.index', ['lab_id' => $peer->id]));
        $index->assertOk();
        $page = $index->viewData('page');
        $this->assertSame(1, data_get($page, 'props.nonConformities.total'));
        $this->assertSame($localRecord->id, data_get($page, 'props.nonConformities.data.0.id'));
        $this->assertSame(1, data_get($page, 'props.stats.total'));

        $dashboard = $this->get(route('qms.index'));
        $dashboard->assertOk();
        $this->assertSame(1, data_get($dashboard->viewData('page'), 'props.summary.open_non_conformities'));
        $this->assertCount(1, data_get($dashboard->viewData('page'), 'props.receivingNonConformities'));
        $this->assertSame(1, data_get($dashboard->viewData('page'), 'props.summary.documents_due_review'));
        $this->assertSame(1, data_get($dashboard->viewData('page'), 'props.summary.responsibility_assignments'));
        $this->assertSame($localDocument->id, data_get($dashboard->viewData('page'), 'props.dueDocumentReviews.0.id'));
        $this->assertNotSame($peerDocument->id, data_get($dashboard->viewData('page'), 'props.dueDocumentReviews.0.id'));

        $exported = (new NonConformitiesExport($lab->id))->collection();
        $this->assertSame([$localRecord->id], $exported->pluck('id')->all());

        $this->get(route('vap_non_conformities.show', $peerRecord))->assertNotFound();
        $this->get(route('vap_non_conformities.edit', $peerRecord))->assertNotFound();
        $this->get(route('vap_non_conformities.export.details.excel', $peerRecord))->assertNotFound();
        $this->get(route('vap_non_conformities.export.details.pdf', $peerRecord))->assertNotFound();
        $this->put(route('vap_non_conformities.update', $peerRecord), [])->assertNotFound();
        $this->delete(route('vap_non_conformities.destroy', $peerRecord))->assertNotFound();
        $this->assertDatabaseHas('v_non_conformities', ['id' => $peerRecord->id]);
    }

    public function test_creation_owns_record_and_actions_despite_submitted_lab_and_notifies_only_members(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->member($lab);
        $peerUser = $this->member($peer);
        $this->record($peer, $peerUser, 'NC-SHARED-NUMBER');

        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id])
            ->post(route('vap_non_conformities.store'), [
                'lab_id' => $peer->id,
                'nc_number' => 'NC-SHARED-NUMBER',
                'title' => 'Local deviation',
                'description' => 'Evidence and correction required.',
                'status' => 'opened',
                'severity' => 'high',
                'category' => 'quality',
                'reported_by' => $user->name,
                'reported_by_id' => $peerUser->id,
                'reported_at' => now()->toDateTimeString(),
                'actions' => [['correction' => 'Quarantine material']],
            ])->assertRedirect(route('vap_non_conformities.index'));

        $record = VAPNonConformity::query()->where('lab_id', $lab->id)->where('nc_number', 'NC-SHARED-NUMBER')->firstOrFail();
        $this->assertSame($lab->id, $record->lab_id);
        $this->assertSame($user->id, $record->reported_by_id);
        $this->assertSame($lab->id, $record->actions()->firstOrFail()->lab_id);
        Notification::assertSentTo($user, OperationalNotification::class);
        Notification::assertNotSentTo($peerUser, OperationalNotification::class);

        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id])
            ->post(route('vap_non_conformities.store'), [
                'nc_number' => 'NC-FOREIGN-ASSIGNEE',
                'title' => 'Invalid assignee',
                'description' => 'Foreign staff cannot be assigned.',
                'status' => 'opened',
                'severity' => 'medium',
                'category' => 'quality',
                'reported_by' => $user->name,
                'reported_at' => now()->toDateTimeString(),
                'assigned_to_id' => $peerUser->id,
            ])->assertSessionHasErrors('assigned_to_id');
        $this->assertDatabaseMissing('v_non_conformities', ['nc_number' => 'NC-FOREIGN-ASSIGNEE']);
    }

    public function test_evidence_uses_private_storage_and_authorized_download(): void
    {
        Storage::fake('local');
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->member($lab);
        $peerUser = $this->member($peer);

        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id])
            ->post(route('vap_non_conformities.store'), [
                'nc_number' => 'NC-PRIVATE-EVIDENCE',
                'title' => 'Evidence',
                'description' => 'Private attachment.',
                'status' => 'opened',
                'severity' => 'medium',
                'category' => 'quality',
                'reported_by' => $user->name,
                'reported_at' => now()->toDateTimeString(),
                'attachment_files' => [UploadedFile::fake()->create('evidence.pdf', 16, 'application/pdf')],
            ])->assertRedirect(route('vap_non_conformities.index'));

        $record = VAPNonConformity::query()->where('nc_number', 'NC-PRIVATE-EVIDENCE')->firstOrFail();
        $media = $record->getMedia('attachments')->sole();
        $this->assertSame('local', $media->disk);
        Storage::disk('local')->assertExists($media->getPathRelativeToRoot());

        $url = route('vap_non_conformities.attachments.show', [$record, $media]);
        $this->get($url)->assertOk();
        $page = $this->get(route('vap_non_conformities.show', $record))->viewData('page');
        $this->assertSame($url, data_get($page, 'props.nonConformity.media_attachments.0.url'));
        $this->assertArrayNotHasKey('media', data_get($page, 'props.nonConformity'));

        $this->actingAs($peerUser)->withSession(['active_lab_id' => $peer->id]);
        $this->get($url)->assertNotFound();
        $this->get(route('vap_non_conformities.show', $record))->assertNotFound();
    }

    public function test_action_cannot_be_assigned_to_another_laboratory_in_database(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->member($lab);
        $record = $this->record($lab, $user, 'NC-FK-001');
        $rejected = false;

        try {
            DB::transaction(fn () => VAPNonConformityAction::query()->create([
                'nc_id' => $record->id,
                'lab_id' => $peer->id,
                'correction' => 'Wrong laboratory',
            ]));
        } catch (QueryException) {
            $rejected = true;
        }

        $this->assertTrue($rejected);
        $this->assertDatabaseCount('v_non_conformity_actions', 0);
    }

    public function test_membership_alone_does_not_grant_quality_register_permission(): void
    {
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $record = $this->record($lab, $user, 'NC-NO-PERMISSION');

        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->get(route('vap_non_conformities.index'))->assertForbidden();
        $this->get(route('vap_non_conformities.show', $record))->assertForbidden();
        $this->post(route('vap_non_conformities.store'), [])->assertForbidden();
        $this->get(route('qms.index'))->assertForbidden();
    }

    private function member(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    private function record(VAPLab $lab, User $reporter, string $number): VAPNonConformity
    {
        return VAPNonConformity::query()->create([
            'lab_id' => $lab->id,
            'nc_number' => $number,
            'title' => 'Deviation',
            'description' => 'Observed deviation',
            'status' => 'opened',
            'severity' => 'medium',
            'category' => 'quality',
            'reported_by' => $reporter->name,
            'reported_by_id' => $reporter->id,
            'reported_at' => now(),
        ]);
    }

    private function dueDocument(VAPLab $lab, User $creator): VAPFile
    {
        return VAPFile::query()->create([
            'lab_id' => $lab->id,
            'name' => 'Review '.$lab->id,
            'type' => 'file',
            'modified_at' => now(),
            'created_by' => $creator->id,
            'review_due_at' => now()->addDay(),
        ]);
    }
}
