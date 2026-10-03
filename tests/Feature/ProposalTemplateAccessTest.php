<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VAPProposalTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProposalTemplateAccessTest extends TestCase
{
    use DatabaseTransactions;

    /** @return array<string, array{string, string}> */
    public static function templateEndpoints(): array
    {
        return [
            'list' => ['get', 'index'], 'show' => ['get', 'show'],
            'create' => ['get', 'create'], 'store' => ['post', 'store'],
            'edit' => ['get', 'edit'], 'update' => ['put', 'update'],
            'delete' => ['delete', 'destroy'], 'toggle' => ['put', 'toggle-status'],
            'import' => ['post', 'import'], 'export' => ['get', 'export'],
            'pdf' => ['get', 'pdf'], 'preview' => ['post', 'preview-draft-pdf'],
        ];
    }

    #[DataProvider('templateEndpoints')]
    public function test_staff_without_template_permissions_cannot_access_endpoints(string $method, string $action): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $template = $this->template($user);
        $parameters = in_array($action, ['show', 'edit', 'update', 'destroy', 'toggle-status', 'pdf'], true)
            ? ['proposalTemplate' => $template->id] : [];

        $this->actingAs($user)->{$method}(route('vap-proposals.templates.'.$action, $parameters))
            ->assertForbidden();
        $this->assertModelExists($template);
        $this->assertTrue($template->fresh()->is_active);
    }

    public function test_shared_template_viewer_can_read_without_lab_membership_but_cannot_edit(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::findOrCreate('view_proposal_templates', 'web'));
        $template = $this->template($user);

        $this->actingAs($user)->get(route('vap-proposals.templates.show', $template))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('VAPProposalTemplates/Show')
            ->where('template.id', $template->id)->has('recentProposals', 0));
        $this->put(route('vap-proposals.templates.toggle-status', $template))->assertForbidden();
    }

    public function test_template_editor_can_toggle_without_admin_role(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::findOrCreate('edit_proposal_templates', 'web'));
        $template = $this->template($user);

        $this->actingAs($user)->putJson(route('vap-proposals.templates.toggle-status', $template), ['is_active' => false])
            ->assertOk()->assertJsonPath('is_active', false);
        $this->assertFalse($template->fresh()->is_active);
        $this->postJson(route('vap-proposals.templates.preview-draft-pdf'), ['name' => ['invalid']])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    private function template(User $user): VAPProposalTemplate
    {
        return VAPProposalTemplate::query()->create([
            'name' => 'Template access fixture', 'content' => '<p>Fixture</p>',
            'user_id' => $user->id, 'is_active' => true,
        ]);
    }
}
