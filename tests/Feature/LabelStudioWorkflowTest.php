<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLabel;
use App\Models\VAPLabelTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LabelStudioWorkflowTest extends TestCase
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

    public function test_admin_can_generate_label_from_inventory_source(): void
    {
        $user = $this->verifiedAdmin();
        $item = InventoryItem::query()->firstOrFail();

        $template = VAPLabelTemplate::query()->create([
            'name' => 'Etiqueta de Equipamento',
            'category' => 'equipment',
            'template_data' => [
                'content' => '{name} | {code} | {serial_number}',
                'width' => 60,
                'height' => 30,
                'background_color' => '#ffffff',
                'text_color' => '#111827',
                'font_size' => 12,
                'border_width' => 1,
                'border_color' => '#111827',
                'text_alignment' => 'center',
                'has_qr_code' => true,
                'has_barcode' => true,
                'barcode_type' => 'CODE128',
            ],
            'is_active' => true,
            'is_featured' => true,
        ]);

        $this->actingAs($user)
            ->post(route('vap_labels.label-generation.from-source'), [
                'name' => 'Etiqueta automática de equipamento',
                'template_id' => $template->id,
                'source_type' => 'equipment',
                'source_id' => $item->id,
            ])
            ->assertRedirect();

        $label = VAPLabel::query()->latest('id')->first();

        $this->assertNotNull($label);
        $this->assertSame('equipment', $label->type);
        $this->assertStringContainsString($item->name, $label->content);
        $this->assertSame('equipment', data_get($label->template_data, 'source_type'));
        $this->assertSame($item->id, data_get($label->template_data, 'source_id'));
        $this->assertSame($template->id, data_get($label->template_data, 'template_id'));
    }

    public function test_label_show_route_can_return_json_payload_for_studio_consumers(): void
    {
        $user = $this->verifiedAdmin();

        $label = VAPLabel::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'name' => 'Etiqueta JSON',
            'type' => 'custom',
            'content' => 'Conteúdo',
            'width' => 50,
            'height' => 20,
            'background_color' => '#ffffff',
            'text_color' => '#000000',
            'font_size' => 12,
            'border_width' => 1,
            'border_color' => '#000000',
            'text_alignment' => 'center',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->getJson(route('vap_labels.labels.show', $label))
            ->assertOk()
            ->assertJsonPath('label.id', $label->id)
            ->assertJsonPath('label.name', 'Etiqueta JSON');
    }

    public function test_label_show_route_includes_templates_for_show_page_actions(): void
    {
        $user = $this->verifiedAdmin();

        $label = VAPLabel::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'name' => 'Etiqueta com Modelos',
            'type' => 'custom',
            'content' => 'Conteúdo',
            'width' => 50,
            'height' => 20,
            'background_color' => '#ffffff',
            'text_color' => '#000000',
            'font_size' => 12,
            'border_width' => 1,
            'border_color' => '#000000',
            'text_alignment' => 'center',
            'is_active' => true,
        ]);

        VAPLabelTemplate::query()->create([
            'name' => 'Modelo Ativo',
            'description' => 'Disponível no ecrã de detalhe',
            'category' => 'general',
            'template_data' => ['content' => '{name}'],
            'is_active' => true,
            'is_featured' => true,
        ]);

        VAPLabelTemplate::query()->create([
            'name' => 'Modelo Inativo',
            'description' => 'Não deve ser exposto',
            'category' => 'general',
            'template_data' => ['content' => '{name}'],
            'is_active' => false,
            'is_featured' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('vap_labels.labels.show', $label))
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPLabels/Show')
                ->has('templates')
            );

        $templateNames = collect($response->inertiaProps('templates'))
            ->pluck('name');

        $this->assertTrue($templateNames->contains('Modelo Ativo'));
        $this->assertFalse($templateNames->contains('Modelo Inativo'));
    }

    public function test_label_create_route_exposes_selected_template_from_query_string(): void
    {
        $user = $this->verifiedAdmin();

        $template = VAPLabelTemplate::query()->create([
            'name' => 'Modelo Pré-selecionado',
            'description' => 'Deve preencher o estúdio',
            'category' => 'general',
            'template_data' => [
                'content' => '{name}',
                'width' => 70,
                'height' => 35,
            ],
            'is_active' => true,
            'is_featured' => false,
        ]);

        $this->actingAs($user)
            ->get(route('vap_labels.labels.create', ['template_id' => $template->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPLabels/Create')
                ->where('selectedTemplateId', $template->id)
            );
    }

    public function test_label_index_keeps_status_filter_applied_when_search_matches_name(): void
    {
        $user = $this->verifiedAdmin();

        VAPLabel::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'name' => 'Filtro Nome Ativo',
            'type' => 'custom',
            'content' => 'Ativo',
            'width' => 50,
            'height' => 20,
            'background_color' => '#ffffff',
            'text_color' => '#000000',
            'font_size' => 12,
            'border_width' => 1,
            'border_color' => '#000000',
            'text_alignment' => 'center',
            'is_active' => true,
        ]);

        VAPLabel::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'name' => 'Filtro Nome Inativo',
            'type' => 'custom',
            'content' => 'Inativo',
            'width' => 50,
            'height' => 20,
            'background_color' => '#ffffff',
            'text_color' => '#000000',
            'font_size' => 12,
            'border_width' => 1,
            'border_color' => '#000000',
            'text_alignment' => 'center',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get(route('vap_labels.labels.index', [
                'search' => 'Filtro Nome',
                'status' => 'active',
            ]))
            ->assertOk()
            ->assertSee('Filtro Nome Ativo')
            ->assertDontSee('Filtro Nome Inativo');
    }

    public function test_template_index_keeps_status_filter_applied_when_search_matches_name(): void
    {
        $user = $this->verifiedAdmin();

        VAPLabelTemplate::query()->create([
            'name' => 'Modelo Filtro Ativo',
            'description' => 'Ativo',
            'category' => 'general',
            'template_data' => ['content' => '{name}'],
            'is_active' => true,
            'is_featured' => false,
        ]);

        VAPLabelTemplate::query()->create([
            'name' => 'Modelo Filtro Inativo',
            'description' => 'Inativo',
            'category' => 'general',
            'template_data' => ['content' => '{name}'],
            'is_active' => false,
            'is_featured' => false,
        ]);

        $this->actingAs($user)
            ->get(route('vap_labels.label-templates.index', [
                'search' => 'Modelo Filtro',
                'status' => 'active',
            ]))
            ->assertOk()
            ->assertSee('Modelo Filtro Ativo')
            ->assertDontSee('Modelo Filtro Inativo');
    }

    public function test_admin_can_duplicate_existing_label(): void
    {
        $user = $this->verifiedAdmin();

        $label = VAPLabel::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'name' => 'Etiqueta Original',
            'type' => 'sample',
            'content' => 'Conteúdo original',
            'width' => 60,
            'height' => 30,
            'background_color' => '#ffffff',
            'text_color' => '#000000',
            'font_size' => 12,
            'border_width' => 1,
            'border_color' => '#000000',
            'text_alignment' => 'center',
            'has_qr_code' => true,
            'qr_code_content' => 'sample-001',
            'has_barcode' => true,
            'barcode_content' => 'sample-001',
            'barcode_type' => 'CODE128',
            'template_data' => [
                'source_type' => 'sample_entry',
                'source_id' => 15,
            ],
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('vap_labels.duplicate', $label))
            ->assertRedirect();

        $duplicate = VAPLabel::query()
            ->whereKeyNot($label->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($duplicate);
        $this->assertSame('Etiqueta Original (Copy)', $duplicate->name);
        $this->assertSame($label->content, $duplicate->content);
        $this->assertSame($label->type, $duplicate->type);
        $this->assertEquals($label->template_data, $duplicate->template_data);
        $this->assertSame($user->id, $duplicate->user_id);
        $this->assertSame($user->tenant_id, $duplicate->tenant_id);
    }

    public function test_applying_template_updates_label_and_preserves_source_metadata(): void
    {
        $user = $this->verifiedAdmin();

        $label = VAPLabel::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'name' => 'Etiqueta Aplicada',
            'type' => 'sample',
            'content' => 'Conteúdo anterior',
            'width' => 40,
            'height' => 20,
            'background_color' => '#ffffff',
            'text_color' => '#000000',
            'font_size' => 10,
            'border_width' => 1,
            'border_color' => '#000000',
            'text_alignment' => 'center',
            'template_data' => [
                'template_id' => 1,
                'source_type' => 'sample_entry',
                'source_id' => 88,
            ],
            'is_active' => true,
        ]);

        $template = VAPLabelTemplate::query()->create([
            'name' => 'Novo Modelo',
            'description' => 'Atualiza a etiqueta',
            'category' => 'general',
            'template_data' => [
                'content' => '{name} / {code}',
                'width' => 90,
                'height' => 45,
                'background_color' => '#f3f4f6',
                'text_color' => '#111827',
                'font_size' => 14,
                'border_width' => 2,
                'border_color' => '#111827',
                'text_alignment' => 'left',
            ],
            'is_active' => true,
            'is_featured' => false,
        ]);

        $this->actingAs($user)
            ->post(route('vap_labels.apply-template', $label), [
                'template_id' => $template->id,
            ])
            ->assertRedirect();

        $label->refresh();

        $this->assertSame('{name} / {code}', $label->content);
        $this->assertSame('90.00', $label->width);
        $this->assertSame('45.00', $label->height);
        $this->assertSame($template->id, data_get($label->template_data, 'template_id'));
        $this->assertSame('sample_entry', data_get($label->template_data, 'source_type'));
        $this->assertSame(88, data_get($label->template_data, 'source_id'));
    }

    public function test_updating_label_can_switch_template_and_keep_source_metadata(): void
    {
        $user = $this->verifiedAdmin();

        $label = VAPLabel::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'name' => 'Etiqueta em edição',
            'type' => 'sample',
            'content' => 'Conteúdo anterior',
            'width' => 40,
            'height' => 20,
            'background_color' => '#ffffff',
            'text_color' => '#000000',
            'font_size' => 10,
            'border_width' => 1,
            'border_color' => '#000000',
            'text_alignment' => 'center',
            'template_data' => [
                'template_id' => 1,
                'source_type' => 'sample_entry',
                'source_id' => 99,
            ],
            'is_active' => true,
        ]);

        $template = VAPLabelTemplate::query()->create([
            'name' => 'Template Editado',
            'description' => 'Aplicado durante update',
            'category' => 'general',
            'template_data' => [
                'content' => '{name} atualizado',
                'width' => 65,
                'height' => 28,
                'background_color' => '#f9fafb',
                'text_color' => '#111827',
                'font_size' => 16,
                'border_width' => 2,
                'border_color' => '#111827',
                'text_alignment' => 'left',
            ],
            'is_active' => true,
            'is_featured' => false,
        ]);

        $this->actingAs($user)
            ->put(route('vap_labels.labels.update', $label), [
                'name' => 'Etiqueta em edição',
                'type' => 'sample',
                'content' => '{name} atualizado',
                'width' => 65,
                'height' => 28,
                'background_color' => '#f9fafb',
                'text_color' => '#111827',
                'font_size' => 16,
                'border_width' => 2,
                'border_color' => '#111827',
                'text_alignment' => 'left',
                'lab_id' => null,
                'department_id' => null,
                'logo_path' => null,
                'logo_size' => null,
                'has_qr_code' => false,
                'qr_code_content' => null,
                'qr_code_size' => null,
                'has_barcode' => false,
                'barcode_content' => null,
                'barcode_type' => 'CODE128',
                'barcode_width' => null,
                'barcode_height' => null,
                'is_active' => true,
                'source_type' => 'sample_entry',
                'source_id' => 99,
                'template_id' => $template->id,
            ])
            ->assertRedirect();

        $label->refresh();

        $this->assertSame('{name} atualizado', $label->content);
        $this->assertSame($template->id, data_get($label->template_data, 'template_id'));
        $this->assertSame('sample_entry', data_get($label->template_data, 'source_type'));
        $this->assertSame(99, data_get($label->template_data, 'source_id'));
    }

    public function test_updating_label_template_can_persist_template_data_changes(): void
    {
        $user = $this->verifiedAdmin();

        $template = VAPLabelTemplate::query()->create([
            'name' => 'Template Base',
            'description' => 'Descrição inicial',
            'category' => 'general',
            'template_data' => [
                'type' => 'custom',
                'content' => 'Conteúdo inicial',
                'width' => 50,
                'height' => 25,
                'background_color' => '#ffffff',
                'text_color' => '#000000',
                'font_size' => 12,
                'border_width' => 1,
                'border_color' => '#000000',
                'text_alignment' => 'center',
                'has_qr_code' => false,
                'has_barcode' => false,
            ],
            'is_active' => true,
            'is_featured' => false,
        ]);

        $this->actingAs($user)
            ->put(route('vap_labels.label-templates.update', $template), [
                'name' => 'Template Atualizado',
                'description' => 'Descrição atualizada',
                'category' => 'general',
                'template_data' => [
                    'type' => 'custom',
                    'content' => 'Conteúdo editado',
                    'width' => 80,
                    'height' => 40,
                    'background_color' => '#f9fafb',
                    'text_color' => '#111827',
                    'font_size' => 16,
                    'border_width' => 2,
                    'border_color' => '#111827',
                    'text_alignment' => 'left',
                    'has_qr_code' => true,
                    'has_barcode' => true,
                ],
                'is_active' => true,
                'is_featured' => true,
            ])
            ->assertRedirect();

        $template->refresh();

        $this->assertSame('Template Atualizado', $template->name);
        $this->assertSame('Conteúdo editado', data_get($template->template_data, 'content'));
        $this->assertSame(80, data_get($template->template_data, 'width'));
        $this->assertSame(40, data_get($template->template_data, 'height'));
        $this->assertSame('left', data_get($template->template_data, 'text_alignment'));
        $this->assertTrue((bool) data_get($template->template_data, 'has_qr_code'));
        $this->assertTrue((bool) data_get($template->template_data, 'has_barcode'));
        $this->assertTrue($template->is_featured);
    }

    public function test_updating_label_template_keeps_existing_advanced_template_data_when_not_changed(): void
    {
        $user = $this->verifiedAdmin();

        $template = VAPLabelTemplate::query()->create([
            'name' => 'Template Completo',
            'description' => 'Com metadados avançados',
            'category' => 'general',
            'template_data' => [
                'type' => 'custom',
                'content' => 'Conteúdo inicial',
                'width' => 50,
                'height' => 25,
                'background_color' => '#ffffff',
                'text_color' => '#000000',
                'font_size' => 12,
                'border_width' => 1,
                'border_color' => '#000000',
                'text_alignment' => 'center',
                'has_qr_code' => true,
                'qr_code_content' => 'QR-001',
                'qr_code_size' => 22,
                'has_barcode' => true,
                'barcode_content' => 'BAR-001',
                'barcode_type' => 'CODE128',
                'barcode_width' => 33,
                'barcode_height' => 12,
                'logo_path' => 'logos/template.png',
                'logo_size' => 18,
            ],
            'is_active' => true,
            'is_featured' => false,
        ]);

        $this->actingAs($user)
            ->put(route('vap_labels.label-templates.update', $template), [
                'name' => 'Template Completo',
                'description' => 'Com metadados avançados',
                'category' => 'general',
                'template_data' => [
                    'type' => 'custom',
                    'content' => 'Conteúdo revisto',
                    'width' => 60,
                    'height' => 30,
                    'background_color' => '#ffffff',
                    'text_color' => '#000000',
                    'font_size' => 12,
                    'border_width' => 1,
                    'border_color' => '#000000',
                    'text_alignment' => 'center',
                    'has_qr_code' => true,
                    'qr_code_content' => 'QR-001',
                    'qr_code_size' => 22,
                    'has_barcode' => true,
                    'barcode_content' => 'BAR-001',
                    'barcode_type' => 'CODE128',
                    'barcode_width' => 33,
                    'barcode_height' => 12,
                    'logo_path' => 'logos/template.png',
                    'logo_size' => 18,
                ],
                'is_active' => true,
                'is_featured' => false,
            ])
            ->assertRedirect();

        $template->refresh();

        $this->assertSame('QR-001', data_get($template->template_data, 'qr_code_content'));
        $this->assertSame(22, data_get($template->template_data, 'qr_code_size'));
        $this->assertSame('BAR-001', data_get($template->template_data, 'barcode_content'));
        $this->assertSame('CODE128', data_get($template->template_data, 'barcode_type'));
        $this->assertSame(33, data_get($template->template_data, 'barcode_width'));
        $this->assertSame(12, data_get($template->template_data, 'barcode_height'));
        $this->assertSame('logos/template.png', data_get($template->template_data, 'logo_path'));
        $this->assertSame(18, data_get($template->template_data, 'logo_size'));
    }
}
