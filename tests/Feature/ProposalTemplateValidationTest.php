<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VAPProposalTemplate;
use App\Support\ProposalTemplatePresetLibrary;
use App\Support\ProposalTemplateValidation;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProposalTemplateValidationTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('invalidPayloads')]
    public function test_authoring_and_preview_reject_invalid_data_before_persistence_or_rendering(string $mode, array $replacement, string $error): void
    {
        $actor = $this->editor();
        $template = $this->template($actor);
        $before = DB::table('proposal_templates')->orderBy('id')->get()->toArray();
        $this->mock(ReportStudioPdfRenderer::class, fn (MockInterface $mock) => $mock->shouldNotReceive('renderDocument'));
        $payload = array_replace(['name' => 'New validation draft', 'content' => '<p>Draft</p>'], $replacement);
        $this->actingAs($actor);
        $response = match ($mode) {
            'create' => $this->postJson(route('vap-proposals.templates.store'), $payload),
            'update' => $this->putJson(route('vap-proposals.templates.update', $template), $payload),
            default => $this->postJson(route('vap-proposals.templates.preview-draft-pdf'), $payload),
        };
        $response->assertUnprocessable()->assertJsonValidationErrors($error);
        $this->assertEquals($before, DB::table('proposal_templates')->orderBy('id')->get()->toArray());
    }

    /** @return array<string, array{string,array<string,mixed>,string}> */
    public static function invalidPayloads(): array
    {
        $block = fn (array $data): array => ['layout_schema' => ['canvas_blocks' => [$data]]];
        $cases = [
            'root creator' => [['user_id' => null], 'user_id'],
            'root identity' => [['id' => 1], 'id'],
            'root archive' => [['deleted_at' => null], 'deleted_at'],
            'root permission' => [['lab_id' => 1], 'lab_id'],
            'layout unknown' => [['layout_schema' => ['server_only' => null]], 'layout_schema'],
            'layout scalar' => [['layout_schema' => 'invalid'], 'layout_schema'],
            'export unknown' => [['export_settings' => ['renderer' => 'chrome']], 'export_settings'],
            'export scalar' => [['export_settings' => 'invalid'], 'export_settings'],
            'blocks map' => [['layout_schema' => ['canvas_blocks' => ['named' => ['id' => 'block']]]], 'layout_schema.canvas_blocks'],
            'blocks sparse' => [['layout_schema' => ['canvas_blocks' => [2 => ['id' => 'block']]]], 'layout_schema.canvas_blocks'],
            'block scalar' => [['layout_schema' => ['canvas_blocks' => ['invalid']]], 'layout_schema.canvas_blocks.0'],
            'block null' => [['layout_schema' => ['canvas_blocks' => [null]]], 'layout_schema.canvas_blocks.0'],
            'block empty' => [['layout_schema' => ['canvas_blocks' => [[]]]], 'layout_schema.canvas_blocks.0'],
            'block unknown' => [$block(['id' => 'block', 'server_only' => null]), 'layout_schema.canvas_blocks.0'],
            'variables map' => [['layout_schema' => ['variable_catalog' => ['named' => ['value' => '{name}']]]], 'layout_schema.variable_catalog'],
            'variable scalar' => [['layout_schema' => ['variable_catalog' => ['invalid']]], 'layout_schema.variable_catalog.0'],
            'variable null' => [['layout_schema' => ['variable_catalog' => [null]]], 'layout_schema.variable_catalog.0'],
            'variable empty' => [['layout_schema' => ['variable_catalog' => [[]]]], 'layout_schema.variable_catalog.0'],
            'variable unknown' => [['layout_schema' => ['variable_catalog' => [['value' => '{name}', 'server_only' => null]]]], 'layout_schema.variable_catalog.0'],
            'chart nested number' => [$block(['chart_values' => [['number' => 1]]]), 'layout_schema.canvas_blocks.0.chart_values.0'],
            'chart boolean number' => [$block(['chart_values' => [true]]), 'layout_schema.canvas_blocks.0.chart_values.0'],
            'chart nested color' => [$block(['chart_colors' => [['color' => '#ffffff']]]), 'layout_schema.canvas_blocks.0.chart_colors.0'],
            'chart boolean color' => [$block(['chart_colors' => [false]]), 'layout_schema.canvas_blocks.0.chart_colors.0'],
            'chart boolean label' => [$block(['chart_labels' => [true]]), 'layout_schema.canvas_blocks.0.chart_labels.0'],
            'chart sparse' => [$block(['chart_values' => [1 => 0]]), 'layout_schema.canvas_blocks.0.chart_values'],
            'chart map' => [$block(['chart_colors' => ['color' => '#ffffff']]), 'layout_schema.canvas_blocks.0.chart_colors'],
            'qr nested color' => [$block(['qr_foreground_color' => ['color' => '#ffffff']]), 'layout_schema.canvas_blocks.0.qr_foreground_color'],
            'content page number' => [$block(['surface' => 'content', 'page_scope' => 'specific', 'page_number' => null]), 'layout_schema.canvas_blocks.0.page_number'],
            'custom page width' => [['export_settings' => ['paper_size' => 'custom', 'custom_page_height' => 200]], 'export_settings.custom_page_width'],
        ];
        $result = [];
        foreach (['create', 'update', 'preview'] as $mode) {
            foreach ($cases as $name => [$payload, $error]) {
                $result[$mode.' '.$name] = [$mode, $payload, $error];
            }
        }

        return $result;
    }

    public function test_all_first_party_presets_keep_their_supported_payloads(): void
    {
        $validation = app(ProposalTemplateValidation::class);
        foreach (ProposalTemplatePresetLibrary::all() as $preset) {
            $payload = Arr::only($preset, ['name', 'category', 'description', 'theme_preset', 'is_active', 'content', 'layout_schema', 'export_settings']);
            $this->assertSame($payload, $validation->validate($payload));
        }
    }

    public function test_shared_editor_can_create_and_update_without_a_laboratory_and_omitted_fields_stay_unchanged(): void
    {
        $actor = $this->editor();
        $this->assertSame(0, DB::table('lab_user')->where('user_id', $actor->id)->count());
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.store'), ['name' => 'Minimal validation draft', 'content' => '<p>Body</p>'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $template = VAPProposalTemplate::query()->where('name', 'Minimal validation draft')->sole();
        $this->assertSame('general', $template->category);
        $this->assertTrue($template->is_active);
        $this->assertSame([], $template->layout_schema);
        $this->assertSame([], $template->export_settings);
        $this->assertSame($actor->id, $template->user_id);
        $template->forceFill(['category' => 'compliance', 'description' => 'Keep description', 'is_active' => false,
            'layout_schema' => ['styles_css' => 'body { color: #123456; }'], 'export_settings' => ['paper_size' => 'Letter']])->save();
        $before = $template->fresh()->getAttributes();
        $this->putJson(route('vap-proposals.templates.update', $template), ['name' => $template->name, 'content' => '<p>Revision</p>'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $after = $template->fresh()->getAttributes();
        unset($before['content'], $before['updated_at'], $after['content'], $after['updated_at']);
        $this->assertSame($before, $after);
        $this->assertSame('<p>Revision</p>', $template->fresh()->content);
        $this->putJson(route('vap-proposals.templates.update', $template), ['name' => $template->name, 'content' => $template->fresh()->content, 'category' => null])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('general', $template->fresh()->category);
    }

    public function test_null_optional_layouts_and_boolean_false_are_preserved(): void
    {
        $actor = $this->editor();
        $template = $this->template($actor);
        $this->actingAs($actor)->putJson(route('vap-proposals.templates.update', $template), [
            'name' => $template->name, 'content' => $template->content, 'is_active' => false,
            'layout_schema' => null, 'export_settings' => null,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $template->refresh();
        $this->assertFalse($template->is_active);
        $this->assertNull($template->layout_schema);
        $this->assertNull($template->export_settings);
    }

    #[DataProvider('nullableListModes')]
    public function test_explicit_null_nested_lists_are_safe_empty_lists_for_authoring_and_preview(string $mode): void
    {
        $actor = $this->editor();
        $template = $this->template($actor);
        $payload = ['name' => 'Nullable lists draft', 'content' => '<p>Nullable lists</p>',
            'layout_schema' => ['canvas_blocks' => null, 'variable_catalog' => null]];
        $this->actingAs($actor);
        if ($mode === 'preview') {
            $this->mock(ReportStudioPdfRenderer::class, fn (MockInterface $mock) => $mock->shouldReceive('renderDocument')->once()->andReturn(['content' => '%PDF-fixture', 'renderer' => 'mpdf']));
            $this->postJson(route('vap-proposals.templates.preview-draft-pdf'), $payload)->assertOk();
            $this->assertSame(0, VAPProposalTemplate::query()->where('name', $payload['name'])->count());
        } else {
            $response = $mode === 'create'
                ? $this->postJson(route('vap-proposals.templates.store'), $payload)
                : $this->putJson(route('vap-proposals.templates.update', $template), $payload);
            $response->assertRedirect()->assertSessionHasNoErrors();
            $stored = VAPProposalTemplate::query()->where('name', $payload['name'])->sole();
            $this->assertSame(['canvas_blocks' => [], 'variable_catalog' => []], $stored->layout_schema);
        }
        $this->assertSame(['canvas_blocks' => [], 'variable_catalog' => []], app(ProposalTemplateValidation::class)->validate($payload, null, true)['layout_schema']);
    }

    /** @return array<string,array{string}> */
    public static function nullableListModes(): array
    {
        return ['create' => ['create'], 'update' => ['update'], 'preview' => ['preview']];
    }

    public function test_pdf_reads_normalize_stored_null_lists_without_rewriting_the_template(): void
    {
        $actor = $this->editor();
        $actor->givePermissionTo(Permission::findOrCreate('export_proposal_templates', 'web'));
        $template = $this->template($actor);
        $template->forceFill(['layout_schema' => ['canvas_blocks' => null, 'variable_catalog' => null]])->save();
        $before = $template->fresh()->getAttributes();
        $this->mock(ReportStudioPdfRenderer::class, fn (MockInterface $mock) => $mock->shouldReceive('renderDocument')->once()->andReturn(['content' => '%PDF-fixture', 'renderer' => 'mpdf']));
        $this->actingAs($actor)->get(route('vap-proposals.templates.pdf', $template))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame($before, $template->fresh()->getAttributes());
    }

    #[DataProvider('previewPermissions')]
    public function test_unsaved_preview_keeps_blank_defaults_and_does_not_persist(string $permission): void
    {
        $actor = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        $before = DB::table('proposal_templates')->orderBy('id')->get()->toArray();
        $this->mock(ReportStudioPdfRenderer::class, function (MockInterface $mock): void {
            $mock->shouldReceive('renderDocument')->once()->withArgs(function (string $type, array $payload, string $filename): bool {
                $this->assertSame('proposal', $type);
                $this->assertSame('Pré-visualização do modelo de proposta', $payload['data']['documentTitle']);
                $this->assertStringContainsString('Pré-visualização da proposta', $payload['data']['bodyHtml']);
                $this->assertStringEndsWith('.pdf', $filename);

                return true;
            })->andReturn(['content' => '%PDF-fixture', 'renderer' => 'mpdf']);
        });
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.preview-draft-pdf'), ['name' => null, 'content' => null])
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertSee('%PDF-fixture');
        $this->assertEquals($before, DB::table('proposal_templates')->orderBy('id')->get()->toArray());
    }

    /** @return array<string, array{string}> */
    public static function previewPermissions(): array
    {
        return ['author' => ['add_proposal_templates'], 'editor' => ['edit_proposal_templates']];
    }

    public function test_name_checks_include_archived_rows_and_ignore_only_the_bound_update_record(): void
    {
        $actor = $this->editor();
        $template = $this->template($actor);
        $archived = VAPProposalTemplate::query()->create(['name' => 'Archived validation name', 'content' => '<p>Retained</p>', 'user_id' => $actor->id]);
        $archived->delete();
        $this->actingAs($actor);
        foreach ([$template->name, $archived->name] as $name) {
            $this->postJson(route('vap-proposals.templates.store'), ['name' => $name, 'content' => '<p>Duplicate</p>'])
                ->assertUnprocessable()->assertJsonValidationErrors('name');
        }
        $this->putJson(route('vap-proposals.templates.update', $template), ['name' => $archived->name, 'content' => '<p>Duplicate</p>'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->putJson(route('vap-proposals.templates.update', $template), ['name' => $template->name, 'content' => $template->content])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->mock(ReportStudioPdfRenderer::class, fn (MockInterface $mock) => $mock->shouldReceive('renderDocument')->once()->andReturn(['content' => '%PDF-fixture', 'renderer' => 'mpdf']));
        $this->postJson(route('vap-proposals.templates.preview-draft-pdf'), ['name' => $archived->name, 'content' => '<p>Preview only</p>'])->assertOk();
        $this->assertNotNull($archived->fresh()->deleted_at);
        $this->assertSame('<p>Retained</p>', $archived->fresh()->content);
    }

    public function test_chart_arrays_and_delimited_text_preserve_zero_decimal_commas_and_placeholders(): void
    {
        $validation = app(ProposalTemplateValidation::class);
        $payload = ['name' => 'Chart validation', 'content' => '<p>Chart</p>', 'layout_schema' => ['canvas_blocks' => [
            ['id' => 'array', 'chart_labels' => ['Zero', 'Decimal', 'Variable'], 'chart_values' => [0, '1,5', '{{ sample_count }}'],
                'chart_colors' => ['#ffffff', '{primary_color}', null]],
            ['id' => 'text', 'chart_labels' => 'Zero; Decimal; Variable', 'chart_values' => "0; 1,5\n{sample_count}",
                'chart_colors' => '#ffffff; {{ primary_color }}'],
            ['id' => 'header', 'surface' => 'first_page_header_html', 'page_scope' => 'specific', 'page_number' => null],
        ]]];
        $this->assertSame($payload, $validation->validate($payload));
        $this->assertSame(['category' => 'general'], $validation->normalize(['category' => null]));
        $this->assertSame([], $validation->normalize([]));
    }

    #[DataProvider('oversizedLists')]
    public function test_list_budgets_fail_before_wildcard_validation_expansion(array $payload, string $error): void
    {
        try {
            app(ProposalTemplateValidation::class)->rules($payload);
            $this->fail('Oversized lists must fail while constructing the rules.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($error, $exception->errors());
        }
    }

    /** @return array<string,array{array<string,mixed>,string}> */
    public static function oversizedLists(): array
    {
        $blocks = fn (array $value): array => ['layout_schema' => ['canvas_blocks' => $value]];

        return [
            'blocks' => [$blocks(array_fill(0, 501, ['id' => 'block'])), 'layout_schema.canvas_blocks'],
            'variables' => [['layout_schema' => ['variable_catalog' => array_fill(0, 501, ['value' => '{name}'])]], 'layout_schema.variable_catalog'],
            'chart array' => [$blocks([['chart_values' => array_fill(0, 1001, 0)]]), 'layout_schema.canvas_blocks.0.chart_values'],
            'chart text zero' => [$blocks([['chart_values' => implode(';', array_fill(0, 1001, '0'))]]), 'layout_schema.canvas_blocks.0.chart_values'],
            'chart total' => [$blocks(array_fill(0, 11, ['chart_values' => array_fill(0, 1000, 0)])), 'layout_schema.canvas_blocks.10.chart_values'],
        ];
    }

    public function test_transport_fields_are_not_persisted_and_internal_validation_rejects_them(): void
    {
        $actor = $this->editor();
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.store'), ['name' => 'Transport validation', 'content' => '<p>Body</p>', '_token' => 'fixture'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($actor->id, VAPProposalTemplate::query()->where('name', 'Transport validation')->sole()->user_id);
        $this->expectException(ValidationException::class);
        app(ProposalTemplateValidation::class)->validate(['name' => 'Internal draft', 'content' => '<p>Body</p>', '_token' => 'fixture']);
    }

    private function editor(): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach (['add_proposal_templates', 'edit_proposal_templates'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    private function template(User $actor): VAPProposalTemplate
    {
        return VAPProposalTemplate::query()->create([
            'name' => 'Retained validation template', 'content' => '<p>Retained</p>', 'user_id' => $actor->id,
            'category' => 'general', 'is_active' => true,
        ]);
    }
}
