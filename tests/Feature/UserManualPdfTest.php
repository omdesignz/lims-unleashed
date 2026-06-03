<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserManualPdfTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        return Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
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
