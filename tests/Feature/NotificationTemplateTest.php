<?php

namespace Tests\Feature;

use App\Actions\ResetNotificationTemplate;
use App\Actions\SaveNotificationTemplate;
use App\Models\NotificationTemplate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Support\NotificationTemplateCatalog;
use App\Support\NotificationTemplateService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationTemplateTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private VAPLab $peer;

    private User $admin;

    private string $key = 'commercial.invoice.paid';

    protected function setUp(): void
    {
        parent::setUp();
        $this->lab = VAPLab::factory()->create();
        $this->peer = VAPLab::factory()->create();
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->attach($this->admin);
        $this->actingAs($this->admin)->withSession(['active_lab_id' => $this->lab->id]);
    }

    public function test_catalog_provides_shared_presets_without_database_synchronization(): void
    {
        $definitions = app(NotificationTemplateCatalog::class)->definitions();
        $this->assertGreaterThanOrEqual(20, count($definitions));
        foreach (['commercial.invoice.paid' => 'commercial', 'lab.results.approved' => 'laboratory', 'documents.controlled_file.shared' => 'documents', 'quality.complaint.created' => 'quality', 'system.message.received' => 'system'] as $key => $category) {
            $this->assertSame($category, $definitions[$key]['category']);
        }
        $this->assertDatabaseCount('notification_templates', 0);
        $this->assertArrayNotHasKey('notifications:sync-templates', Artisan::all());
    }

    public function test_admin_can_edit_only_the_active_laboratory_copy_and_delivery_channels(): void
    {
        $peer = NotificationTemplate::factory()->create(['lab_id' => $this->peer->id, 'title_template' => 'Private peer copy']);
        $data = $this->copy(['title_template' => 'Factura {{document_number}} liquidada', 'channels' => ['database', 'broadcast', 'mail']]);
        $this->put($this->url(), $data)->assertRedirect(route('admin.notification-templates.index'));
        $template = NotificationTemplate::query()->where('lab_id', $this->lab->id)->firstOrFail();
        $this->assertSame($this->admin->id, $template->updated_by_id);
        $this->assertSame($data['title_template'], $template->title_template);
        $this->assertSame($data['channels'], $template->channels);
        $this->assertSame('Private peer copy', $peer->fresh()->title_template);
        $this->assertSame('Pagamento confirmado', app(NotificationTemplateCatalog::class)->definitions()[$this->key]['title_template']);
        $this->put($this->url(), $this->copy(['title_template' => 'Updated local']))->assertRedirect();
        $this->assertDatabaseCount('notification_templates', 2);
    }

    public function test_template_service_requires_explicit_laboratory_context_for_saved_copy(): void
    {
        NotificationTemplate::factory()->create(['lab_id' => $this->lab->id, 'title_template' => 'Local {{document_number}}', 'in_app_template' => '{{customer_name}} liquidou {{total}}.', 'channels' => ['database']]);
        NotificationTemplate::factory()->create(['lab_id' => $this->peer->id, 'title_template' => 'Peer {{document_number}}']);
        $service = app(NotificationTemplateService::class);
        $context = ['document_number' => 'FT 2026/17', 'customer_name' => 'Cliente', 'total' => 'AOA 125 000,00'];
        $payload = $service->render($this->key, [...$context, 'lab_id' => $this->lab->id]);
        $this->assertSame('Local FT 2026/17', $payload['title']);
        $this->assertSame('Cliente liquidou AOA 125 000,00.', $payload['message']);
        $this->assertSame(['database'], $payload['channels']);
        $this->assertSame('Pagamento confirmado', $service->render($this->key, $context)['title']);
        auth()->forgetGuards();
        $this->assertSame('Peer FT 2026/17', $service->render($this->key, [...$context, 'lab_id' => $this->peer->id])['title']);
        $this->assertNull($service->render($this->key, ['lab_id' => 'invalid']));
    }

    public function test_shared_system_metadata_cannot_be_overwritten_by_an_override(): void
    {
        $before = app(NotificationTemplateCatalog::class)->definitions();
        foreach (['audience_permission' => 'edit_settings', 'lab_id' => $this->peer->id, 'updated_by_id' => $this->admin->id, 'key' => 'system.message.received', 'category' => 'system', 'variables' => ['secret'], 'name' => 'Forged'] as $field => $value) {
            $this->put($this->url(), [...$this->copy(), $field => $value])->assertSessionHasErrors($field);
        }
        $this->assertDatabaseCount('notification_templates', 0);
        $this->assertSame($before, app(NotificationTemplateCatalog::class)->definitions());
    }

    public function test_admin_can_open_a_read_only_catalog_without_creating_global_rows(): void
    {
        NotificationTemplate::factory()->create(['lab_id' => $this->peer->id, 'title_template' => 'Private peer copy']);
        $this->get(route('admin.notification-templates.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Notifications/Templates')->where('laboratory.id', $this->lab->id)->where('canEdit', true)
            ->has('templates', count(app(NotificationTemplateCatalog::class)->definitions()))
            ->where('templates', fn ($templates): bool => collect($templates)->every(fn ($template): bool => ! $template['is_overridden'] && ! isset($template['lab_id']) && $template['title_template'] !== 'Private peer copy')));
        $this->assertDatabaseCount('notification_templates', 1);
    }

    public function test_restoring_a_preset_deletes_only_the_local_override(): void
    {
        $local = NotificationTemplate::factory()->create(['lab_id' => $this->lab->id]);
        $peer = NotificationTemplate::factory()->create(['lab_id' => $this->peer->id]);
        $this->delete(route('admin.notification-templates.destroy', ['key' => $this->key]))->assertRedirect();
        $this->assertModelMissing($local);
        $this->assertModelExists($peer);
        $this->delete(route('admin.notification-templates.destroy', ['key' => $this->key]), ['lab_id' => $this->peer->id])->assertSessionHasErrors('lab_id');
        $this->assertModelExists($peer);
    }

    public function test_disabled_override_does_not_disable_other_labs_or_shared_presets(): void
    {
        NotificationTemplate::factory()->create(['lab_id' => $this->lab->id, 'enabled' => false]);
        $service = app(NotificationTemplateService::class);
        $this->assertNull($service->render($this->key, ['lab_id' => $this->lab->id]));
        $this->assertNotNull($service->render($this->key, ['lab_id' => $this->peer->id]));
        $this->assertNotNull($service->render($this->key));
    }

    public function test_unassociated_administrator_and_inactive_members_cannot_access_templates(): void
    {
        DB::table('lab_user')->where('user_id', $this->admin->id)->delete();
        $this->get(route('admin.notification-templates.index'))->assertForbidden();
        $this->put($this->url(), $this->copy())->assertForbidden();
        $this->delete(route('admin.notification-templates.destroy', ['key' => $this->key]))->assertForbidden();
        $this->attach($this->admin);
        $this->admin->update(['is_active' => false]);
        $this->get(route('admin.notification-templates.index'))->assertUnauthorized();
        $this->assertDatabaseCount('notification_templates', 0);
    }

    public function test_view_permission_does_not_grant_mutation_permission(): void
    {
        $reader = User::factory()->create(['is_active' => true]);
        $this->attach($reader);
        $reader->givePermissionTo(Permission::findOrCreate('view_settings', 'web'));
        $this->actingAs($reader)->get(route('admin.notification-templates.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canEdit', false));
        $this->put($this->url(), $this->copy())->assertForbidden();
        $this->delete(route('admin.notification-templates.destroy', ['key' => $this->key]))->assertForbidden();
        $this->assertDatabaseCount('notification_templates', 0);
    }

    public function test_actions_recheck_membership_even_after_http_authorization(): void
    {
        $local = NotificationTemplate::factory()->create(['lab_id' => $this->lab->id]);
        DB::table('lab_user')->where('user_id', $this->admin->id)->where('lab_id', $this->lab->id)->delete();
        foreach ([fn () => app(SaveNotificationTemplate::class)->handle($this->admin->id, $this->lab->id, $this->key, $this->copy()), fn () => app(ResetNotificationTemplate::class)->handle($this->admin->id, $this->lab->id, $this->key)] as $mutate) {
            try {
                $mutate();
                $this->fail('Removed membership allowed an override mutation.');
            } catch (AuthorizationException) {
                $this->assertModelExists($local);
            }
        }
    }

    public function test_invalid_channels_destinations_and_unknown_presets_leave_no_override(): void
    {
        foreach (['channels' => ['sms'], 'priority' => 'critical', 'title_template' => '', 'action_url_template' => 'javascript:alert(1)'] as $field => $value) {
            $this->put($this->url(), $this->copy([$field => $value]))->assertSessionHasErrors($field === 'channels' ? 'channels.0' : $field);
        }
        $this->put($this->url('unknown.preset'), $this->copy())->assertNotFound();
        $this->delete(route('admin.notification-templates.destroy', ['key' => 'unknown.preset']))->assertNotFound();
        $this->put($this->url('123'), $this->copy())->assertNotFound();
        $this->assertDatabaseCount('notification_templates', 0);
    }

    public function test_empty_forged_identity_fields_never_replace_server_derived_owner_or_key(): void
    {
        $this->put($this->url(), [...$this->copy(), 'lab_id' => null, 'key' => null, 'updated_by_id' => null])->assertRedirect();
        $template = NotificationTemplate::query()->firstOrFail();
        $this->assertSame($this->lab->id, $template->lab_id);
        $this->assertSame($this->key, $template->key);
        $this->assertSame($this->admin->id, $template->updated_by_id);
    }

    public function test_unknown_placeholders_are_rejected_and_disabled_labs_do_not_render_overrides(): void
    {
        $this->put($this->url(), $this->copy(['title_template' => '{{secret_customer_field}}']))->assertSessionHasErrors('title_template');
        $this->assertDatabaseCount('notification_templates', 0);
        $this->assertNull(app(NotificationTemplateService::class)->render($this->key, ['lab_id' => PHP_INT_MAX]));
        $this->lab->delete();
        $this->assertNull(app(NotificationTemplateService::class)->render($this->key, ['lab_id' => $this->lab->id]));
    }

    public function test_override_owner_and_key_are_immutable_and_required_by_postgresql(): void
    {
        $template = NotificationTemplate::factory()->create(['lab_id' => $this->lab->id]);
        foreach (['lab_id' => $this->peer->id, 'key' => 'lab.results.approved'] as $field => $value) {
            try {
                $template->update([$field => $value]);
                $this->fail('Override identity changed.');
            } catch (\LogicException) {
                $template->refresh();
                $this->assertSame($this->lab->id, $template->lab_id);
                $this->assertSame($this->key, $template->key);
            }
        }
        foreach ([
            fn () => NotificationTemplate::factory()->create(['lab_id' => null]),
            fn () => NotificationTemplate::factory()->create(['lab_id' => $this->lab->id]),
        ] as $invalid) {
            try {
                DB::transaction($invalid);
                $this->fail('Invalid override accepted.');
            } catch (QueryException) {
                $this->assertDatabaseCount('notification_templates', 1);
            }
        }
    }

    public function test_override_migration_is_reversible_only_without_retained_rows(): void
    {
        $migration = require database_path('migrations/2026_10_01_141902_make_notification_templates_laboratory_overrides.php');
        $migration->down();
        $migration->up();
        $template = NotificationTemplate::factory()->create(['lab_id' => $this->lab->id]);
        try {
            $migration->down();
            $this->fail('Retained overrides became globally editable.');
        } catch (\RuntimeException) {
            $this->assertModelExists($template);
        }
    }

    public function test_override_migration_refuses_to_guess_an_owner_for_global_customizations(): void
    {
        $migration = require database_path('migrations/2026_10_01_141902_make_notification_templates_laboratory_overrides.php');
        $migration->down();
        DB::table('notification_templates')->insert([
            ...app(NotificationTemplateCatalog::class)->definitions()[$this->key],
            'key' => $this->key, 'title_template' => 'Retained custom copy',
            'channels' => json_encode(['database']), 'variables' => json_encode([]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        try {
            $migration->up();
            $this->fail('Global customizations were assigned without approval.');
        } catch (\RuntimeException) {
            $this->assertDatabaseHas('notification_templates', ['title_template' => 'Retained custom copy']);
        }
        DB::table('notification_templates')->delete();
        $migration->up();
    }

    /** @param array<string, mixed> $changes @return array<string, mixed> */
    private function copy(array $changes = []): array
    {
        return [...collect(app(NotificationTemplateCatalog::class)->definitions()[$this->key])->only(NotificationTemplate::EDITABLE_FIELDS)->all(), ...$changes];
    }

    private function attach(User $user): void
    {
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $user->id, 'can_view_network' => false, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function url(?string $key = null): string
    {
        return route('admin.notification-templates.update', ['key' => $key ?? $this->key]);
    }
}
