<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserManualPdfTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $admin->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        return $admin;
    }

    public function test_verified_admin_can_download_pdf_user_manual(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->get(route('users.manual.pdf'));

        $response->assertOk();
        $response->assertDownload('manual-do-utilizador-gestlab.pdf');

        $content = (string) $response->baseResponse->getContent();

        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertGreaterThan(65000, strlen($content));
        $this->assertGreaterThanOrEqual(8, substr_count($content, '/Type /Page'));
    }
}
