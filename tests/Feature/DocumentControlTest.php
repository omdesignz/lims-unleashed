<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VAPFile;
use App\Models\VAPLab;
use App\Models\WorkflowTask;
use App\Notifications\OperationalNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DocumentControlTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $admin->id]);
        $this->withSession(['active_lab_id' => $this->lab->id]);

        return $admin;
    }

    public function test_controlled_document_can_move_from_draft_to_effective(): void
    {
        Storage::fake(config('filesystems.default', 'local'));

        $admin = $this->verifiedAdmin();

        $uploadResponse = $this->actingAs($admin)->post(route('files.upload'), [
            'file' => UploadedFile::fake()->create('procedimento-lab.pdf', 120, 'application/pdf'),
            'document_number' => 'DOC-ISO-001',
            'document_type' => 'Procedimento',
            'category' => 'Qualidade',
            'confidentiality_level' => 'internal',
            'retention_period_days' => 365,
            'change_reason' => 'Emissão inicial controlada',
        ]);

        $uploadResponse->assertStatus(201);
        $fileId = $uploadResponse->json('data.id');

        $this->assertNotNull($fileId);
        $this->assertDatabaseHas('v_files', [
            'id' => $fileId,
            'document_number' => 'DOC-ISO-001',
            'status' => 'draft',
            'revision_code' => 'R01',
        ]);
        $this->assertSame('text', Schema::getColumnType('v_files', 'content'));
        $this->assertSame('text', Schema::getColumnType('v_file_versions', 'content'));

        $file = VAPFile::query()->findOrFail($fileId);
        $this->assertIsString($file->content);
        $this->assertSame($file->content, $file->versions()->firstOrFail()->content);
        Storage::disk(config('filesystems.default', 'local'))->assertExists($file->content);
        $this->actingAs($admin)->get(route('files.download', $file))->assertOk();

        $this->actingAs($admin)
            ->post(route('files.submit-review', $fileId), [
                'assigned_to' => $admin->id,
                'change_reason' => 'Submissão para revisão técnica',
            ])
            ->assertOk();

        $this->assertDatabaseHas('v_files', [
            'id' => $fileId,
            'status' => 'in_review',
        ]);

        $this->assertDatabaseHas('workflow_tasks', [
            'file_id' => $fileId,
            'type' => 'review',
            'assigned_to' => $admin->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('files.approve', $fileId), [
                'change_reason' => 'Aprovado para uso controlado',
                'review_due_at' => now()->addMonths(6)->toDateString(),
            ])
            ->assertOk();

        $this->assertDatabaseHas('v_files', [
            'id' => $fileId,
            'status' => 'effective',
            'approved_by' => $admin->id,
        ]);

        $this->assertSame(
            1,
            WorkflowTask::query()->where('file_id', $fileId)->where('status', 'completed')->count()
        );
    }

    public function test_a_new_revision_keeps_the_documents_control_data_and_starts_as_a_draft(): void
    {
        Storage::fake(config('filesystems.default', 'local'));

        $admin = $this->verifiedAdmin();

        $fileId = $this->actingAs($admin)->post(route('files.upload'), [
            'file' => UploadedFile::fake()->create('procedimento-recepcao.pdf', 80, 'application/pdf'),
            'document_number' => 'PT-01',
            'document_type' => 'Procedimento',
            'category' => 'Recepção',
            'confidentiality_level' => 'restricted',
            'retention_period_days' => 1825,
        ])->json('data.id');

        $reviewDue = now()->addYear()->toDateString();
        $this->actingAs($admin)->post(route('files.approve', $fileId), [
            'change_reason' => 'Aprovado para uso controlado',
            'review_due_at' => $reviewDue,
        ])->assertOk();

        // The file list replaces a document by uploading the same name again, with nothing but the file.
        $this->actingAs($admin)->post(route('files.upload'), [
            'file' => UploadedFile::fake()->create('procedimento-recepcao.pdf', 95, 'application/pdf'),
            'override' => true,
        ])->assertOk()
            ->assertJsonPath('data.document_number', 'PT-01')
            ->assertJsonPath('data.status', 'draft');

        $file = VAPFile::query()->findOrFail($fileId);

        // Identification and control data stay with the document.
        $this->assertSame('R02', $file->revision_code);
        $this->assertSame('PT-01', $file->document_number);
        $this->assertSame('Procedimento', $file->document_type);
        $this->assertSame('Recepção', $file->category);
        $this->assertSame('restricted', $file->confidentiality_level);
        $this->assertSame(1825, (int) $file->retention_period_days);
        $this->assertSame($reviewDue, $file->review_due_at?->toDateString());

        // The new revision is not approved or effective until it goes through approval again.
        $this->assertSame('draft', $file->status);
        $this->assertNull($file->effective_at);
        $this->assertNull($file->approved_at);
        $this->assertNull($file->approved_by);

        // Stating control data with the upload still changes it.
        $this->actingAs($admin)->post(route('files.upload'), [
            'file' => UploadedFile::fake()->create('procedimento-recepcao.pdf', 99, 'application/pdf'),
            'override' => true,
            'category' => 'Amostras',
        ])->assertOk();

        $file->refresh();
        $this->assertSame('R03', $file->revision_code);
        $this->assertSame('Amostras', $file->category);
        $this->assertSame('PT-01', $file->document_number);
    }

    public function test_deleting_a_document_removes_its_files_and_leaves_a_record_of_it(): void
    {
        $disk = config('filesystems.default', 'local');
        Storage::fake($disk);

        $admin = $this->verifiedAdmin();

        $fileId = $this->actingAs($admin)->post(route('files.upload'), [
            'file' => UploadedFile::fake()->create('registo-obsoleto.pdf', 40, 'application/pdf'),
        ])->json('data.id');
        $this->actingAs($admin)->post(route('files.upload'), [
            'file' => UploadedFile::fake()->create('registo-obsoleto.pdf', 45, 'application/pdf'),
            'override' => true,
        ])->assertOk();

        $file = VAPFile::query()->findOrFail($fileId);
        $stored = $file->versions()->pluck('content')->all();
        $this->assertCount(2, $stored);

        // Someone of the laboratory without rights over the document cannot delete it.
        $colleague = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $colleague->id]);
        $this->actingAs($colleague)->delete(route('files.destroy', $fileId))->assertForbidden();
        $this->assertDatabaseHas('v_files', ['id' => $fileId]);

        $this->actingAs($admin)->delete(route('files.destroy', $fileId))->assertOk();

        $this->assertDatabaseMissing('v_files', ['id' => $fileId]);
        $this->assertDatabaseMissing('v_file_versions', ['file_id' => $fileId]);
        foreach ($stored as $path) {
            Storage::disk($disk)->assertMissing($path);
        }
        $this->assertTrue(
            Activity::query()->where('log_name', 'document_control')->where('event', 'deleted')->where('properties', 'like', '%'.$fileId.'%')->exists()
        );
    }

    public function test_a_folder_is_deleted_only_when_nothing_is_left_inside_it(): void
    {
        Storage::fake(config('filesystems.default', 'local'));

        $admin = $this->verifiedAdmin();
        $folder = VAPFile::query()->forceCreate([
            'name' => 'Procedimentos',
            'type' => 'folder',
            'lab_id' => $this->lab->id,
            'created_by' => $admin->id,
            'owner_id' => $admin->id,
            'modified_at' => now(),
        ]);

        $fileId = $this->actingAs($admin)->post(route('files.upload'), [
            'file' => UploadedFile::fake()->create('pt-01.pdf', 40, 'application/pdf'),
            'parent_id' => $folder->id,
            'document_number' => 'PT-01',
        ])->json('data.id');

        // Deleting the folder would take the document with it, unrecorded: it is refused.
        $this->actingAs($admin)->deleteJson(route('files.destroy', $folder->id))
            ->assertStatus(422)
            ->assertJsonPath('message', 'A pasta ainda tem documentos ou pastas, incluindo os arquivados. Mova-os ou elimine-os primeiro.');
        $this->assertDatabaseHas('v_files', ['id' => $folder->id]);
        $this->assertDatabaseHas('v_files', ['id' => $fileId, 'document_number' => 'PT-01']);

        // An archived document still counts: it is still on record in that folder.
        $this->actingAs($admin)->post(route('files.archive', $fileId))->assertOk();
        $this->actingAs($admin)->deleteJson(route('files.destroy', $folder->id))->assertStatus(422);

        $this->actingAs($admin)->delete(route('files.destroy', $fileId))->assertOk();
        $this->actingAs($admin)->delete(route('files.destroy', $folder->id))->assertOk();
        $this->assertDatabaseMissing('v_files', ['id' => $folder->id]);
    }

    public function test_document_versioning_archive_and_audit_trail_are_recorded(): void
    {
        Storage::fake(config('filesystems.default', 'local'));

        $admin = $this->verifiedAdmin();

        $firstResponse = $this->actingAs($admin)->post(route('files.upload'), [
            'file' => UploadedFile::fake()->create('instrucao-trabalho.pdf', 80, 'application/pdf'),
            'document_number' => 'IT-002',
            'document_type' => 'Instrução',
            'category' => 'Operações',
            'change_reason' => 'Versão inicial',
        ]);

        $fileId = $firstResponse->json('data.id');
        $this->assertNotNull($fileId);

        $this->actingAs($admin)->post(route('files.upload'), [
            'file' => UploadedFile::fake()->create('instrucao-trabalho.pdf', 90, 'application/pdf'),
            'document_number' => 'IT-002',
            'document_type' => 'Instrução',
            'category' => 'Operações',
            'override' => true,
            'change_reason' => 'Atualização do método',
        ])->assertOk();

        $this->assertDatabaseHas('v_files', [
            'id' => $fileId,
            'revision_code' => 'R02',
        ]);

        $this->assertSame(
            2,
            VAPFile::query()->findOrFail($fileId)->versions()->count()
        );

        $this->actingAs($admin)
            ->post(route('files.archive', $fileId))
            ->assertOk();

        $this->assertDatabaseHas('v_files', [
            'id' => $fileId,
            'archived' => true,
            'status' => 'archived',
        ]);

        $this->assertGreaterThanOrEqual(
            2,
            Activity::query()
                ->where('log_name', 'document_control')
                ->where('properties', 'like', '%'.$fileId.'%')
                ->count()
        );
    }

    public function test_sharing_a_controlled_document_notifies_the_recipient(): void
    {
        Storage::fake(config('filesystems.default', 'local'));
        Notification::fake();

        $admin = $this->verifiedAdmin();
        $recipient = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $recipient->id]);

        $uploadResponse = $this->actingAs($admin)->post(route('files.upload'), [
            'file' => UploadedFile::fake()->create('procedimento-partilhado.pdf', 80, 'application/pdf'),
            'document_number' => 'DOC-SHARE-001',
            'document_type' => 'Procedimento',
            'category' => 'Qualidade',
            'change_reason' => 'Emissão para partilha controlada',
        ]);

        $fileId = $uploadResponse->json('data.id');
        $this->assertNotNull($fileId);

        $this->actingAs($admin)
            ->post(route('files.share', $fileId), [
                'user_id' => $recipient->id,
                'access_level' => 'read',
            ])
            ->assertOk();

        Notification::assertSentTo(
            $recipient,
            OperationalNotification::class,
            fn (OperationalNotification $notification): bool => $notification->payload['key'] === 'documents.controlled_file.shared'
                && $notification->payload['context']['access_level'] === 'read'
        );
    }
}
