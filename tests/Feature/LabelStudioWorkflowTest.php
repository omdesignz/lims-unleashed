<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\ItemCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPLabel;
use App\Models\VAPLabelTemplate;
use App\Models\VAPSampleEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LabelStudioWorkflowTest extends TestCase
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

    /** @param array<string, mixed> $attributes */
    private function createLabel(array $attributes): VAPLabel
    {
        return VAPLabel::query()->create(array_merge(['lab_id' => $this->lab->id], $attributes));
    }

    /** @param array<string, mixed> $attributes */
    private function createTemplate(array $attributes): VAPLabelTemplate
    {
        return VAPLabelTemplate::query()->create(array_merge(['lab_id' => $this->lab->id], $attributes));
    }

    public function test_admin_can_generate_label_from_inventory_source(): void
    {
        $user = $this->verifiedAdmin();
        $category = ItemCategory::query()->create(['name' => 'Label studio equipment '.fake()->uuid()]);
        $item = InventoryItem::query()->create([
            'lab_id' => $this->lab->id,
            'name' => 'Label studio instrument',
            'code' => fake()->unique()->bothify('EQ-#######'),
            'category_id' => $category->id,
        ]);
        $warehouse = InventoryItemWarehouse::query()->forceCreate([
            'name' => 'Label studio warehouse',
            'lab_id' => $this->lab->id,
        ]);
        Inventory::query()->create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'qty_available' => 1]);

        $template = $this->createTemplate([
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

        $peerLab = VAPLab::factory()->create();
        $peerWarehouse = InventoryItemWarehouse::query()->forceCreate([
            'name' => 'Other laboratory warehouse',
            'lab_id' => $peerLab->id,
        ]);
        $peerItem = InventoryItem::query()->create([
            'lab_id' => $peerLab->id,
            'name' => 'Private peer instrument',
            'code' => fake()->unique()->bothify('EQ-#######'),
            'category_id' => $category->id,
        ]);
        Inventory::query()->create(['item_id' => $peerItem->id, 'warehouse_id' => $peerWarehouse->id, 'qty_available' => 1]);

        $this->actingAs($user)
            ->post(route('vap_labels.label-generation.from-source'), [
                'name' => 'Foreign inventory label',
                'template_id' => $template->id,
                'source_type' => 'equipment',
                'source_id' => $peerItem->id,
            ])
            ->assertNotFound();
        $this->assertDatabaseMissing('labels', ['name' => 'Foreign inventory label']);
    }

    public function test_label_show_route_can_return_json_payload_for_studio_consumers(): void
    {
        $user = $this->verifiedAdmin();

        $label = $this->createLabel([
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

        $label = $this->createLabel([
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

        $this->createTemplate([
            'name' => 'Modelo Ativo',
            'description' => 'Disponível no ecrã de detalhe',
            'category' => 'general',
            'template_data' => ['content' => '{name}'],
            'is_active' => true,
            'is_featured' => true,
        ]);

        $this->createTemplate([
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
                ->has('pdfRenderer.chrome.available')
                ->has('pdfRenderer.fallback.available')
                ->has('printSettings')
            );

        $templateNames = collect($response->inertiaProps('templates'))
            ->pluck('name');

        $this->assertTrue($templateNames->contains('Modelo Ativo'));
        $this->assertFalse($templateNames->contains('Modelo Inativo'));
    }

    public function test_label_create_route_exposes_selected_template_from_query_string(): void
    {
        $user = $this->verifiedAdmin();

        $template = $this->createTemplate([
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

    public function test_label_edit_route_reuses_polished_studio_shell(): void
    {
        $user = $this->verifiedAdmin();

        $template = $this->createTemplate([
            'name' => 'Modelo de edição',
            'description' => 'Disponível no ecrã unificado',
            'category' => 'general',
            'template_data' => ['content' => '{name}'],
            'is_active' => true,
            'is_featured' => false,
        ]);

        $label = $this->createLabel([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'name' => 'Etiqueta editável',
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
            'template_data' => ['template_id' => $template->id],
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('vap_labels.labels.edit', $label))
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPLabels/Create')
                ->where('label.id', $label->id)
                ->where('selectedTemplateId', $template->id)
                ->has('sourceOptions.samples')
                ->has('supportedPlaceholders')
            );
    }

    public function test_label_index_keeps_status_filter_applied_when_search_matches_name(): void
    {
        $user = $this->verifiedAdmin();

        $this->createLabel([
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

        $this->createLabel([
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

        $this->createTemplate([
            'name' => 'Modelo Filtro Ativo',
            'description' => 'Ativo',
            'category' => 'general',
            'template_data' => ['content' => '{name}'],
            'is_active' => true,
            'is_featured' => false,
        ]);

        $this->createTemplate([
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

        $label = $this->createLabel([
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

        $label = $this->createLabel([
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

        $template = $this->createTemplate([
            'name' => 'Novo Modelo',
            'description' => 'Atualiza a etiqueta',
            'category' => 'general',
            'template_data' => [
                'content' => '{name} / {code}',
                'lab_id' => 999999,
                'tenant_id' => 999999,
                'user_id' => 999999,
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
        $this->assertSame($this->lab->id, $label->lab_id);
        $this->assertSame($user->id, $label->user_id);
        $this->assertSame($user->tenant_id, $label->tenant_id);
        $this->assertArrayNotHasKey('lab_id', $label->template_data);
    }

    public function test_updating_label_can_switch_template_and_keep_source_metadata(): void
    {
        $user = $this->verifiedAdmin();
        $sample = VAPSampleEntry::factory()->create(['lab_id' => $this->lab->id, 'name' => 'Amostra em edição']);

        $label = $this->createLabel([
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
                'source_id' => $sample->id,
            ],
            'is_active' => true,
        ]);

        $template = $this->createTemplate([
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
                'source_id' => $sample->id,
                'template_id' => $template->id,
            ])
            ->assertRedirect();

        $label->refresh();

        $this->assertSame('Amostra em edição atualizado', $label->content);
        $this->assertSame($template->id, data_get($label->template_data, 'template_id'));
        $this->assertSame('sample_entry', data_get($label->template_data, 'source_type'));
        $this->assertSame($sample->id, data_get($label->template_data, 'source_id'));
    }

    public function test_updating_label_template_can_persist_template_data_changes(): void
    {
        $user = $this->verifiedAdmin();

        $template = $this->createTemplate([
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

        $template = $this->createTemplate([
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

    public function test_labels_are_isolated_by_laboratory_across_index_and_document_routes(): void
    {
        $user = $this->verifiedAdmin();
        $peerLab = VAPLab::factory()->create();
        $attributes = [
            'user_id' => $user->id,
            'type' => 'sample',
            'content' => 'Amostra controlada',
            'width' => 60,
            'height' => 30,
            'background_color' => '#ffffff',
            'text_color' => '#111827',
            'font_size' => 12,
            'border_width' => 1,
            'border_color' => '#111827',
            'text_alignment' => 'center',
            'is_active' => true,
        ];

        $this->createLabel(array_merge($attributes, [
            'tenant_id' => $user->tenant_id,
            'name' => 'Laboratory scope marker local',
        ]));
        $foreignLabel = $this->createLabel(array_merge($attributes, [
            'tenant_id' => $user->tenant_id,
            'lab_id' => $peerLab->id,
            'name' => 'Laboratory scope marker foreign',
        ]));

        $this->actingAs($user)
            ->get(route('vap_labels.labels.index', ['search' => 'Laboratory scope marker']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('labels.data', 1)
                ->where('labels.data.0.name', 'Laboratory scope marker local'));

        $this->actingAs($user)
            ->get(route('vap_labels.labels.show', $foreignLabel))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('vap_labels.preview-pdf', $foreignLabel))
            ->assertNotFound();

        $this->get(route('vap_labels.labels.edit', $foreignLabel))->assertNotFound();
        $this->put(route('vap_labels.labels.update', $foreignLabel), [])->assertNotFound();
        $this->delete(route('vap_labels.labels.destroy', $foreignLabel))->assertNotFound();
        $this->post(route('vap_labels.duplicate', $foreignLabel))->assertNotFound();
        $this->post(route('vap_labels.toggle-status', $foreignLabel))->assertNotFound();
        $this->post(route('vap_labels.apply-template', $foreignLabel), [])->assertNotFound();
        $this->post(route('vap_labels.generate-pdf', $foreignLabel), [])->assertNotFound();
        $this->post(route('vap_labels.generate-batch-pdf', $foreignLabel), [])->assertNotFound();
        $this->assertModelExists($foreignLabel);
    }

    public function test_custom_templates_follow_active_lab_while_system_presets_remain_shared_and_read_only(): void
    {
        $user = $this->verifiedAdmin();
        $firstLab = $this->lab;
        $secondLab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $secondLab->id, 'user_id' => $user->id]);
        $templateData = ['content' => '{name}', 'width' => 50, 'height' => 25];

        $local = $this->createTemplate(['name' => 'Modelo local', 'category' => 'samples', 'template_data' => $templateData]);
        $peer = $this->createTemplate(['name' => 'Modelo de outra bancada', 'category' => 'samples', 'template_data' => $templateData, 'lab_id' => $secondLab->id]);
        $system = VAPLabelTemplate::query()->forceCreate([
            'name' => 'Modelo de sistema', 'category' => 'samples', 'template_data' => $templateData,
            'is_system' => true, 'lab_id' => null,
        ]);
        $inactive = $this->createTemplate([
            'name' => 'Modelo desactivado', 'category' => 'samples',
            'template_data' => $templateData, 'is_active' => false,
        ]);
        $label = $this->createLabel([
            'name' => 'Etiqueta local', 'content' => 'Antes', 'width' => 50, 'height' => 25,
        ]);

        $this->actingAs($user)->withSession(['active_lab_id' => $firstLab->id]);
        $firstPage = $this->get(route('vap_labels.label-templates.index'))->assertOk();
        $this->assertEqualsCanonicalizing([$local->id, $system->id, $inactive->id], collect($firstPage->inertiaProps('templates.data'))->pluck('id')->all());
        $this->post(route('vap_labels.apply-template', $label), ['template_id' => $peer->id])->assertNotFound();
        $this->post(route('vap_labels.apply-template', $label), ['template_id' => $inactive->id])->assertNotFound();
        $this->post(route('vap_labels.apply-template', $label), ['template_id' => $system->id])->assertRedirect();
        $this->assertSame($system->id, data_get($label->fresh()->template_data, 'template_id'));
        $this->get(route('vap_labels.label-templates.edit', $peer))->assertNotFound();
        $this->get(route('vap_labels.label-templates.edit', $system))->assertNotFound();
        $this->post(route('vap_labels.templates.toggle-status', $system))->assertNotFound();
        $this->post(route('vap_labels.templates.toggle-featured', $system))->assertNotFound();
        $this->put(route('vap_labels.label-templates.update', $peer), [])->assertNotFound();
        $this->delete(route('vap_labels.label-templates.destroy', $system))->assertNotFound();

        $secondPage = $this->withSession(['active_lab_id' => $secondLab->id])
            ->get(route('vap_labels.label-templates.index'))->assertOk();
        $this->assertEqualsCanonicalizing([$peer->id, $system->id], collect($secondPage->inertiaProps('templates.data'))->pluck('id')->all());
    }

    public function test_custom_template_requests_reject_ownership_fields_hidden_inside_presentation_data(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->post(route('vap_labels.label-templates.store'), [
                'name' => 'Modelo adulterado',
                'category' => 'samples',
                'template_data' => [
                    'content' => '{name}',
                    'width' => 50,
                    'height' => 25,
                    'user_id' => $user->id,
                    'lab_id' => $this->lab->id,
                ],
            ])
            ->assertSessionHasErrors('template_data');

        $this->assertDatabaseMissing('label_templates', ['name' => 'Modelo adulterado']);
    }

    public function test_label_sources_require_local_sample_ownership_and_ignore_forged_lab_selection(): void
    {
        $user = $this->verifiedAdmin();
        $peerLab = VAPLab::factory()->create();
        $localSample = VAPSampleEntry::factory()->create(['lab_id' => $this->lab->id, 'name' => 'Amostra local']);
        $peerSample = VAPSampleEntry::factory()->create(['lab_id' => $peerLab->id, 'name' => 'Amostra privada']);
        $template = $this->createTemplate([
            'name' => 'Modelo de amostra', 'category' => 'samples',
            'template_data' => ['content' => '{name} | {code}', 'width' => 50, 'height' => 25],
        ]);

        $response = $this->actingAs($user)
            ->get(route('vap_labels.labels.create', ['source_type' => 'sample_entry', 'source_id' => $peerSample->id]))
            ->assertOk();
        $this->assertNull($response->inertiaProps('sourcePreview'));
        $this->assertSame([$localSample->id], collect($response->inertiaProps('sourceOptions.samples'))->pluck('id')->all());
        $this->actingAs($user)
            ->get(route('vap_labels.labels.create', ['source_type' => 'unknown', 'source_id' => $localSample->id]))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('vap_labels.labels.store'), [
                'name' => 'Etiqueta de origem privada',
                'type' => 'sample',
                'content' => '{name}',
                'width' => 50,
                'height' => 25,
                'background_color' => '#ffffff',
                'text_color' => '#000000',
                'font_size' => 12,
                'border_width' => 1,
                'border_color' => '#000000',
                'text_alignment' => 'center',
                'source_type' => 'sample_entry',
                'source_id' => $peerSample->id,
            ])
            ->assertNotFound();
        $this->assertDatabaseMissing('labels', ['name' => 'Etiqueta de origem privada']);

        $payload = [
            'name' => 'Etiqueta da amostra', 'template_id' => $template->id,
            'source_type' => 'sample_entry', 'source_id' => $peerSample->id,
        ];
        $this->post(route('vap_labels.label-generation.from-source'), $payload)->assertNotFound();
        $this->post(route('vap_labels.label-generation.from-source'), array_merge($payload, [
            'source_id' => $localSample->id, 'lab_id' => $peerLab->id,
        ]))->assertSessionHasErrors('lab_id');
        $this->post(route('vap_labels.label-generation.from-source'), array_merge($payload, [
            'source_id' => $localSample->id,
        ]))->assertRedirect();
        $this->assertSame($this->lab->id, VAPLabel::query()->latest('id')->firstOrFail()->lab_id);
    }

    public function test_label_preview_pdf_uses_production_renderer_contract(): void
    {
        config()->set('laravel-pdf.chrome.chrome_binary', '/missing/chrome');

        $user = $this->verifiedAdmin();
        $label = $this->createLabel([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'name' => 'Etiqueta PDF Preview',
            'type' => 'sample',
            'content' => 'Amostra PDF',
            'width' => 60,
            'height' => 30,
            'background_color' => '#ffffff',
            'text_color' => '#111827',
            'font_size' => 12,
            'border_width' => 1,
            'border_color' => '#111827',
            'text_alignment' => 'center',
            'has_qr_code' => true,
            'qr_code_content' => 'sample-preview-001',
            'has_barcode' => true,
            'barcode_content' => 'sample-preview-001',
            'barcode_type' => 'CODE128',
            'barcode_width' => 32,
            'barcode_height' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('vap_labels.preview-pdf', $label));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Label-Pdf-Renderer', 'mpdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());

        foreach (['pdf.blade.php', 'preview.blade.php', 'generate.blade.php', 'batch.blade.php'] as $view) {
            $source = file_get_contents(resource_path('views/PDFs/labels/'.$view));

            $this->assertIsString($source);
            $this->assertStringNotContainsString('would go here', $source);
            $this->assertStringNotContainsString('>LOGO<', $source);
        }

        $this->assertStringContainsString(
            'qr_code_image',
            (string) file_get_contents(resource_path('views/PDFs/labels/batch.blade.php'))
        );
    }

    public function test_label_generation_pdf_embeds_real_qr_and_barcode_payloads(): void
    {
        $user = $this->verifiedAdmin();
        $label = $this->createLabel([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'name' => 'Etiqueta PDF Produção',
            'type' => 'sample',
            'content' => '{name}',
            'width' => 60,
            'height' => 30,
            'background_color' => '#f8fafc',
            'text_color' => '#0f172a',
            'font_size' => 12,
            'border_width' => 1,
            'border_color' => '#0f172a',
            'text_alignment' => 'center',
            'has_qr_code' => true,
            'qr_code_size' => 12,
            'has_barcode' => true,
            'barcode_type' => 'CODE128',
            'barcode_width' => 32,
            'barcode_height' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('vap_labels.generate-pdf', $label), [
                'data' => [
                    [
                        'content' => 'Amostra 001',
                        'qr_content' => 'sample:001',
                        'barcode_content' => 'SAMPLE001',
                    ],
                ],
                'include_cutouts' => true,
                'labels_per_page' => 1,
                'margin' => 5,
                'page_size' => 'Legal',
                'orientation' => 'landscape',
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Label-Pdf-Renderer');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());

        $label->refresh();

        $this->assertSame('Legal', data_get($label->template_data, 'print_settings.page_size'));
        $this->assertSame('landscape', data_get($label->template_data, 'print_settings.orientation'));
        $this->assertSame(5, data_get($label->template_data, 'print_settings.margin'));
    }

    public function test_label_batch_pdf_uses_same_renderer_contract(): void
    {
        config()->set('laravel-pdf.chrome.chrome_binary', '/missing/chrome');

        $user = $this->verifiedAdmin();
        $label = $this->createLabel([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'name' => 'Etiqueta PDF Lote',
            'type' => 'custom',
            'content' => 'Lote',
            'width' => 50,
            'height' => 25,
            'background_color' => '#ffffff',
            'text_color' => '#000000',
            'font_size' => 11,
            'border_width' => 1,
            'border_color' => '#000000',
            'text_alignment' => 'center',
            'has_qr_code' => true,
            'qr_code_size' => 10,
            'has_barcode' => true,
            'barcode_type' => 'CODE128',
            'barcode_width' => 28,
            'barcode_height' => 8,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('vap_labels.generate-batch-pdf', $label), [
                'data' => [
                    ['content' => 'Etiqueta 1', 'qr_content' => 'batch:1', 'barcode_content' => 'BATCH001'],
                    ['content' => 'Etiqueta 2', 'qr_content' => 'batch:2', 'barcode_content' => 'BATCH002'],
                ],
                'columns' => 2,
                'rows' => 4,
                'spacing' => 4,
                'include_cutouts' => true,
                'page_size' => 'A3',
                'orientation' => 'portrait',
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Label-Pdf-Renderer', 'mpdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());

        $label->refresh();

        $this->assertSame('A3', data_get($label->template_data, 'print_settings.page_size'));
        $this->assertSame(2, data_get($label->template_data, 'print_settings.columns'));
        $this->assertSame(4, data_get($label->template_data, 'print_settings.rows'));
        $this->assertSame(4, data_get($label->template_data, 'print_settings.spacing'));
    }
}
