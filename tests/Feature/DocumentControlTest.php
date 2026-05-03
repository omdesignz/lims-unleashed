<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VAPFile;
use App\Models\WorkflowTask;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DocumentControlTest extends TestCase
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

        $this->assertNotNull($admin, 'Expected at least one verified admin user for document control testing.');

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
                ->where('properties', 'like', '%' . $fileId . '%')
                ->count()
        );
    }
}
