<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Models\VAPFile;
use App\Models\VAPLab;
use App\Models\WorkflowTask;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ControlledFileAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.default', 'local'));
        Notification::fake();
    }

    public function test_document_metadata_and_mutations_stay_inside_the_active_laboratory(): void
    {
        $localLab = VAPLab::factory()->create();
        $peerLab = VAPLab::factory()->create();
        $admin = $this->admin($localLab);
        $peerAdmin = $this->admin($peerLab);
        $localRecipient = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $localLab->id, 'user_id' => $localRecipient->id]);

        $localUpload = $this->actingAs($admin)->withSession(['active_lab_id' => $localLab->id])
            ->post(route('files.upload'), ['file' => UploadedFile::fake()->createWithContent('procedure.txt', "Local procedure\n")])
            ->assertCreated()
            ->assertJsonPath('data.lab_id', $localLab->id)
            ->assertJsonMissingPath('data.content')
            ->assertJsonMissingPath('data.versions.0.content');
        $localFile = VAPFile::query()->findOrFail($localUpload->json('data.id'));
        $this->assertSame($localUpload->json('data.current_version_id'), $localFile->versions()->firstOrFail()->id);

        $this->actingAs($peerAdmin)->withSession(['active_lab_id' => $peerLab->id])
            ->get(route('files.show', $localFile))->assertNotFound();
        $this->get(route('files.download', $localFile))->assertNotFound();
        $this->get(route('files.versions', $localFile))->assertNotFound();
        $this->get(route('files.list'))->assertOk()->assertJsonCount(0, 'data');

        $peerUpload = $this->post(route('files.upload'), [
            'file' => UploadedFile::fake()->createWithContent('procedure.txt', "Peer procedure\n"),
        ])->assertCreated();
        $peerFile = VAPFile::query()->findOrFail($peerUpload->json('data.id'));
        $this->assertSame($peerLab->id, (int) $peerFile->lab_id);

        DB::table('lab_user')->insert(['lab_id' => $peerLab->id, 'user_id' => $admin->id]);
        $this->actingAs($admin)->withSession(['active_lab_id' => $peerLab->id])
            ->get(route('files.show', $localFile))->assertNotFound();

        $this->withSession(['active_lab_id' => $localLab->id])
            ->post(route('files.share', $localFile), [
                'user_id' => $peerAdmin->id,
                'access_level' => 'read',
            ])->assertSessionHasErrors('user_id');
        $this->assertDatabaseMissing('v_file_permissions', ['file_id' => $localFile->id, 'user_id' => $peerAdmin->id]);

        $this->post(route('files.share', $localFile), [
            'user_id' => $localRecipient->id,
            'access_level' => 'read',
        ])->assertOk();
        $this->assertDatabaseHas('v_file_permissions', ['file_id' => $localFile->id, 'user_id' => $localRecipient->id]);

        $this->get(route('files.show', $localFile))
            ->assertOk()
            ->assertJsonMissingPath('data.content')
            ->assertJsonMissingPath('data.versions.0.content');
        $this->get(route('files.versions', $localFile))
            ->assertOk()
            ->assertJsonMissingPath('data.0.content');
        $this->get(route('files.list'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.content');
        $this->get(route('files.search', ['query' => 'procedure']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonMissingPath('0.content');

        $this->post(route('files.upload'), [
            'file' => UploadedFile::fake()->createWithContent('in-peer-folder.txt', 'blocked'),
            'parent_id' => $peerFile->id,
        ])->assertNotFound();
        $this->put(route('files.move', $localFile), ['parent_id' => $peerFile->id])->assertNotFound();
        $this->assertSame($localLab->id, (int) $localFile->fresh()->lab_id);
    }

    public function test_text_comparison_reads_only_authorized_versions_of_the_same_file(): void
    {
        $lab = VAPLab::factory()->create();
        $admin = $this->admin($lab);
        $this->actingAs($admin)->withSession(['active_lab_id' => $lab->id]);

        $fileId = $this->post(route('files.upload'), [
            'file' => UploadedFile::fake()->createWithContent('notes.txt', "Before\n"),
        ])->assertCreated()->json('data.id');
        $file = VAPFile::query()->findOrFail($fileId);
        $older = $file->versions()->firstOrFail();

        $this->post(route('files.upload'), [
            'file' => UploadedFile::fake()->createWithContent('notes.txt', "After\n"),
            'override' => true,
        ])->assertOk();
        $newer = $file->versions()->whereKeyNot($older->id)->firstOrFail();

        $comparison = route('files.versions.compare', [
            'file' => $file,
            'older' => $older->id,
            'newer' => $newer->id,
        ]);
        $this->get($comparison)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('older.text', "Before\n")
            ->assertJsonPath('newer.text', "After\n")
            ->assertJsonMissingPath('older.content')
            ->assertJsonMissingPath('newer.content');

        $otherFileId = $this->post(route('files.upload'), [
            'file' => UploadedFile::fake()->createWithContent('other.txt', 'Other'),
        ])->assertCreated()->json('data.id');
        $otherVersion = VAPFile::query()->findOrFail($otherFileId)->versions()->firstOrFail();
        $this->get(route('files.versions.compare', [
            'file' => $file,
            'older' => $older->id,
            'newer' => $otherVersion->id,
        ]))->assertNotFound();

        $peerLab = VAPLab::factory()->create();
        $peerAdmin = $this->admin($peerLab);
        $this->actingAs($peerAdmin)->withSession(['active_lab_id' => $peerLab->id])
            ->get($comparison)->assertNotFound();
    }

    public function test_text_comparison_rejects_binary_large_missing_and_invalid_content(): void
    {
        $lab = VAPLab::factory()->create();
        $admin = $this->admin($lab);
        $this->actingAs($admin)->withSession(['active_lab_id' => $lab->id]);

        $fileId = $this->post(route('files.upload'), [
            'file' => UploadedFile::fake()->createWithContent('notes.txt', "Before\n"),
        ])->assertCreated()->json('data.id');
        $file = VAPFile::query()->findOrFail($fileId);
        $older = $file->versions()->firstOrFail();

        $this->post(route('files.upload'), [
            'file' => UploadedFile::fake()->createWithContent('notes.txt', "After\n"),
            'override' => true,
        ])->assertOk();
        $newer = $file->versions()->whereKeyNot($older->id)->firstOrFail();
        $comparison = route('files.versions.compare', [
            'file' => $file,
            'older' => $older->id,
            'newer' => $newer->id,
        ]);

        $newer->update(['mime_type' => 'application/pdf']);
        $this->get($comparison)->assertStatus(415);

        $newer->update(['mime_type' => 'text/plain', 'size' => 1048577]);
        $this->get($comparison)->assertStatus(413);

        $newer->update(['size' => 6, 'content' => 'missing/document.txt']);
        $this->get($comparison)->assertNotFound();

        $newer->update(['content' => $older->content]);
        Storage::put($older->content, "\xFF");
        $this->get($comparison)->assertStatus(422);
    }

    public function test_document_tags_and_workflow_tasks_are_private_to_the_owning_laboratory(): void
    {
        $localLab = VAPLab::factory()->create();
        $peerLab = VAPLab::factory()->create();
        $admin = $this->admin($localLab);
        $peerAdmin = $this->admin($peerLab);
        $this->actingAs($admin)->withSession(['active_lab_id' => $localLab->id]);

        $fileId = $this->post(route('files.upload'), [
            'file' => UploadedFile::fake()->createWithContent('procedure.txt', 'Local'),
        ])->assertCreated()->json('data.id');
        $file = VAPFile::query()->findOrFail($fileId);

        $this->put(route('files.tags.update', $file), ['tags' => ['Controlled']])
            ->assertOk()
            ->assertJsonPath('tags.0', 'Controlled')
            ->assertJsonMissingPath('content');
        $this->get(route('tags.index'))->assertOk()->assertJsonCount(1);

        $this->post(route('workflow.tasks.store'), [
            'file_id' => $file->id,
            'type' => 'review',
            'assigned_to' => $peerAdmin->id,
        ])->assertSessionHasErrors('assigned_to');
        $this->post(route('files.submit-review', $file), [
            'assigned_to' => $peerAdmin->id,
            'change_reason' => 'Peer assignment should fail',
        ])->assertSessionHasErrors('assigned_to');

        $taskId = $this->post(route('workflow.tasks.store'), [
            'file_id' => $file->id,
            'type' => 'review',
            'assigned_to' => $admin->id,
        ])->assertCreated()->json('data.id');
        $task = WorkflowTask::query()->findOrFail($taskId);
        $this->get(route('workflow.tasks.index'))->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($peerAdmin)->withSession(['active_lab_id' => $peerLab->id]);
        $this->get(route('tags.index'))->assertOk()->assertJsonCount(0);
        $this->put(route('files.tags.update', $file), ['tags' => ['Peer']])->assertNotFound();
        $this->get(route('workflow.tasks.index'))->assertOk()->assertJsonCount(0, 'data');
        $this->put(route('workflow.tasks.update-status', $task), ['status' => 'completed'])->assertNotFound();
        $this->post(route('workflow.tasks.add-comment', $task), ['comment' => 'Peer'])->assertNotFound();
        $this->post(route('workflow.tasks.store'), [
            'file_id' => $file->id,
            'type' => 'review',
            'assigned_to' => $peerAdmin->id,
        ])->assertSessionHasErrors('file_id');

        $this->post(route('tags.store'), ['name' => 'Controlled'])->assertCreated();
        $this->get(route('tags.index'))->assertOk()->assertJsonCount(1);
        $this->assertSame(2, Tag::query()->where('name', 'Controlled')->count());
        $this->assertDatabaseHas('workflow_tasks', ['id' => $task->id, 'status' => 'pending']);
        $this->assertDatabaseMissing('workflow_task_comments', ['task_id' => $task->id]);
    }

    public function test_document_parent_must_be_a_folder_without_a_cycle(): void
    {
        $lab = VAPLab::factory()->create();
        $admin = $this->admin($lab);
        $this->actingAs($admin)->withSession(['active_lab_id' => $lab->id]);

        $fileId = $this->post(route('files.upload'), [
            'file' => UploadedFile::fake()->createWithContent('notes.txt', 'Notes'),
        ])->assertCreated()->json('data.id');
        $file = VAPFile::query()->findOrFail($fileId);
        $this->post(route('files.upload'), [
            'file' => UploadedFile::fake()->createWithContent('nested.txt', 'Invalid parent'),
            'parent_id' => $file->id,
        ])->assertSessionHasErrors('parent_id');

        $rootId = $this->post(route('files.upload-folder'), ['name' => 'Root'])
            ->assertCreated()->json('data.id');
        $childId = $this->post(route('files.upload-folder'), [
            'name' => 'Child',
            'parent_id' => $rootId,
        ])->assertCreated()->json('data.id');

        $this->put(route('files.move', $rootId), ['parent_id' => $rootId])
            ->assertSessionHasErrors('parent_id');
        $this->put(route('files.move', $rootId), ['parent_id' => $childId])
            ->assertSessionHasErrors('parent_id');
        $this->assertNull(VAPFile::query()->findOrFail($rootId)->parent_id);
        $this->assertSame($rootId, VAPFile::query()->findOrFail($childId)->parent_id);
    }

    private function admin(VAPLab $lab): User
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $admin->id]);

        return $admin;
    }
}
