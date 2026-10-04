<?php

namespace Tests\Feature;

use App\Actions\SaveNonConformity;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPNonConformity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class NonConformityEvidenceTest extends TestCase
{
    use DatabaseTransactions;

    private User $operator;

    private VAPLab $lab;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->lab = VAPLab::factory()->create();
        $this->operator = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id]);
        foreach (['view', 'add', 'edit'] as $ability) {
            $this->operator->givePermissionTo(Permission::findOrCreate($ability.'_occurrences', 'web'));
        }
        Notification::fake();
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    private function payload(): array
    {
        return ['nc_number' => 'NC-EVIDENCE-ATOMIC', 'title' => 'Evidence test', 'description' => 'Observed evidence',
            'status' => 'opened', 'severity' => 'medium', 'category' => 'quality', 'reported_by' => 'Reporter',
            'reported_at' => '2026-10-04 12:00:00', 'actions' => [['correction' => 'Quarantine material']]];
    }

    private function file(string $name = 'evidence.txt'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, 'Evidence for '.$name);
    }

    public function test_success_keeps_private_files_with_verified_hashes_and_authorized_downloads(): void
    {
        $this->post(route('vap_non_conformities.store'), [...$this->payload(),
            'attachment_files' => [$this->file(), $this->file('second.txt')]])->assertRedirect();
        $record = VAPNonConformity::where('nc_number', 'NC-EVIDENCE-ATOMIC')->firstOrFail();
        $this->assertCount(2, $record->getMedia('attachments'));
        foreach ($record->getMedia('attachments') as $media) {
            $this->assertSame('local', $media->disk);
            $this->assertStringStartsWith('quality-evidence/'.$media->uuid.'/', $media->getPathRelativeToRoot());
            $content = Storage::disk('local')->get($media->getPathRelativeToRoot());
            $this->assertSame(hash('sha256', $content), $media->getCustomProperty('document_sha256'));
            $this->get(route('vap_non_conformities.attachments.show', [$record, $media]))->assertOk();
        }
    }

    public function test_second_file_failure_rolls_back_record_actions_media_and_all_new_files_then_retry_succeeds(): void
    {
        $filesystem = app(Filesystem::class);
        $calls = 0;
        $this->partialMock(Filesystem::class)->shouldReceive('add')->andReturnUsing(function ($file, $media, $name) use ($filesystem, &$calls) {
            $result = $filesystem->add($file, $media, $name);
            if (++$calls === 2) {
                throw new RuntimeException('Simulated failure after copying second evidence.');
            }

            return $result;
        });
        $payload = [...$this->payload(), 'attachment_files' => [$this->file(), $this->file('second.txt')]];
        $this->postJson(route('vap_non_conformities.store'), $payload)->assertConflict();
        $this->assertDatabaseMissing('v_non_conformities', ['nc_number' => 'NC-EVIDENCE-ATOMIC']);
        $this->assertDatabaseCount('v_non_conformity_actions', 0);
        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('quality-evidence'));
        Notification::assertNothingSent();

        $this->app->instance(Filesystem::class, $filesystem);
        $this->post(route('vap_non_conformities.store'), $payload)->assertRedirect();
        $this->assertSame(1, VAPNonConformity::where('nc_number', 'NC-EVIDENCE-ATOMIC')->count());
        $this->assertSame(2, Media::count());
    }

    public function test_failed_update_restores_metadata_action_state_and_existing_evidence(): void
    {
        $this->post(route('vap_non_conformities.store'), [...$this->payload(), 'attachment_files' => [$this->file()]])->assertRedirect();
        $record = VAPNonConformity::where('nc_number', 'NC-EVIDENCE-ATOMIC')->firstOrFail();
        $original = $record->getMedia('attachments')->sole();
        $beforeFiles = Storage::disk('local')->allFiles();
        $filesystem = app(Filesystem::class);
        $this->partialMock(Filesystem::class)->shouldReceive('add')->andReturnUsing(function ($file, $media, $name) use ($filesystem) {
            $filesystem->add($file, $media, $name);
            throw new RuntimeException('Simulated upload failure.');
        });
        $this->putJson(route('vap_non_conformities.update', $record), [...$this->payload(), 'title' => 'Must roll back',
            'actions' => [], 'attachment_files' => [$this->file('new.txt')]])->assertConflict();
        $this->assertSame('Evidence test', $record->fresh()->title);
        $this->assertSame(1, $record->actions()->count());
        $this->assertSame(0, $record->actions()->onlyTrashed()->count());
        $this->assertModelExists($original);
        $this->assertSame($beforeFiles, Storage::disk('local')->allFiles());
    }

    public function test_media_model_veto_cannot_leave_an_untracked_file_or_saved_record(): void
    {
        $event = 'eloquent.creating: '.Media::class;
        Event::listen($event, fn () => false);
        try {
            $this->postJson(route('vap_non_conformities.store'), [...$this->payload(), 'attachment_files' => [$this->file()]])->assertConflict();
        } finally {
            Event::forget($event);
        }
        $this->assertDatabaseMissing('v_non_conformities', ['nc_number' => 'NC-EVIDENCE-ATOMIC']);
        $this->assertSame([], Storage::disk('local')->allFiles('quality-evidence'));
    }

    public function test_late_authority_failure_also_cleans_successfully_copied_files(): void
    {
        Event::listen(MediaHasBeenAddedEvent::class, function (): void {
            DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->operator->id)->delete();
        });
        try {
            $this->postJson(route('vap_non_conformities.store'), [...$this->payload(), 'attachment_files' => [$this->file()]])->assertForbidden();
        } finally {
            Event::forget(MediaHasBeenAddedEvent::class);
        }
        $this->assertDatabaseMissing('v_non_conformities', ['nc_number' => 'NC-EVIDENCE-ATOMIC']);
        $this->assertSame([], Storage::disk('local')->allFiles('quality-evidence'));
    }

    public function test_outer_transaction_rollback_cleans_nested_uploads(): void
    {
        DB::beginTransaction();
        try {
            app(SaveNonConformity::class)->execute($this->operator->id, $this->lab->id,
                [...$this->payload(), 'attachment_files' => [$this->file()]]);
            $this->assertCount(1, Storage::disk('local')->allFiles('quality-evidence'));
        } finally {
            DB::rollBack();
        }
        $this->assertSame([], Storage::disk('local')->allFiles('quality-evidence'));
        $this->assertDatabaseMissing('v_non_conformities', ['nc_number' => 'NC-EVIDENCE-ATOMIC']);
    }

    public function test_corrupted_stored_evidence_rejects_the_whole_save(): void
    {
        Event::listen(MediaHasBeenAddedEvent::class, function (MediaHasBeenAddedEvent $event): void {
            Storage::disk('local')->put($event->media->getPathRelativeToRoot(), 'Corrupted evidence');
        });
        try {
            $this->postJson(route('vap_non_conformities.store'), [...$this->payload(), 'attachment_files' => [$this->file()]])->assertConflict();
        } finally {
            Event::forget(MediaHasBeenAddedEvent::class);
        }
        $this->assertDatabaseMissing('v_non_conformities', ['nc_number' => 'NC-EVIDENCE-ATOMIC']);
        $this->assertSame([], Storage::disk('local')->allFiles('quality-evidence'));
        Notification::assertNothingSent();
    }
}
