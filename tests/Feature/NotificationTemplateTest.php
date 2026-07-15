<?php

namespace Tests\Feature;

use App\Models\NotificationTemplate;
use App\Models\Role;
use App\Models\User;
use App\Support\NotificationTemplateCatalog;
use App\Support\NotificationTemplateService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationTemplateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_catalog_synchronizes_configurable_operational_templates(): void
    {
        $templates = app(NotificationTemplateCatalog::class)->synchronize();

        $this->assertGreaterThanOrEqual(20, $templates->count());
        $this->assertDatabaseHas('notification_templates', [
            'key' => 'commercial.invoice.paid',
            'category' => 'commercial',
        ]);
        $this->assertDatabaseHas('notification_templates', [
            'key' => 'lab.results.approved',
            'category' => 'laboratory',
        ]);
        $this->assertDatabaseHas('notification_templates', [
            'key' => 'documents.controlled_file.shared',
            'category' => 'documents',
        ]);
        $this->assertDatabaseHas('notification_templates', [
            'key' => 'quality.complaint.created',
            'category' => 'quality',
        ]);
        $this->assertDatabaseHas('notification_templates', [
            'key' => 'system.message.received',
            'category' => 'system',
        ]);
    }

    public function test_admin_can_edit_the_copy_and_delivery_channels(): void
    {
        $admin = $this->verifiedAdmin();
        app(NotificationTemplateCatalog::class)->synchronize();
        $template = NotificationTemplate::query()->where('key', 'commercial.invoice.paid')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.notification-templates.update', $template), [
                'title_template' => 'Factura {{document_number}} liquidada',
                'in_app_template' => 'O pagamento de {{document_number}} foi confirmado por {{actor_name}}.',
                'email_subject_template' => 'Pagamento confirmado: {{document_number}}',
                'email_template' => 'Confirmamos a liquidação integral da factura {{document_number}}.',
                'action_label_template' => 'Abrir factura',
                'action_url_template' => '{{document_url}}',
                'channels' => ['database', 'broadcast', 'mail'],
                'priority' => 'high',
                'enabled' => true,
            ])
            ->assertRedirect();

        $template->refresh();
        $this->assertSame('high', $template->priority);
        $this->assertSame(['database', 'broadcast', 'mail'], $template->channels);
        $this->assertSame('Factura {{document_number}} liquidada', $template->title_template);
    }

    public function test_template_service_interpolates_saved_copy(): void
    {
        $admin = $this->verifiedAdmin();
        app(NotificationTemplateCatalog::class)->synchronize();
        NotificationTemplate::query()->where('key', 'commercial.invoice.paid')->update([
            'title_template' => 'Factura {{document_number}} paga',
            'in_app_template' => '{{customer_name}} liquidou {{total}}.',
            'channels' => ['database'],
        ]);

        $payload = $this->actingAs($admin)->app->make(NotificationTemplateService::class)->render('commercial.invoice.paid', [
            'document_number' => 'FT 2026/17',
            'customer_name' => 'Laboratório Central',
            'total' => 'AOA 125 000,00',
            'document_url' => 'https://lims.test/invoices/17/show',
        ]);

        $this->assertSame('Factura FT 2026/17 paga', $payload['title']);
        $this->assertSame('Laboratório Central liquidou AOA 125 000,00.', $payload['message']);
        $this->assertSame(['database'], $payload['channels']);
    }

    public function test_catalog_sync_refreshes_system_metadata_without_overwriting_admin_copy(): void
    {
        app(NotificationTemplateCatalog::class)->synchronize();
        $template = NotificationTemplate::query()->where('key', 'lab.results.inserted')->firstOrFail();
        $template->update([
            'title_template' => 'Texto personalizado {{sample_code}}',
            'channels' => ['database'],
            'priority' => 'urgent',
        ]);

        app(NotificationTemplateCatalog::class)->synchronize();

        $template->refresh();
        $this->assertSame('verify_results', $template->audience_permission);
        $this->assertSame('Texto personalizado {{sample_code}}', $template->title_template);
        $this->assertSame(['database'], $template->channels);
        $this->assertSame('urgent', $template->priority);
    }

    public function test_admin_can_open_the_template_editor(): void
    {
        $this->actingAs($this->verifiedAdmin())
            ->get(route('admin.notification-templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Notifications/Templates')
                ->has('templates'));
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
}
