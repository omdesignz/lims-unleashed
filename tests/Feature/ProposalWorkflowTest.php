<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Customer;
use App\Models\Department;
use App\Models\LabCode;
use App\Models\Matrix;
use App\Models\Parameter;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPProposal;
use App\Models\VAPProposalItem;
use App\Models\VAPProposalTemplate;
use App\Models\Warehouse;
use App\Notifications\GlobalNotification;
use App\Notifications\ProposalSentNotification;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioPdfBuilder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProposalWorkflowTest extends TestCase
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
        $admin = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->first();

        $this->assertNotNull($admin, 'Expected at least one verified admin user for proposal workflow testing.');

        return $admin;
    }

    private function draftProposal(User $user): VAPProposal
    {
        $customer = Customer::query()->firstOrFail();
        $department = Department::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $template = VAPProposalTemplate::query()->first();

        if (! $template) {
            $template = VAPProposalTemplate::query()->create([
                'name' => 'Smoke Template',
                'content' => '<p>Smoke template</p>',
                'user_id' => $user->id,
                'is_active' => true,
            ]);
        }

        /** @var VAPProposal $proposal */
        $proposal = VAPProposal::query()->create([
            'proposal_year' => now()->year,
            'service_location' => 'Smoke Workflow',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'department_id' => $department->id,
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'PENDING',
            'details' => [],
            'obs' => 'Smoke proposal',
            'sub_total' => 0,
            'total' => 0,
            'tax' => 0,
            'discount' => 0,
            'unique_hash' => (string) str()->uuid(),
            'tolerance_days' => 30,
            'withhold_tax' => false,
            'use_matrix_price' => true,
            'withholding_tax_amount' => 0,
            'withholding_tax_percentage' => 0,
            'global_discount_amount' => 0,
            'global_discount_percentage' => 0,
            'converted_to_invoice' => false,
        ]);

        $proposal->complianceAgreement()->create([
            'confidentiality' => false,
            'impartiality' => false,
            'nondisclosure' => false,
        ]);

        return $proposal->fresh(['warehouse', 'user']);
    }

    public function test_sending_a_vap_proposal_notifies_customer_and_internal_owner(): void
    {
        Notification::fake();

        $user = $this->verifiedAdmin();
        $proposal = $this->draftProposal($user);

        ob_start();

        try {
            $response = $this->withoutMiddleware(VerifyCsrfToken::class)
                ->actingAs($user)
                ->post(route('vap-proposals.send', $proposal));
            ob_end_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        $response->assertRedirect();

        $proposal->refresh();

        $this->assertSame('SENT', $proposal->status);
        $this->assertNotNull($proposal->file_path);
        $this->assertStringContainsString('vap-proposals/', $proposal->file_path);

        Notification::assertSentTo($proposal->warehouse, ProposalSentNotification::class);
        Notification::assertSentTo($user, GlobalNotification::class);
    }

    public function test_revising_a_vap_proposal_invalidates_stale_pdf_and_send_regenerates_it(): void
    {
        Notification::fake();

        $disk = config('filesystems.default', 'local');
        Storage::fake($disk);

        $user = $this->verifiedAdmin();
        $proposal = $this->draftProposal($user);
        $unit = Unit::query()->firstOrFail();
        $stalePdfPath = 'vap-proposals/'.$proposal->id.'/'.str($proposal->proposal_number)->slug('-')->prepend('Proposta-')->append('.pdf')->value();

        Storage::disk($disk)->put($stalePdfPath, 'stale proposal pdf');
        $proposal->update(['file_path' => $stalePdfPath]);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($user)
            ->put(route('vap-proposals.update', $proposal), [
                'service_location' => 'Unidade industrial revista',
                'obs' => 'Âmbito comercial revisto antes de novo envio.',
                'tolerance_days' => 45,
                'revision_reason' => 'Actualização do âmbito técnico e comercial antes do reenvio.',
                'withhold_tax' => false,
                'use_matrix_price' => true,
                'sub_total' => 1000,
                'total' => 1000,
                'tax' => 0,
                'discount' => 0,
                'items' => [
                    [
                        'item_id' => null,
                        'itemable_type' => null,
                        'itemable_id' => null,
                        'item_description' => 'Ensaio microbiológico revisto',
                        'standard_id' => null,
                        'unit_id' => $unit->id,
                        'qty' => 2,
                        'unit_price' => 500,
                        'discount_percentage' => 0,
                        'discount_amount' => 0,
                        'discount_id' => null,
                        'tax_percentage' => 0,
                        'tax_amount' => 0,
                        'tax_id' => null,
                        'charge_tax' => false,
                        'withhold_tax' => false,
                        'exemption_id' => null,
                        'exemption_code' => null,
                        'obs' => 'Escopo actualizado',
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $proposal->refresh();

        $this->assertSame('REVISED', $proposal->status);
        $this->assertNull($proposal->file_path);
        Storage::disk($disk)->assertMissing($stalePdfPath);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($user)
            ->post(route('vap-proposals.send', $proposal))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $proposal->refresh();

        $this->assertSame('SENT', $proposal->status);
        $this->assertSame($stalePdfPath, $proposal->file_path);
        Storage::disk($disk)->assertExists($proposal->file_path);
        $this->assertStringStartsWith('%PDF-', Storage::disk($disk)->get($proposal->file_path));
    }

    public function test_legacy_proposal_pdf_endpoint_uses_report_studio_renderer(): void
    {
        $user = $this->verifiedAdmin();
        $proposal = $this->draftProposal($user);

        $response = $this->actingAs($user)
            ->get(route('proposals.getPDF', ['id' => $proposal->id]));

        $response->assertOk();
        $response->assertHeader('X-Report-Studio-Renderer');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
    }

    public function test_public_thank_you_route_uses_vap_proposal_binding(): void
    {
        $proposal = $this->draftProposal($this->verifiedAdmin());

        $response = $this->get(route('vap-proposals.public.thankyou', $proposal));

        $response->assertSuccessful();
    }

    public function test_public_accept_returns_vap_thank_you_redirect(): void
    {
        $proposal = $this->draftProposal($this->verifiedAdmin());
        $proposal->update(['status' => 'SENT']);

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->postJson(
                route('proposals.api.accept', $proposal),
                [
                    'confidentiality' => true,
                    'impartiality' => true,
                    'nondisclosure' => true,
                ]
            );

        $response
            ->assertSuccessful()
            ->assertJsonPath('redirect', route('vap-proposals.public.thankyou', $proposal));
    }

    public function test_public_reject_persists_customer_reason_in_compliance_payload(): void
    {
        $proposal = $this->draftProposal($this->verifiedAdmin());
        $proposal->update(['status' => 'SENT']);
        $reason = 'O âmbito técnico precisa de revisão antes da aprovação.';

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->postJson(route('proposals.api.reject', $proposal), [
                'reason' => $reason,
            ]);

        $response
            ->assertSuccessful()
            ->assertJsonPath('redirect', route('vap-proposals.public.thankyou', $proposal));

        $proposal->refresh()->load('complianceAgreement');

        $this->assertSame('REJECTED', $proposal->status);
        $this->assertSame($reason, $proposal->complianceAgreement->rejection_reason);
        $this->assertNotNull($proposal->complianceAgreement->rejected_at);
        $this->assertNull($proposal->complianceAgreement->acknowledged_at);

        $this->get(route('vap-proposals.public.show', $proposal->unique_hash))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('proposal.compliance_agreement.rejection_reason', $reason)
            );
    }

    public function test_public_proposal_show_exposes_company_and_banking_metadata(): void
    {
        $proposal = $this->draftProposal($this->verifiedAdmin());
        $settings = app(GeneralSettings::class);
        $original = [
            'app_name' => $settings->app_name,
            'app_slogan' => $settings->app_slogan,
            'app_logo_url' => $settings->app_logo_url,
            'app_client_name' => $settings->app_client_name,
            'app_client_nif' => $settings->app_client_nif,
            'app_client_address' => $settings->app_client_address,
            'app_client_contact' => $settings->app_client_contact,
            'app_client_email' => $settings->app_client_email,
            'app_client_lab_name' => $settings->app_client_lab_name,
            'app_client_lab_director' => $settings->app_client_lab_director,
            'app_client_lab_slogan' => $settings->app_client_lab_slogan,
            'app_bank_name' => $settings->app_bank_name,
            'app_bank_account_name' => $settings->app_bank_account_name,
            'app_bank_account_number' => $settings->app_bank_account_number,
            'app_bank_iban' => $settings->app_bank_iban,
            'app_bank_swift' => $settings->app_bank_swift,
            'app_bank_details' => $settings->app_bank_details,
            'app_document_keywords' => $settings->app_document_keywords,
        ];

        try {
            $settings->fill([
                'app_client_lab_name' => 'Laboratório de Qualidade Kudi',
                'app_client_lab_slogan' => 'Evidência técnica para decisões seguras',
                'app_logo_url' => 'https://cdn.example.test/brand/kudi.svg',
                'app_client_address' => 'Rua da Qualidade, Luanda',
                'app_client_contact' => '+244 900 000 000',
                'app_client_email' => 'comercial@kudi.test',
                'app_client_nif' => '5000000000',
                'app_client_lab_director' => 'Direcção Técnica Kudi',
                'app_bank_name' => 'Banco de Qualidade',
                'app_bank_account_name' => 'Laboratório de Qualidade Kudi',
                'app_bank_account_number' => '00123456789',
                'app_bank_iban' => 'AO06004400000012345678901',
                'app_bank_swift' => 'BQAUAOLU',
                'app_bank_details' => 'Pagamentos por transferência identificada.',
                'app_document_keywords' => 'proposta, ISO 17025, rastreabilidade',
            ]);
            $settings->save();

            $this->get(route('vap-proposals.public.show', $proposal->unique_hash))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('Public/ProposalShow')
                    ->where('proposal.id', $proposal->id)
                    ->where('company.name', 'Laboratório de Qualidade Kudi')
                    ->where('company.tagline', 'Evidência técnica para decisões seguras')
                    ->where('company.logo_url', 'https://cdn.example.test/brand/kudi.svg')
                    ->where('company.address', 'Rua da Qualidade, Luanda')
                    ->where('company.bank_name', 'Banco de Qualidade')
                    ->where('company.bank_iban', 'AO06004400000012345678901')
                    ->where('company.document_keywords', 'proposta, ISO 17025, rastreabilidade')
                );
        } finally {
            $settings = app(GeneralSettings::class);
            $settings->fill($original);
            $settings->save();
        }
    }

    public function test_vap_proposal_show_exposes_template_author_and_compliance_payload(): void
    {
        $user = $this->verifiedAdmin();
        $proposal = $this->draftProposal($user);
        $settings = app(GeneralSettings::class);
        $original = [
            'app_client_lab_director' => $settings->app_client_lab_director,
            'app_bank_name' => $settings->app_bank_name,
            'app_bank_account_name' => $settings->app_bank_account_name,
            'app_bank_account_number' => $settings->app_bank_account_number,
            'app_bank_iban' => $settings->app_bank_iban,
            'app_bank_swift' => $settings->app_bank_swift,
            'app_bank_details' => $settings->app_bank_details,
            'app_document_keywords' => $settings->app_document_keywords,
        ];

        try {
            $settings->fill([
                'app_client_lab_director' => 'Direcção Técnica QA',
                'app_bank_name' => 'Banco QA',
                'app_bank_account_name' => 'Laboratório QA',
                'app_bank_account_number' => '000123456',
                'app_bank_iban' => 'AO06000000000001234567890',
                'app_bank_swift' => 'QAQAAOLU',
                'app_bank_details' => 'Transferência identificada pela proposta.',
                'app_document_keywords' => 'proposta, rastreabilidade, ISO 17025',
            ]);
            $settings->save();

            VAPProposalTemplate::query()->whereKey($proposal->template_id)->update([
                'user_id' => $user->id,
                'content' => '<p>{customer_name}</p><p>{banking_details}</p><p>{{bank_iban}}</p><p>{document_keywords}</p><p>{signature_block}</p>',
            ]);

            $this->actingAs($user)
                ->get(route('vap-proposals.show', $proposal))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('VAPProposals/Show')
                    ->where('proposal.id', $proposal->id)
                    ->where('proposal.template.user.id', $user->id)
                    ->where('proposal.compliance_agreement.proposal_id', $proposal->id)
                    ->where('parsedTemplateContent', fn (string $content): bool => str_contains($content, $proposal->customer->name)
                        && str_contains($content, 'Banco QA')
                        && str_contains($content, 'AO06000000000001234567890')
                        && str_contains($content, 'ISO 17025')
                        && str_contains($content, 'Direcção Técnica QA')
                        && ! str_contains($content, '{banking_details}')
                        && ! str_contains($content, '{{bank_iban}}'))
                );
        } finally {
            $settings = app(GeneralSettings::class);
            $settings->fill($original);
            $settings->save();
        }
    }

    public function test_vap_proposal_templates_parse_authenticity_and_acceptance_evidence_blocks(): void
    {
        $user = $this->verifiedAdmin();
        $proposal = $this->draftProposal($user);
        $proposal->update(['status' => 'ACCEPTED']);
        $proposal->complianceAgreement()->update([
            'confidentiality' => true,
            'impartiality' => true,
            'nondisclosure' => true,
            'acknowledged_at' => now()->setSecond(0),
            'client_ip' => '203.0.113.24',
        ]);

        VAPProposalTemplate::query()->whereKey($proposal->template_id)->update([
            'user_id' => $user->id,
            'content' => '<section>{proposal_authenticity}</section><section>{proposal_acceptance_evidence}</section><p>{verification_url}</p>',
        ]);

        $verificationUrl = route('vap-proposals.public.show', $proposal->unique_hash);

        $this->actingAs($user)
            ->get(route('vap-proposals.show', $proposal))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('VAPProposals/Show')
                ->where('parsedTemplateContent', fn (string $content): bool => str_contains($content, 'Verificação da proposta')
                    && str_contains($content, 'Evidência de aceite')
                    && str_contains($content, 'Aceite pelo cliente')
                    && str_contains($content, 'Confidencialidade')
                    && str_contains($content, 'Não divulgação')
                    && str_contains($content, '203.0.113.24')
                    && str_contains($content, $verificationUrl)
                    && str_contains($content, 'data:image/svg+xml')
                    && ! str_contains($content, '{proposal_authenticity}')
                    && ! str_contains($content, '{proposal_acceptance_evidence}')
                    && ! str_contains($content, '{verification_url}'))
            );
    }

    public function test_vap_proposal_index_can_be_filtered_by_template_context(): void
    {
        $user = $this->verifiedAdmin();

        $template = VAPProposalTemplate::query()->create([
            'name' => 'Modelo de filtro comercial',
            'category' => 'compliance',
            'content' => '<p>Modelo filtrado</p>',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $otherTemplate = VAPProposalTemplate::query()->create([
            'name' => 'Modelo não listado',
            'category' => 'general',
            'content' => '<p>Outro modelo</p>',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $firstProposal = $this->draftProposal($user);
        $secondProposal = $this->draftProposal($user);
        $hiddenProposal = $this->draftProposal($user);

        $firstProposal->update(['template_id' => $template->id, 'status' => 'ACCEPTED', 'total' => 1200]);
        $secondProposal->update(['template_id' => $template->id, 'status' => 'PENDING', 'total' => 800]);
        $hiddenProposal->update(['template_id' => $otherTemplate->id, 'status' => 'ACCEPTED', 'total' => 9999]);

        $this->actingAs($user)
            ->get(route('vap-proposals.index', ['template_id' => $template->id, 'period' => 90]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('VAPProposals/Index')
                ->where('filters.template_id', $template->id)
                ->where('filters.period', 90)
                ->where('selectedTemplate.name', 'Modelo de filtro comercial')
                ->where('proposals.total', 2)
                ->where('stats.total', 2)
                ->where('stats.accepted', 1)
            );
    }

    public function test_proposal_template_create_exposes_full_base_models_for_inline_selection(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->get(route('vap-proposals.templates.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('VAPProposalTemplates/Create')
                ->where('presets.0.slug', 'standard-commercial')
                ->where('presets.0.name', 'Proposta comercial executiva')
                ->has('presets.0.content')
                ->has('presets.0.layout_schema.first_page_header_html')
                ->where('presets.0.layout_schema.canvas_blocks.0.block_kind', 'rich_text')
                ->where('presets.0.layout_schema.canvas_blocks.1.block_kind', 'qr_code')
                ->where('presets.0.layout_schema.canvas_blocks.2.block_kind', 'signature')
                ->where('presets.0.layout_schema.canvas_blocks.3.block_kind', 'chart_snapshot')
                ->where('presets.0.layout_schema.canvas_blocks.3.chart_title', 'Resumo visual do âmbito')
                ->where('presets.0.content', fn (string $content) => str_contains($content, '{lab_details}')
                    && str_contains($content, '{customer_details}')
                    && str_contains($content, '{banking_details}')
                    && str_contains($content, '{signature_block}'))
                ->where('presets.0.layout_schema.variable_catalog.5.value', '{lab_details}')
                ->where('presets.0.layout_schema.variable_catalog.6.value', '{customer_details}')
                ->where('presets.0.layout_schema.variable_catalog.7.value', '{banking_details}')
                ->where('presets.1.layout_schema.canvas_blocks.2.qr_label', 'Validar proposta {proposal_number}')
                ->where('presets.2.layout_schema.canvas_blocks.1.title', 'Nota de custódia')
                ->where('presets.0.export_settings.paper_size', 'A4')
                ->where('variables.27', '{banking_details}')
                ->where('variables.28', '{document_keywords}')
                ->where('variables', fn ($variables): bool => collect($variables)->contains('{proposal_authenticity}')
                    && collect($variables)->contains('{proposal_acceptance_evidence}')
                    && collect($variables)->contains('{verification_url}'))
            );

        $template = VAPProposalTemplate::query()->create([
            'name' => 'Modelo para edição com variáveis actuais',
            'category' => 'general',
            'content' => '<p>{items_table}</p>',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('vap-proposals.templates.edit', $template))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('VAPProposalTemplates/Edit')
                ->where('template.id', $template->id)
                ->where('presets.1.slug', 'iso-decision-rule')
                ->where('variables.27', '{banking_details}')
                ->where('variables.28', '{document_keywords}')
                ->where('variables', fn ($variables): bool => collect($variables)->contains('{proposal_authenticity}')
                    && collect($variables)->contains('{proposal_acceptance_evidence}')
                    && collect($variables)->contains('{verification_url}'))
            );
    }

    public function test_proposal_template_show_uses_current_placeholder_label_catalog(): void
    {
        $user = $this->verifiedAdmin();

        $template = VAPProposalTemplate::query()->create([
            'name' => 'Modelo com catálogo actual',
            'category' => 'general',
            'content' => '<p>{proposal_authenticity}</p><p>{proposal_acceptance_evidence}</p>',
            'user_id' => $user->id,
            'is_active' => true,
            'layout_schema' => [
                'document_font_family' => '"Century Gothic", DejaVu Sans, sans-serif',
            ],
            'export_settings' => [
                'paper_size' => 'A4',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('vap-proposals.templates.show', $template))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('VAPProposalTemplates/Show')
                ->where('template.id', $template->id)
                ->where('variables', fn ($variables): bool => ($variables['{proposal_authenticity}'] ?? null) === 'QR e autenticidade da proposta'
                    && ($variables['{proposal_acceptance_evidence}'] ?? null) === 'Evidência de aceite do cliente'
                    && ($variables['{verification_url}'] ?? null) === 'Ligação pública de verificação'
                    && ($variables['{banking_details}'] ?? null) === 'Dados bancários'
                    && count($variables) === count(VAPProposalTemplate::getPlaceholders()))
            );
    }

    public function test_admin_can_create_and_update_proposal_templates_with_advanced_layout_settings(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->post(route('vap-proposals.templates.store'), [
                'name' => 'Studio Proposal Template',
                'category' => 'compliance',
                'description' => 'Template com layout editorial multi-página.',
                'theme_preset' => 'compliance',
                'is_active' => true,
                'content' => '<h1>Proposta</h1><p>{items_table}</p>',
                'layout_schema' => [
                    'first_page_header_html' => '<div>Cover</div>',
                    'default_header_html' => '<div>Header</div>',
                    'footer_html' => '<div>Footer</div>',
                    'styles_css' => 'body{color:#0f172a;}',
                    'document_font_family' => '"Century Gothic", DejaVu Sans, sans-serif',
                    'canvas_blocks' => [
                        [
                            'id' => 'hero-band',
                            'title' => 'Hero band',
                            'surface' => 'content',
                            'content_html' => '<div>Hero</div>',
                            'x' => 0,
                            'y' => 0,
                            'width' => 100,
                            'min_height' => 48,
                            'z_index' => 20,
                            'padding' => 16,
                            'background_color' => 'rgba(15,23,42,0.92)',
                            'background_image' => '/storage/proposals/blocks/hero-cover.png',
                            'background_image_fit' => 'cover',
                            'background_image_position' => 'center center',
                            'overlay_color' => 'rgba(15,23,42,0.65)',
                            'overlay_opacity' => 0.5,
                            'text_color' => '#ffffff',
                            'border_width' => 2,
                            'border_color' => 'rgba(255,255,255,0.4)',
                            'border_radius' => 24,
                            'opacity' => 0.95,
                            'text_align' => 'center',
                            'font_size' => 16,
                            'line_height' => 1.5,
                            'is_locked' => true,
                            'page_scope' => 'all',
                            'page_number' => null,
                        ],
                    ],
                    'background_image_path' => '/storage/proposals/backgrounds/cover.png',
                    'background_size' => 'cover',
                    'background_position' => 'top center',
                    'background_repeat' => 'no-repeat',
                ],
                'export_settings' => [
                    'paper_size' => 'A4',
                    'orientation' => 'P',
                    'margin_top' => 24,
                    'margin_right' => 16,
                    'margin_bottom' => 20,
                    'margin_left' => 16,
                    'first_page_margin_top' => 62,
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $template = VAPProposalTemplate::query()
            ->where('name', 'Studio Proposal Template')
            ->firstOrFail();

        $this->assertSame('top center', data_get($template->layout_schema, 'background_position'));
        $this->assertSame('"Century Gothic", DejaVu Sans, sans-serif', data_get($template->layout_schema, 'document_font_family'));
        $this->assertSame('Hero band', data_get($template->layout_schema, 'canvas_blocks.0.title'));
        $this->assertSame('A4', data_get($template->export_settings, 'paper_size'));

        $this->actingAs($user)
            ->put(route('vap-proposals.templates.update', $template), [
                'name' => 'Studio Proposal Template v2',
                'category' => 'general',
                'description' => 'Template actualizado.',
                'theme_preset' => 'corporate',
                'is_active' => true,
                'content' => '<h1>Proposta comercial</h1><p>{summary_table}</p>',
                'layout_schema' => [
                    'first_page_header_html' => '<div>New Cover</div>',
                    'default_header_html' => '<div>New Header</div>',
                    'footer_html' => '<div>New Footer</div>',
                    'styles_css' => 'body{color:#111827;}',
                    'canvas_blocks' => [
                        [
                            'id' => 'compliance-chip',
                            'title' => 'Compliance chip',
                            'surface' => 'first_page_header_html',
                            'content_html' => '<div>ISO 17025</div>',
                            'x' => 62,
                            'y' => 8,
                            'width' => 28,
                            'min_height' => 20,
                            'z_index' => 30,
                            'padding' => 10,
                            'background_color' => '#14532d',
                            'background_image' => '/storage/proposals/blocks/compliance-chip.png',
                            'background_image_fit' => 'contain',
                            'background_image_position' => 'top center',
                            'overlay_color' => 'rgba(20,83,45,0.5)',
                            'overlay_opacity' => 0.35,
                            'text_color' => '#ffffff',
                            'border_width' => 1,
                            'border_color' => '#86efac',
                            'border_radius' => 999,
                            'opacity' => 1,
                            'text_align' => 'center',
                            'font_size' => 12,
                            'line_height' => 1.2,
                            'is_locked' => false,
                            'page_scope' => 'specific',
                            'page_number' => 2,
                        ],
                    ],
                    'background_image_path' => '/storage/proposals/backgrounds/cover-v2.png',
                    'background_size' => 'contain',
                    'background_position' => 'center center',
                    'background_repeat' => 'repeat-y',
                ],
                'export_settings' => [
                    'paper_size' => 'custom',
                    'custom_page_width' => 250,
                    'custom_page_height' => 180,
                    'orientation' => 'L',
                    'margin_top' => 18,
                    'margin_right' => 12,
                    'margin_bottom' => 18,
                    'margin_left' => 12,
                    'first_page_margin_top' => 48,
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $template->refresh();

        $this->assertSame('Studio Proposal Template v2', $template->name);
        $this->assertSame('repeat-y', data_get($template->layout_schema, 'background_repeat'));
        $this->assertSame('Compliance chip', data_get($template->layout_schema, 'canvas_blocks.0.title'));
        $this->assertSame('/storage/proposals/blocks/compliance-chip.png', data_get($template->layout_schema, 'canvas_blocks.0.background_image'));
        $this->assertSame('contain', data_get($template->layout_schema, 'canvas_blocks.0.background_image_fit'));
        $this->assertSame('rgba(20,83,45,0.5)', data_get($template->layout_schema, 'canvas_blocks.0.overlay_color'));
        $this->assertSame(1, data_get($template->layout_schema, 'canvas_blocks.0.border_width'));
        $this->assertSame('#86efac', data_get($template->layout_schema, 'canvas_blocks.0.border_color'));
        $this->assertSame('center', data_get($template->layout_schema, 'canvas_blocks.0.text_align'));
        $this->assertSame(12, data_get($template->layout_schema, 'canvas_blocks.0.font_size'));
        $this->assertFalse((bool) data_get($template->layout_schema, 'canvas_blocks.0.is_locked'));
        $this->assertSame('specific', data_get($template->layout_schema, 'canvas_blocks.0.page_scope'));
        $this->assertSame(2, data_get($template->layout_schema, 'canvas_blocks.0.page_number'));
        $this->assertSame('custom', data_get($template->export_settings, 'paper_size'));
        $this->assertSame(250, data_get($template->export_settings, 'custom_page_width'));
        $this->assertSame(180, data_get($template->export_settings, 'custom_page_height'));
        $this->assertSame('L', data_get($template->export_settings, 'orientation'));
    }

    public function test_proposal_pdf_payload_uses_template_layout_and_content_page_scopes(): void
    {
        $user = $this->verifiedAdmin();

        $template = VAPProposalTemplate::query()->create([
            'name' => 'Scoped Proposal Layout',
            'category' => 'general',
            'content' => '<p>Base</p>',
            'user_id' => $user->id,
            'is_active' => true,
            'layout_schema' => [
                'first_page_header_html' => '<div>Template Cover</div>',
                'default_header_html' => '<div>Template Header</div>',
                'footer_html' => '<div>Template Footer {PAGENO}/{nbpg}</div>',
                'canvas_blocks' => [
                    [
                        'id' => 'cover-block',
                        'title' => 'Bloco capa',
                        'surface' => 'content',
                        'content_html' => '<div>Bloco capa</div>',
                        'x' => 0,
                        'y' => 0,
                        'width' => 100,
                        'min_height' => 40,
                        'z_index' => 10,
                        'padding' => 10,
                        'background_color' => '#0f172a',
                        'background_image' => '/storage/proposals/blocks/cover-scene.png',
                        'background_image_fit' => 'cover',
                        'background_image_position' => 'center center',
                        'overlay_color' => 'rgba(15,23,42,0.7)',
                        'overlay_opacity' => 0.45,
                        'text_color' => '#ffffff',
                        'border_width' => 2,
                        'border_color' => '#94a3b8',
                        'border_radius' => 20,
                        'opacity' => 0.96,
                        'text_align' => 'center',
                        'font_size' => 18,
                        'line_height' => 1.4,
                        'is_locked' => true,
                        'page_scope' => 'first',
                    ],
                    [
                        'id' => 'all-pages-block',
                        'title' => 'Bloco persistente',
                        'surface' => 'content',
                        'content_html' => '<div>Bloco em todas · {customer_name}</div>',
                        'x' => 10,
                        'y' => 10,
                        'width' => 80,
                        'min_height' => 32,
                        'z_index' => 20,
                        'padding' => 8,
                        'background_color' => '#1d4ed8',
                        'background_image' => '/storage/proposals/blocks/all-pages-banner.png',
                        'background_image_fit' => 'contain',
                        'background_image_position' => 'top center',
                        'overlay_color' => 'rgba(29,78,216,0.4)',
                        'overlay_opacity' => 0.3,
                        'text_color' => '#ffffff',
                        'border_width' => 1,
                        'border_color' => '#bfdbfe',
                        'border_radius' => 18,
                        'opacity' => 0.92,
                        'text_align' => 'left',
                        'font_size' => 13,
                        'line_height' => 1.6,
                        'is_locked' => false,
                        'page_scope' => 'all',
                    ],
                    [
                        'id' => 'second-page-block',
                        'title' => 'Bloco página 2',
                        'surface' => 'content',
                        'content_html' => '<div>Bloco página 2</div>',
                        'x' => 12,
                        'y' => 14,
                        'width' => 76,
                        'min_height' => 32,
                        'z_index' => 30,
                        'padding' => 8,
                        'background_color' => '#14532d',
                        'background_image' => '/storage/proposals/blocks/page-two-panel.png',
                        'background_image_fit' => 'cover',
                        'background_image_position' => 'bottom center',
                        'overlay_color' => 'rgba(20,83,45,0.55)',
                        'overlay_opacity' => 0.4,
                        'text_color' => '#ffffff',
                        'border_width' => 1,
                        'border_color' => '#86efac',
                        'border_radius' => 18,
                        'opacity' => 0.9,
                        'text_align' => 'justify',
                        'font_size' => 12,
                        'line_height' => 1.5,
                        'is_locked' => false,
                        'page_scope' => 'specific',
                        'page_number' => 2,
                    ],
                    [
                        'id' => 'client-signature',
                        'title' => 'Aceitação do cliente',
                        'surface' => 'content',
                        'block_kind' => 'signature',
                        'content_html' => '',
                        'x' => 58,
                        'y' => 72,
                        'width' => 32,
                        'min_height' => 120,
                        'z_index' => 40,
                        'padding' => 10,
                        'background_color' => 'rgba(255,255,255,0.9)',
                        'text_color' => '#0f172a',
                        'border_width' => 1,
                        'border_color' => '#cbd5e1',
                        'border_radius' => 18,
                        'opacity' => 1,
                        'text_align' => 'right',
                        'font_size' => 12,
                        'line_height' => 1.4,
                        'signature_label' => 'Aceitação do cliente',
                        'signature_name' => '{{customer_name}}',
                        'signature_title' => 'Cliente / representante',
                        'signature_image' => '/storage/proposals/signatures/customer.png',
                        'signature_image_fit' => 'cover',
                        'signature_image_position' => '37% 68%',
                        'signature_image_width' => 220,
                        'signature_image_height' => 92,
                        'signature_line_style' => 'dashed',
                        'signature_align' => 'right',
                        'signature_show_date' => true,
                        'signature_date_label' => 'Data da aceitação: ____ / ____ / ______',
                        'page_scope' => 'following',
                    ],
                    [
                        'id' => 'proposal-qr',
                        'title' => 'QR de validação',
                        'surface' => 'content',
                        'block_kind' => 'qr_code',
                        'x' => 4,
                        'y' => 72,
                        'width' => 18,
                        'min_height' => 110,
                        'z_index' => 45,
                        'padding' => 10,
                        'background_color' => '#ffffff',
                        'border_width' => 1,
                        'border_color' => '#cbd5e1',
                        'border_radius' => 18,
                        'qr_content' => '{proposal_number} · {customer_name}',
                        'qr_label' => 'Verificar proposta {proposal_number}',
                        'page_scope' => 'first',
                    ],
                ],
            ],
            'export_settings' => [
                'paper_size' => 'Letter',
                'orientation' => 'L',
            ],
        ]);

        $proposal = $this->draftProposal($user);
        $proposal->update(['template_id' => $template->id]);
        $proposal = $proposal->fresh(['template', 'customer', 'warehouse', 'department', 'user', 'items.standard', 'items.unit']);

        $payload = app(ReportStudioPdfBuilder::class)->buildProposalPayload(
            $proposal,
            '<p>Primeira página</p><pagebreak /><p>Segunda página</p>',
            app(GeneralSettings::class)
        );

        $this->assertSame('Scoped Proposal Layout', $payload['data']['documentTitle']);
        $this->assertStringContainsString('Template Cover', $payload['data']['firstPageHeader']);
        $this->assertStringContainsString('Template Header', $payload['data']['defaultHeader']);
        $this->assertSame('Letter', $payload['data']['format']);
        $this->assertSame('L', $payload['data']['orientation']);
        $pageCount = substr_count($payload['data']['bodyHtml'], 'studio-canvas-page-');

        $this->assertGreaterThanOrEqual(2, $pageCount);
        $this->assertSame(1, substr_count($payload['data']['bodyHtml'], 'Bloco capa'));
        $this->assertSame($pageCount, substr_count($payload['data']['bodyHtml'], 'Bloco em todas'));
        $this->assertSame(1, substr_count($payload['data']['bodyHtml'], 'Bloco página 2'));
        $this->assertSame($pageCount - 1, substr_count($payload['data']['bodyHtml'], 'Cliente / representante'));
        $this->assertStringContainsString($proposal->customer->name, $payload['data']['bodyHtml']);
        $this->assertStringContainsString('Verificar proposta '.$proposal->proposal_number, $payload['data']['bodyHtml']);
        $this->assertStringNotContainsString('{customer_name}', $payload['data']['bodyHtml']);
        $this->assertStringNotContainsString('{proposal_number}', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('Cliente / representante', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('Data da aceitação: ____ / ____ / ______', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('border-top: 2px dashed rgba(148,163,184,0.85);', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('/storage/proposals/signatures/customer.png', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('width:220px; max-width:100%; height:92px; object-fit:cover; object-position:37% 68%;', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('border: 2px solid #94a3b8;', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('text-align: center;', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('font-size: 18px;', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('line-height: 1.4;', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('opacity: 0.96;', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('background-image: url("', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('background-size: cover;', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('background-position: bottom center;', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('rgba(20,83,45,0.55)', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('opacity:0.4', str_replace(' ', '', $payload['data']['bodyHtml']));
    }

    public function test_proposal_pdf_payload_uses_template_body_html_when_available(): void
    {
        $user = $this->verifiedAdmin();

        $template = VAPProposalTemplate::query()->create([
            'name' => 'Studio Body Proposal Layout',
            'category' => 'general',
            'content' => '<section class="proposal-clauses">Cláusulas aceites por {customer_name}</section>',
            'user_id' => $user->id,
            'is_active' => true,
            'layout_schema' => [
                'body_html' => '<section class="studio-body-marker"><h1>Corpo controlado pelo estúdio</h1>{proposal_content}<div class="studio-summary">{summary_table}</div><div>{banking_details}</div><div class="studio-evidence">{proposal_acceptance_evidence}</div><div class="studio-auth">{proposal_authenticity}</div></section>',
            ],
        ]);

        $proposal = $this->draftProposal($user);
        $proposal->update(['template_id' => $template->id, 'status' => 'ACCEPTED']);
        $proposal->complianceAgreement()->update([
            'confidentiality' => true,
            'impartiality' => true,
            'nondisclosure' => true,
            'acknowledged_at' => now()->setSecond(0),
            'client_ip' => '203.0.113.55',
        ]);
        $proposal = $proposal->fresh(['template', 'customer', 'warehouse', 'department', 'user', 'items.standard', 'items.unit']);
        $parsedContent = VAPProposalTemplate::parseContent($template->content, $proposal, app(GeneralSettings::class));

        $payload = app(ReportStudioPdfBuilder::class)->buildProposalPayload(
            $proposal,
            $parsedContent,
            app(GeneralSettings::class)
        );

        $this->assertStringContainsString('studio-body-marker', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('Corpo controlado pelo estúdio', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('proposal-clauses', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('Cláusulas aceites por '.$proposal->customer->name, $payload['data']['bodyHtml']);
        $this->assertStringContainsString('studio-summary', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('Dados bancários', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('studio-evidence', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('Aceite pelo cliente', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('203.0.113.55', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('studio-auth', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('Verificação da proposta', $payload['data']['bodyHtml']);
        $this->assertStringContainsString(route('vap-proposals.public.show', $proposal->unique_hash), $payload['data']['bodyHtml']);
        $this->assertStringContainsString('data:image/svg+xml', $payload['data']['bodyHtml']);
        $this->assertStringNotContainsString('{proposal_content}', $payload['data']['bodyHtml']);
        $this->assertStringNotContainsString('{summary_table}', $payload['data']['bodyHtml']);
        $this->assertStringNotContainsString('{proposal_acceptance_evidence}', $payload['data']['bodyHtml']);
        $this->assertStringNotContainsString('{proposal_authenticity}', $payload['data']['bodyHtml']);
        $this->assertStringNotContainsString('Proposta comercial</div>', $payload['data']['bodyHtml']);
    }

    public function test_proposal_template_qr_customisation_is_persisted_and_validated(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->post(route('vap-proposals.templates.store'), [
                'name' => 'Proposal QR Studio Template',
                'content' => '<p>Proposta {proposal_number}</p>',
                'layout_schema' => [
                    'canvas_blocks' => [
                        [
                            'id' => 'proposal-verification-qr',
                            'block_kind' => 'qr_code',
                            'qr_content' => 'Proposta {proposal_number}',
                            'qr_label' => 'Validar proposta',
                            'qr_foreground_color' => '#143d37',
                            'qr_background_color' => '#f7f1e7',
                            'qr_error_correction' => 'high',
                            'qr_margin' => 14,
                        ],
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $template = VAPProposalTemplate::query()
            ->where('name', 'Proposal QR Studio Template')
            ->firstOrFail();

        $this->assertSame('#143d37', data_get($template->layout_schema, 'canvas_blocks.0.qr_foreground_color'));
        $this->assertSame('#f7f1e7', data_get($template->layout_schema, 'canvas_blocks.0.qr_background_color'));
        $this->assertSame('high', data_get($template->layout_schema, 'canvas_blocks.0.qr_error_correction'));
        $this->assertSame(14, data_get($template->layout_schema, 'canvas_blocks.0.qr_margin'));

        $this->actingAs($user)
            ->post(route('vap-proposals.templates.store'), [
                'name' => 'Invalid Proposal QR Studio Template',
                'content' => '<p>Proposta inválida</p>',
                'layout_schema' => [
                    'canvas_blocks' => [
                        [
                            'id' => 'invalid-store-qr',
                            'block_kind' => 'qr_code',
                            'qr_foreground_color' => 'navy',
                            'qr_background_color' => '#ffffff',
                            'qr_error_correction' => 'extreme',
                            'qr_margin' => 80,
                        ],
                    ],
                ],
            ])
            ->assertSessionHasErrors([
                'layout_schema.canvas_blocks.0.qr_foreground_color',
                'layout_schema.canvas_blocks.0.qr_error_correction',
                'layout_schema.canvas_blocks.0.qr_margin',
            ]);

        $this->actingAs($user)
            ->put(route('vap-proposals.templates.update', $template), [
                'name' => $template->name,
                'content' => $template->content,
                'layout_schema' => [
                    'canvas_blocks' => [
                        [
                            'id' => 'invalid-update-qr',
                            'block_kind' => 'qr_code',
                            'qr_foreground_color' => '#ffffff',
                            'qr_background_color' => 'transparent',
                            'qr_error_correction' => 'high',
                            'qr_margin' => -1,
                        ],
                    ],
                ],
            ])
            ->assertSessionHasErrors([
                'layout_schema.canvas_blocks.0.qr_background_color',
                'layout_schema.canvas_blocks.0.qr_margin',
            ]);
    }

    public function test_proposal_template_signature_image_customisation_is_persisted_and_validated(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->post(route('vap-proposals.templates.store'), [
                'name' => 'Proposal Signature Studio Template',
                'content' => '<p>Proposta {proposal_number}</p>',
                'layout_schema' => [
                    'canvas_blocks' => [
                        [
                            'id' => 'proposal-signature',
                            'block_kind' => 'signature',
                            'signature_label' => 'Aceitação do cliente',
                            'signature_name' => '{{customer_name}}',
                            'signature_title' => 'Representante autorizado',
                            'signature_image' => '/storage/proposals/signatures/customer.png',
                            'signature_image_fit' => 'cover',
                            'signature_image_position' => '37% 68%',
                            'signature_image_width' => 220,
                            'signature_image_height' => 92,
                        ],
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $template = VAPProposalTemplate::query()
            ->where('name', 'Proposal Signature Studio Template')
            ->firstOrFail();

        $this->assertSame('cover', data_get($template->layout_schema, 'canvas_blocks.0.signature_image_fit'));
        $this->assertSame('37% 68%', data_get($template->layout_schema, 'canvas_blocks.0.signature_image_position'));
        $this->assertSame(220, data_get($template->layout_schema, 'canvas_blocks.0.signature_image_width'));
        $this->assertSame(92, data_get($template->layout_schema, 'canvas_blocks.0.signature_image_height'));

        $this->actingAs($user)
            ->put(route('vap-proposals.templates.update', $template), [
                'name' => $template->name,
                'content' => $template->content,
                'layout_schema' => [
                    'background_position' => 'center; background:red',
                    'canvas_blocks' => [
                        [
                            'id' => 'invalid-signature',
                            'block_kind' => 'signature',
                            'signature_image_fit' => 'stretch',
                            'signature_image_position' => 'center; transform:rotate(45deg)',
                            'signature_image_width' => 800,
                            'signature_image_height' => 4,
                            'image_position' => 'left; position:fixed',
                            'background_image_position' => 'bottom; background:red',
                        ],
                    ],
                ],
            ])
            ->assertSessionHasErrors([
                'layout_schema.background_position',
                'layout_schema.canvas_blocks.0.signature_image_fit',
                'layout_schema.canvas_blocks.0.signature_image_position',
                'layout_schema.canvas_blocks.0.signature_image_width',
                'layout_schema.canvas_blocks.0.signature_image_height',
                'layout_schema.canvas_blocks.0.image_position',
                'layout_schema.canvas_blocks.0.background_image_position',
            ]);
    }

    public function test_proposal_template_chart_snapshot_blocks_are_persisted_validated_and_rendered(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->post(route('vap-proposals.templates.store'), [
                'name' => 'Proposal Chart Studio Template',
                'content' => '<p>Proposta {proposal_number}</p>',
                'layout_schema' => [
                    'canvas_blocks' => [
                        [
                            'id' => 'proposal-chart',
                            'title' => 'Âmbito visual',
                            'surface' => 'content',
                            'block_kind' => 'chart_snapshot',
                            'chart_title' => 'Resumo para {customer_name}',
                            'chart_caption' => 'Visualização comercial gerada pelo estúdio.',
                            'chart_type' => 'line',
                            'chart_labels' => ['Âmbito', 'Amostras', 'Serviços'],
                            'chart_values' => [3, 5, 8],
                            'chart_colors' => ['#143d37', '#d9b05f', '#0f766e'],
                            'chart_primary_color' => '#143d37',
                            'chart_background_color' => '#f8f4ea',
                            'chart_show_values' => true,
                            'x' => 8,
                            'y' => 24,
                            'width' => 58,
                            'min_height' => 180,
                            'page_scope' => 'first',
                        ],
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $template = VAPProposalTemplate::query()
            ->where('name', 'Proposal Chart Studio Template')
            ->firstOrFail();

        $this->assertSame('chart_snapshot', data_get($template->layout_schema, 'canvas_blocks.0.block_kind'));
        $this->assertSame('line', data_get($template->layout_schema, 'canvas_blocks.0.chart_type'));
        $this->assertSame([3, 5, 8], data_get($template->layout_schema, 'canvas_blocks.0.chart_values'));
        $this->assertSame('#f8f4ea', data_get($template->layout_schema, 'canvas_blocks.0.chart_background_color'));

        $proposal = $this->draftProposal($user);
        $proposal->update(['template_id' => $template->id]);
        $proposal = $proposal->fresh(['template', 'customer', 'warehouse', 'department', 'user', 'items.standard', 'items.unit']);

        $payload = app(ReportStudioPdfBuilder::class)->buildProposalPayload(
            $proposal,
            '<p>Proposta com gráfico</p>',
            app(GeneralSettings::class)
        );

        $this->assertStringContainsString('report-chart studio-avoid-break', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('data-chart-type="line"', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('Resumo para '.$proposal->customer->name, $payload['data']['bodyHtml']);
        $this->assertStringContainsString('Visualização comercial gerada pelo estúdio.', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('#f8f4ea', $payload['data']['bodyHtml']);
        $this->assertStringNotContainsString('{customer_name}', $payload['data']['bodyHtml']);

        $this->actingAs($user)
            ->put(route('vap-proposals.templates.update', $template), [
                'name' => $template->name,
                'content' => $template->content,
                'layout_schema' => [
                    'canvas_blocks' => [
                        [
                            'id' => 'invalid-chart',
                            'block_kind' => 'chart_snapshot',
                            'chart_type' => 'radar',
                            'chart_values' => [18, 'bad'],
                            'chart_colors' => ['#143d37', 'gold'],
                            'chart_primary_color' => 'navy',
                            'chart_background_color' => 'transparent',
                        ],
                    ],
                ],
            ])
            ->assertSessionHasErrors([
                'layout_schema.canvas_blocks.0.chart_type',
                'layout_schema.canvas_blocks.0.chart_values.1',
                'layout_schema.canvas_blocks.0.chart_colors.1',
                'layout_schema.canvas_blocks.0.chart_primary_color',
                'layout_schema.canvas_blocks.0.chart_background_color',
            ]);
    }

    public function test_proposal_template_chart_snapshot_text_lists_are_validated_and_rendered(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->post(route('vap-proposals.templates.store'), [
                'name' => 'Proposal Chart Text List Template',
                'content' => '<p>Proposta {proposal_number}</p>',
                'layout_schema' => [
                    'canvas_blocks' => [
                        [
                            'id' => 'proposal-chart-text-list',
                            'title' => 'Resumo digitado',
                            'surface' => 'content',
                            'block_kind' => 'chart_snapshot',
                            'chart_title' => 'Resumo digitado para {customer_name}',
                            'chart_caption' => 'Valores introduzidos no painel do estúdio.',
                            'chart_type' => 'bar',
                            'chart_labels' => "Âmbito\nAmostras\nServiços",
                            'chart_values' => "1,5; 2,75\n3,25",
                            'chart_colors' => '#143d37, #d9b05f; #0f766e',
                            'chart_primary_color' => '#143d37',
                            'chart_background_color' => '#f8f4ea',
                            'chart_show_values' => true,
                            'page_scope' => 'first',
                        ],
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $template = VAPProposalTemplate::query()
            ->where('name', 'Proposal Chart Text List Template')
            ->firstOrFail();

        $this->assertSame("1,5; 2,75\n3,25", data_get($template->layout_schema, 'canvas_blocks.0.chart_values'));
        $this->assertSame('#143d37, #d9b05f; #0f766e', data_get($template->layout_schema, 'canvas_blocks.0.chart_colors'));

        $proposal = $this->draftProposal($user);
        $proposal->update(['template_id' => $template->id]);
        $proposal = $proposal->fresh(['template', 'customer', 'warehouse', 'department', 'user', 'items.standard', 'items.unit']);

        $payload = app(ReportStudioPdfBuilder::class)->buildProposalPayload(
            $proposal,
            '<p>Proposta com gráfico textual</p>',
            app(GeneralSettings::class)
        );

        $this->assertStringContainsString('data-chart-type="bar"', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('Resumo digitado para '.$proposal->customer->name, $payload['data']['bodyHtml']);
        $this->assertStringContainsString('Valores introduzidos no painel do estúdio.', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('Âmbito', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('>1.5</text>', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('>2.75</text>', $payload['data']['bodyHtml']);
        $this->assertStringNotContainsString('>75</text>', $payload['data']['bodyHtml']);
        $this->assertStringContainsString('#d9b05f', $payload['data']['bodyHtml']);

        $this->actingAs($user)
            ->put(route('vap-proposals.templates.update', $template), [
                'name' => $template->name,
                'content' => $template->content,
                'layout_schema' => [
                    'canvas_blocks' => [
                        [
                            'id' => 'invalid-text-list-chart',
                            'block_kind' => 'chart_snapshot',
                            'chart_values' => '3, inválido, 8',
                            'chart_colors' => '#143d37, gold',
                        ],
                    ],
                ],
            ])
            ->assertSessionHasErrors([
                'layout_schema.canvas_blocks.0.chart_values',
                'layout_schema.canvas_blocks.0.chart_colors',
            ]);
    }

    public function test_proposal_template_chart_snapshot_allows_placeholder_series_items(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->post(route('vap-proposals.templates.store'), [
                'name' => 'Proposal Chart Placeholder Series Template',
                'content' => '<p>Proposta {proposal_number}</p>',
                'layout_schema' => [
                    'canvas_blocks' => [
                        [
                            'id' => 'proposal-chart-placeholder-series',
                            'surface' => 'content',
                            'block_kind' => 'chart_snapshot',
                            'chart_title' => 'Indicadores comerciais',
                            'chart_caption' => 'Série resolvida quando o documento é gerado.',
                            'chart_type' => 'bar',
                            'chart_labels' => ['Âmbito', 'Amostras', 'Serviços'],
                            'chart_values' => ['{proposal_chart_scope}', '{{ proposal_chart_samples }}', 8],
                            'chart_colors' => ['#143d37', '{{ proposal_chart_accent }}', '#0f766e'],
                            'chart_show_values' => true,
                        ],
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $template = VAPProposalTemplate::query()
            ->where('name', 'Proposal Chart Placeholder Series Template')
            ->firstOrFail();

        $this->assertSame('{proposal_chart_scope}', data_get($template->layout_schema, 'canvas_blocks.0.chart_values.0'));
        $this->assertSame('{{ proposal_chart_samples }}', data_get($template->layout_schema, 'canvas_blocks.0.chart_values.1'));
        $this->assertSame('{{ proposal_chart_accent }}', data_get($template->layout_schema, 'canvas_blocks.0.chart_colors.1'));
    }

    public function test_admin_can_preview_unsaved_vap_proposal_template_as_pdf(): void
    {
        $user = $this->verifiedAdmin();
        $templateCount = VAPProposalTemplate::query()->count();

        $response = $this->actingAs($user)->post(route('vap-proposals.templates.preview-draft-pdf'), [
            'name' => 'Draft Template Preview',
            'category' => 'compliance',
            'description' => 'Rascunho ainda não persistido.',
            'theme_preset' => 'compliance',
            'is_active' => true,
            'content' => '<h1>Proposta de ensaio</h1><p>{lab_details}</p><p>{customer_details}</p><p>{items_table}</p><p>{banking_details}</p><p>{signature_block}</p>',
            'layout_schema' => [
                'first_page_header_html' => '<div>{{lab_name}} · {{document_code}}</div>',
                'default_header_html' => '<div>{{document_code}} · {{customer_name}}</div>',
                'footer_html' => '<div>Página {PAGENO}/{nbpg}</div>',
                'styles_css' => '.report-table th{background:#143d37;color:#fffdf7;}',
                'document_font_family' => '"Century Gothic", DejaVu Sans, sans-serif',
                'table_header_background' => '#143d37',
                'table_header_text_color' => '#fffdf7',
                'table_border_color' => '#ded2bb',
                'canvas_blocks' => [
                    [
                        'id' => 'draft-qr',
                        'title' => 'QR do rascunho',
                        'surface' => 'content',
                        'block_kind' => 'qr_code',
                        'x' => 68,
                        'y' => 6,
                        'width' => 22,
                        'min_height' => 120,
                        'qr_content' => 'Rascunho {proposal_number} · {verification_url}',
                        'qr_label' => 'Verificar {proposal_number}',
                        'qr_foreground_color' => '#143d37',
                        'qr_background_color' => '#f7f1e7',
                        'qr_error_correction' => 'quartile',
                        'qr_margin' => 10,
                        'page_scope' => 'first',
                    ],
                ],
            ],
            'export_settings' => [
                'paper_size' => 'custom',
                'custom_page_width' => 210,
                'custom_page_height' => 297,
                'orientation' => 'P',
                'margin_top' => 22,
                'margin_right' => 14,
                'margin_bottom' => 18,
                'margin_left' => 14,
                'first_page_margin_top' => 56,
            ],
        ]);

        $response->assertOk();
        $response->assertHeader('X-Report-Studio-Renderer');
        $response->assertDownload('proposal-template-draft-draft-template-preview.pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
        $this->assertSame($templateCount, VAPProposalTemplate::query()->count());
    }

    public function test_admin_can_export_a_vap_proposal_template_as_pdf(): void
    {
        $user = $this->verifiedAdmin();
        $settings = app(GeneralSettings::class);
        $original = [
            'app_client_lab_name' => $settings->app_client_lab_name,
            'app_client_lab_director' => $settings->app_client_lab_director,
            'app_client_address' => $settings->app_client_address,
            'app_client_contact' => $settings->app_client_contact,
            'app_client_email' => $settings->app_client_email,
            'app_client_nif' => $settings->app_client_nif,
            'app_bank_name' => $settings->app_bank_name,
            'app_bank_account_name' => $settings->app_bank_account_name,
            'app_bank_account_number' => $settings->app_bank_account_number,
            'app_bank_iban' => $settings->app_bank_iban,
            'app_bank_swift' => $settings->app_bank_swift,
            'app_bank_details' => $settings->app_bank_details,
            'app_document_keywords' => $settings->app_document_keywords,
        ];

        try {
            $settings->fill([
                'app_client_lab_name' => 'Laboratório Premium QA',
                'app_client_lab_director' => 'Direcção Técnica Premium',
                'app_client_address' => 'Rua da Qualidade, Luanda',
                'app_client_contact' => '+244 900 000 001',
                'app_client_email' => 'qa@premium.test',
                'app_client_nif' => '5410000000',
                'app_bank_name' => 'Banco Premium',
                'app_bank_account_name' => 'Laboratório Premium QA',
                'app_bank_account_number' => '000987654',
                'app_bank_iban' => 'AO06000000000009876543210',
                'app_bank_swift' => 'PREMAOLU',
                'app_bank_details' => 'Usar a referência da proposta no comprovativo.',
                'app_document_keywords' => 'proposta, ISO 17025, dados bancários',
            ]);
            $settings->save();

            $template = VAPProposalTemplate::query()->create([
                'name' => 'PDF Export Template',
                'category' => 'general',
                'content' => '<h1>Template PDF</h1><p>{lab_details}</p><p>{customer_details}</p><p>{items_table}</p><p>{banking_details}</p><p>{{bank_iban}}</p><pagebreak /><p>{signature_block}</p><p>{document_keywords}</p>',
                'user_id' => $user->id,
                'is_active' => true,
                'layout_schema' => [
                    'first_page_header_html' => '<div>Template cover {{lab_name}}</div>',
                    'default_header_html' => '<div>Header padrão</div>',
                    'footer_html' => '<div>Rodapé {PAGENO}/{nbpg}</div>',
                    'canvas_blocks' => [
                        [
                            'id' => 'verification-qr',
                            'title' => 'QR de verificação',
                            'surface' => 'content',
                            'block_kind' => 'qr_code',
                            'content_html' => '',
                            'x' => 72,
                            'y' => 4,
                            'width' => 18,
                            'min_height' => 100,
                            'z_index' => 8,
                            'padding' => 8,
                            'background_color' => 'rgba(255,255,255,0.96)',
                            'border_width' => 1,
                            'border_color' => '#cbd5e1',
                            'border_radius' => 16,
                            'qr_content' => 'Proposta {proposal_number} · {customer_name} · {bank_iban}',
                            'qr_label' => 'Verificar proposta {proposal_number}',
                            'qr_foreground_color' => '#143d37',
                            'qr_background_color' => '#f7f1e7',
                            'qr_error_correction' => 'quartile',
                            'qr_margin' => 12,
                            'page_scope' => 'first',
                        ],
                        [
                            'id' => 'signature-band',
                            'title' => 'Faixa de assinatura',
                            'surface' => 'content',
                            'block_kind' => 'signature',
                            'content_html' => '',
                            'x' => 56,
                            'y' => 72,
                            'width' => 34,
                            'min_height' => 110,
                            'z_index' => 12,
                            'padding' => 10,
                            'background_color' => 'rgba(255,255,255,0.96)',
                            'text_color' => '#0f172a',
                            'border_width' => 1,
                            'border_color' => '#cbd5e1',
                            'border_radius' => 16,
                            'signature_label' => 'Aprovação comercial',
                            'signature_name' => '{{lab_name}}',
                            'signature_title' => 'Direção comercial',
                            'signature_line_style' => 'solid',
                            'signature_align' => 'left',
                            'signature_show_date' => true,
                            'page_scope' => 'following',
                        ],
                    ],
                ],
                'export_settings' => [
                    'paper_size' => 'A4',
                    'orientation' => 'P',
                ],
            ]);

            $payload = app(ReportStudioPdfBuilder::class)->buildProposalTemplatePayload($template, $settings);
            $bodyHtml = $payload['data']['bodyHtml'];

            $this->assertStringContainsString('Laboratório Premium QA', $bodyHtml);
            $this->assertStringContainsString('Banco Premium', $bodyHtml);
            $this->assertStringContainsString('AO06000000000009876543210', $bodyHtml);
            $this->assertStringContainsString('ISO 17025', $bodyHtml);
            $this->assertStringContainsString('Direcção Técnica Premium', $bodyHtml);
            $this->assertStringContainsString('Verificar proposta PROP-2026-001', $bodyHtml);
            $this->assertStringNotContainsString('{banking_details}', $bodyHtml);
            $this->assertStringNotContainsString('{{bank_iban}}', $bodyHtml);
            $this->assertStringNotContainsString('{signature_block}', $bodyHtml);
            preg_match_all('/data:image\/svg\+xml;base64,([^"\']+)/', $bodyHtml, $svgDataUris);
            $this->assertTrue(collect($svgDataUris[1])->contains(function (string $encodedSvg): bool {
                $svg = base64_decode($encodedSvg, true);

                return is_string($svg)
                    && str_contains($svg, 'fill="#143d37"')
                    && str_contains($svg, 'fill="#f7f1e7"');
            }));

            $response = $this->actingAs($user)->get(route('vap-proposals.templates.pdf', $template));

            $response->assertOk();
            $response->assertDownload('proposal-template-pdf-export-template.pdf');
            $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
        } finally {
            $settings = app(GeneralSettings::class);
            $settings->fill($original);
            $settings->save();
        }
    }

    public function test_proposal_template_show_counts_all_statuses_and_toggle_endpoint_changes_status(): void
    {
        $user = $this->verifiedAdmin();

        $template = VAPProposalTemplate::query()->create([
            'name' => 'Template Metrics Toggle',
            'category' => 'compliance',
            'content' => '<p>Métricas</p>',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $acceptedProposal = $this->draftProposal($user);
        $pendingProposal = $this->draftProposal($user);
        $rejectedProposal = $this->draftProposal($user);

        $acceptedProposal->update(['template_id' => $template->id, 'status' => 'ACCEPTED']);
        $pendingProposal->update(['template_id' => $template->id, 'status' => 'SENT']);
        $rejectedProposal->update(['template_id' => $template->id, 'status' => 'REJECTED']);

        $response = $this->actingAs($user)->get(route('vap-proposals.templates.show', $template));

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('template.proposals_count', 3)
                ->where('template.accepted_proposals_count', 1)
                ->where('template.pending_proposals_count', 1)
                ->where('template.rejected_proposals_count', 1)
                ->where('variables.{banking_details}', 'Dados bancários')
                ->where('variables.{lab_details}', 'Dados do laboratório')
                ->where('variables.{customer_details}', 'Dados do cliente')
            );

        $this->actingAs($user)
            ->putJson(route('vap-proposals.templates.toggle-status', $template))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_active', false);

        $this->assertFalse($template->refresh()->is_active);
    }

    public function test_vap_proposal_combobox_option_endpoints_return_json_payloads(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();

        $proposal = $this->draftProposal($user);
        $warehouse = Warehouse::query()->create([
            'customer_id' => $customer->id,
            'name' => 'Armazém combobox proposta',
            'address' => 'Rua do Combobox Comercial',
        ]);
        $matrix = Matrix::query()->create([
            'code' => 'CBX-MTX-'.str()->random(8),
            'description' => 'Matriz para combobox comercial',
            'fixed_price' => 1250,
            'charge_tax' => true,
            'tax_percentage' => 14,
        ]);
        $parameter = Parameter::query()->create([
            'code' => 'CBX-PAR-'.str()->random(8),
            'name' => 'Parâmetro para combobox comercial',
            'price' => 450,
            'charge_tax' => true,
            'tax_percentage' => 14,
            'active' => true,
        ]);
        $labCode = LabCode::query()->create([
            'cl_month' => '06',
            'codeable_type' => VAPProposal::class,
            'codeable_id' => $proposal->id,
        ]);

        $routes = [
            route('vap-proposals.options.proposals', ['q' => $proposal->proposal_no]),
            route('vap-proposals.options.warehouses', ['q' => $warehouse->address]),
            route('vap-proposals.options.matrixes', ['q' => $matrix->description]),
            route('vap-proposals.options.parameters', ['q' => $parameter->name]),
            route('vap-proposals.options.lab-codes', ['q' => $labCode->code]),
            route('vap-proposals.options.lab-code-parameters', ['code_id' => 0]),
        ];

        foreach ($routes as $route) {
            $this->actingAs($user)
                ->getJson($route)
                ->assertOk()
                ->assertHeader('content-type', 'application/json');
        }

        $this->actingAs($user)
            ->getJson(route('vap-proposals.options.proposals', ['q' => $proposal->proposal_no]))
            ->assertOk()
            ->assertJsonFragment([
                'value' => $proposal->id,
                'proposal_no' => $proposal->proposal_no,
            ]);

        $this->actingAs($user)
            ->getJson(route('vap-proposals.options.warehouses', ['q' => $warehouse->address]))
            ->assertOk()
            ->assertJsonFragment([
                'value' => $warehouse->id,
                'label' => $warehouse->address,
            ]);

        $this->actingAs($user)
            ->getJson(route('vap-proposals.options.matrixes', ['q' => $matrix->description]))
            ->assertOk()
            ->assertJsonFragment([
                'value' => $matrix->id,
                'label' => $matrix->description,
            ]);

        $this->actingAs($user)
            ->getJson(route('vap-proposals.options.parameters', ['q' => $parameter->name]))
            ->assertOk()
            ->assertJsonFragment([
                'value' => $parameter->id,
                'label' => $parameter->name,
            ]);

        $this->actingAs($user)
            ->getJson(route('vap-proposals.options.lab-codes', ['q' => $labCode->code]))
            ->assertOk()
            ->assertJsonFragment([
                'value' => $labCode->id,
                'label' => $labCode->code,
            ]);
    }

    public function test_vap_proposal_tax_accessor_sums_item_tax_for_payloads_and_pdfs(): void
    {
        $user = $this->verifiedAdmin();
        $proposal = $this->draftProposal($user);

        VAPProposalItem::query()->create([
            'proposal_id' => $proposal->id,
            'itemable_id' => 1,
            'itemable_type' => Matrix::class,
            'unit_id' => Unit::query()->value('id'),
            'item_id' => 1,
            'item_description' => 'Ensaios com IVA',
            'qty' => 1,
            'unit_price' => 100,
            'total' => 100,
            'discount_amount' => 5,
            'tax_amount' => 14,
            'tax_percentage' => 14,
            'charge_tax' => true,
        ]);

        $proposal = $proposal->fresh(['items']);

        $this->assertSame(14.0, $proposal->tax);
        $this->assertSame(5.0, $proposal->discount);
        $this->assertSame(14.0, $proposal->toArray()['tax']);
        $this->assertSame(5.0, $proposal->toArray()['discount']);
    }
}
