<?php

namespace Tests\Feature;

use App\Models\ReportStudioTemplate;
use App\Models\Role;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioDefaultTemplates;
use App\Support\ReportStudioPdfBuilder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Free-form documents: a template declares its own fields, and whoever issues
 * the document types a value for each.
 */
class ReportStudioCustomDocumentTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function template(User $author, array $attributes = []): ReportStudioTemplate
    {
        $defaults = ReportStudioDefaultTemplates::make(ReportStudioDefaultTemplates::CUSTOM);

        return ReportStudioTemplate::query()->create([
            'name' => 'Declaração de serviços',
            'studio_type' => ReportStudioDefaultTemplates::CUSTOM,
            'renderer' => 'internal',
            'status' => 'active',
            'is_default' => false,
            'theme_preset' => 'corporate',
            'layout_schema' => $defaults->layout_schema,
            'export_settings' => $defaults->export_settings,
            'created_by_id' => $author->id,
            'updated_by_id' => $author->id,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $layout
     * @return array<string, mixed>
     */
    private function storePayload(array $layout): array
    {
        $defaults = ReportStudioDefaultTemplates::make(ReportStudioDefaultTemplates::CUSTOM);

        return [
            'name' => 'Acta de reunião',
            'studio_type' => ReportStudioDefaultTemplates::CUSTOM,
            'renderer' => 'internal',
            'status' => 'active',
            'theme_preset' => 'corporate',
            'layout_schema' => [...$defaults->layout_schema, ...$layout],
            'export_settings' => $defaults->export_settings,
        ];
    }

    public function test_a_free_form_template_starts_with_fields_and_a_controlled_page(): void
    {
        $defaults = ReportStudioDefaultTemplates::make(ReportStudioDefaultTemplates::CUSTOM);

        $this->assertContains(ReportStudioDefaultTemplates::CUSTOM, ReportStudioDefaultTemplates::supportedTypes());
        $this->assertSame(['recipient', 'document_subject', 'document_body', 'signatory_name', 'signatory_role'], array_column($defaults->layout_schema['custom_fields'], 'key'));
        $this->assertStringContainsString('{document_body}', $defaults->layout_schema['body_html']);
        $this->assertStringContainsString('{{document_title}}', $defaults->layout_schema['first_page_header_html']);
    }

    public function test_fields_are_saved_with_the_template_and_bad_keys_are_refused(): void
    {
        $this->actingAs($this->admin());
        $field = ['key' => 'meeting_date', 'label' => 'Data da reunião', 'type' => 'date', 'sample' => '', 'required' => true];

        $this->post(route('report-studios.store'), $this->storePayload(['custom_fields' => [$field]]))->assertSessionHasNoErrors();
        $template = ReportStudioTemplate::query()->where('name', 'Acta de reunião')->firstOrFail();
        $this->assertSame('meeting_date', $template->layout_schema['custom_fields'][0]['key']);

        foreach ([
            ['key' => 'Data', 'label' => 'x', 'type' => 'text'],          // capitals
            ['key' => 'lab_name', 'label' => 'x', 'type' => 'text'],      // reserved token
            ['key' => 'notes', 'label' => 'x', 'type' => 'colour'],       // unknown type
        ] as $index => $bad) {
            $this->post(route('report-studios.store'), [...$this->storePayload(['custom_fields' => [$bad]]), 'name' => 'Inválido '.$index])
                ->assertSessionHasErrors();
        }

        $this->post(route('report-studios.store'), [...$this->storePayload(['custom_fields' => [$field, $field]]), 'name' => 'Duplicado'])
            ->assertSessionHasErrors('layout_schema.custom_fields.1.key');
    }

    public function test_an_active_free_form_template_is_offered_for_issue(): void
    {
        $admin = $this->admin();
        $active = $this->template($admin);
        $draft = $this->template($admin, ['name' => 'Rascunho', 'status' => 'draft']);

        $this->actingAs($admin)->get(route('report-studios.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('summary.custom', 2)
            ->where('templates', fn ($templates): bool => collect($templates)->firstWhere('id', $active->id)['issue_path'] === route('report-studios.issue', $active)
                && collect($templates)->firstWhere('id', $draft->id)['issue_path'] === null));
    }

    public function test_issuing_prints_the_typed_values_escaped_and_checks_required_fields(): void
    {
        $admin = $this->admin();
        $template = $this->template($admin);

        $this->actingAs($admin)
            ->postJson(route('report-studios.issue', $template), ['document_code' => 'DEC-2026-007', 'fields' => ['recipient' => 'Cliente']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fields.document_subject', 'fields.document_body']);

        $response = $this->actingAs($admin)->post(route('report-studios.issue', $template), [
            'document_code' => 'DEC-2026-007',
            'issue_date' => '2026-10-05',
            'fields' => ['document_subject' => 'Declaração', 'document_body' => 'Texto da declaração.', 'signatory_name' => 'Ana Técnica'],
        ]);

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());

        $payload = app(ReportStudioPdfBuilder::class)->buildCustomDocumentPayload($template, [
            'document_code' => 'DEC-<7>',
            'issue_date' => '2026-10-05',
            'fields' => ['recipient' => '<b>Cliente</b>', 'document_subject' => 'Assunto', 'document_body' => "Linha 1\nLinha 2", 'signatory_name' => 'Ana Técnica'],
        ], app(GeneralSettings::class));
        $body = $payload['data']['bodyHtml'];

        $this->assertStringContainsString('&lt;b&gt;Cliente&lt;/b&gt;', $body);
        $this->assertStringContainsString('Linha 1<br>', $body);
        $this->assertStringContainsString('Ana Técnica', $body);
        $this->assertStringContainsString('05/10/2026', $body);
        $this->assertStringNotContainsString('{document_body}', $body);
        $this->assertStringContainsString('DEC-&lt;7&gt;', $payload['data']['firstPageHeader']);
    }

    public function test_only_an_active_free_form_template_issues_and_only_for_an_administrator(): void
    {
        $admin = $this->admin();
        $values = ['document_code' => 'X-1', 'fields' => ['document_subject' => 'A', 'document_body' => 'B']];

        $this->actingAs($admin)->post(route('report-studios.issue', $this->template($admin, ['status' => 'draft'])), $values)->assertStatus(422);
        $this->actingAs($admin)->post(route('report-studios.issue', $this->template($admin, ['studio_type' => 'analysis'])), $values)->assertNotFound();

        $reader = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $this->actingAs($reader)->post(route('report-studios.issue', $this->template($admin)), $values)->assertForbidden();
    }
}
