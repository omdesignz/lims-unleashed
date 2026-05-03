<?php

namespace Tests\Feature;

use App\Models\ReportStudioTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportStudioWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private function captureResponseOutput(callable $callback): array
    {
        ob_start();

        try {
            $response = $callback();
            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        return [$response, (string) $output];
    }

    private function verifiedAdmin(): User
    {
        return Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
    }

    public function test_admin_can_view_report_studios_index(): void
    {
        $this->actingAs($this->verifiedAdmin())
            ->get(route('report-studios.index'))
            ->assertOk();
    }

    public function test_admin_can_create_and_update_report_studio_templates(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->post(route('report-studios.store'), [
                'name' => 'Executive Board Pack',
                'studio_type' => 'executive',
                'renderer' => 'canva',
                'status' => 'active',
                'is_default' => true,
                'theme_preset' => 'executive',
                'canva_design_url' => 'https://www.canva.com/design/test-report-studio',
                'description' => 'Premium board reporting template.',
                'layout_schema' => [
                    'header' => ['title' => 'Board Report'],
                    'sections' => [
                        ['key' => 'kpis', 'visible' => true],
                        ['key' => 'customers', 'visible' => true],
                    ],
                ],
                'export_settings' => ['format' => 'pdf'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $template = ReportStudioTemplate::query()->where('name', 'Executive Board Pack')->first();

        $this->assertNotNull($template);
        $this->assertTrue($template->is_default);
        $this->assertSame('executive', $template->studio_type);

        $this->actingAs($user)
            ->put(route('report-studios.update', $template), [
                'name' => 'Executive Board Pack v2',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'active',
                'is_default' => true,
                'theme_preset' => 'corporate',
                'canva_design_url' => null,
                'description' => 'Updated executive reporting template.',
                'layout_schema' => [
                    'header' => ['title' => 'Board Report v2'],
                    'sections' => [
                        ['key' => 'kpis', 'visible' => true],
                        ['key' => 'receivables', 'visible' => true],
                    ],
                ],
                'export_settings' => ['format' => 'pdf', 'orientation' => 'landscape'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('report_studio_templates', [
            'id' => $template->id,
            'name' => 'Executive Board Pack v2',
            'renderer' => 'internal',
            'theme_preset' => 'corporate',
        ]);
    }

    public function test_admin_can_export_executive_dashboard_as_pdf(): void
    {
        [$response, $output] = $this->captureResponseOutput(
            fn () => $this->actingAs($this->verifiedAdmin())
                ->get(route('dashboard.export', ['format' => 'pdf']))
        );

        $response->assertOk();
        $this->assertStringStartsWith('%PDF-', $output);
    }

    public function test_admin_can_create_proposal_studio_template(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->post(route('report-studios.store'), [
                'name' => 'Proposal Premium Layout',
                'studio_type' => 'proposal',
                'renderer' => 'internal',
                'status' => 'active',
                'is_default' => true,
                'theme_preset' => 'corporate',
                'canva_design_url' => null,
                'description' => 'Proposal multipage layout',
                'layout_schema' => [
                    'first_page_header_html' => '<div>Proposal cover</div>',
                    'default_header_html' => '<div>Proposal header</div>',
                    'footer_html' => '<div>Page {PAGENO}/{nbpg}</div>',
                ],
                'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('report_studio_templates', [
            'name' => 'Proposal Premium Layout',
            'studio_type' => 'proposal',
        ]);
    }
}
