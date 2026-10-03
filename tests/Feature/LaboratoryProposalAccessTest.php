<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\Warehouse;
use App\Support\ExportHubQuery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LaboratoryProposalAccessTest extends TestCase
{
    use DatabaseTransactions;

    private function operator(?VAPLab $lab, array $permissions = ['view_proposals', 'add_proposals', 'edit_proposals', 'delete_proposals', 'restore_proposals', 'view_samples', 'add_samples']): User
    {
        $user = User::factory()->create(['is_active' => true]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        if ($lab) {
            DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        }

        return $user;
    }

    private function proposal(?VAPLab $lab, User $user, ?Customer $customer = null): VAPProposal
    {
        $customer ??= Customer::create(['name' => fake()->company()]);
        $warehouse = Warehouse::create(['name' => fake()->unique()->company(), 'customer_id' => $customer->id]);
        $template = VAPProposalTemplate::create(['name' => 'Access fixture', 'content' => '<p>Fixture</p>', 'user_id' => $user->id, 'is_active' => true]);
        $proposal = new VAPProposal([
            'proposal_year' => now()->year, 'proposal_no' => 'TEST-'.Str::uuid(),
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'department_id' => Department::factory()->create()->id, 'template_id' => $template->id,
            'user_id' => $user->id, 'status' => 'PENDING', 'details' => ['fixture' => true],
            'service_location' => 'Shared search term', 'unique_hash' => (string) Str::uuid(),
        ]);
        $proposal->lab_id = $lab?->id;
        $proposal->save();

        return $proposal;
    }

    public function test_both_lookups_only_return_active_lab_proposals(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $local = $this->proposal($lab, $user);
        $this->proposal(VAPLab::factory()->create(), $user);
        $this->proposal(null, $user);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        foreach (['proposals.getProposal', 'vap-proposals.options.proposals'] as $route) {
            $this->getJson(route($route, ['q' => 'Shared search']))
                ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $local->id);
        }
    }

    public function test_older_authoring_links_redirect_to_canonical_forms_within_the_active_lab(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $proposal = $this->proposal($lab, $user);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        $this->get(route('proposals.create'))->assertRedirect(route('vap-proposals.create'));
        $this->get(route('proposals.edit', $proposal->id))->assertRedirect(route('vap-proposals.edit', $proposal->id));
        $this->get(route('vap-proposals.create'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('VAPProposals/Create'));
        $this->get(route('vap-proposals.edit', $proposal->id))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('VAPProposals/Edit'));
    }

    public function test_older_authoring_redirects_require_the_existing_write_permissions(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['view_proposals']);
        $proposal = $this->proposal($lab, $user);
        $this->actingAs($user);

        $this->get(route('proposals.create'))->assertForbidden();
        $this->get(route('proposals.edit', $proposal->id))->assertForbidden();
    }

    public function test_retired_authoring_writes_cannot_replace_accepted_proposal_or_consent(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $proposal = $this->proposal($lab, $user);
        $proposal->update(['status' => 'ACCEPTED']);
        $agreement = $proposal->complianceAgreement()->create(['confidentiality' => true, 'impartiality' => false, 'nondisclosure' => true, 'acknowledged_at' => now()]);
        $item = $proposal->items()->create(['item_description' => 'Retained evidence', 'qty' => 1, 'unit_price' => 25, 'total' => 25,
            'unit_id' => Unit::create(['code' => 'U-'.Str::uuid(), 'description' => 'Retained evidence unit'])->id]);
        $before = $proposal->fresh()->getRawOriginal();
        $agreementBefore = $agreement->fresh()->getRawOriginal();
        $itemBefore = $item->fresh()->getRawOriginal();
        $count = VAPProposal::count();
        $activityCount = Activity::count();
        $this->actingAs($user);

        $this->assertFalse(Route::has('proposals.store'));
        $this->assertFalse(Route::has('proposals.update'));
        $payload = ['status' => 'PENDING', 'unique_hash' => 'forged-token', 'confidentiality' => false, 'items' => []];
        $this->postJson('/proposals', $payload)->assertStatus(405);
        $this->putJson('/proposals/'.$proposal->id, $payload)->assertNotFound();

        $this->assertSame($before, $proposal->fresh()->getRawOriginal());
        $this->assertSame($agreementBefore, $agreement->fresh()->getRawOriginal());
        $this->assertSame($itemBefore, $item->fresh()->getRawOriginal());
        $this->assertSame($count, VAPProposal::count());
        $this->assertSame($activityCount, Activity::count());
        $this->get(route('proposals.index'))->assertOk();
        $this->get(route('proposals.show', $proposal->id))->assertOk();
        $this->get(route('proposals.edit', $proposal->id))->assertRedirect(route('vap-proposals.edit', $proposal->id));
        $this->get(route('vap-proposals.edit', $proposal->id))->assertRedirect(route('vap-proposals.show', $proposal->id));
    }

    public function test_lists_and_statistics_exclude_peer_and_unassigned_proposals(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $local = $this->proposal($lab, $user);
        $this->proposal(VAPLab::factory()->create(), $user);
        $this->proposal(null, $user);
        $this->actingAs($user)->get(route('vap-proposals.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('VAPProposals/Index')
                ->has('proposals.data', 1)->where('proposals.data.0.id', $local->id)->where('stats.total', 1));
    }

    public function test_admin_cannot_read_modify_send_or_download_peer_proposal(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $peer = $this->proposal(VAPLab::factory()->create(), $user);
        $this->actingAs($user);
        foreach (['vap-proposals.show', 'vap-proposals.edit', 'vap-proposals.download.pdf', 'proposals.show', 'proposals.edit'] as $route) {
            $this->get(route($route, $peer->id))->assertNotFound();
        }
        $this->putJson(route('vap-proposals.update', $peer->id), [])->assertNotFound();
        $this->deleteJson(route('vap-proposals.destroy', $peer->id))->assertNotFound();
        $this->postJson(route('vap-proposals.send', $peer->id), [])->assertNotFound();
        $this->assertSame('PENDING', $peer->fresh()->status);
    }

    public function test_membership_and_permission_are_both_required(): void
    {
        $user = $this->operator(null);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($user)->getJson(route('vap-proposals.options.proposals'))->assertForbidden();
        $this->actingAs($this->operator(VAPLab::factory()->create(), []))
            ->getJson(route('vap-proposals.options.proposals'))->assertForbidden();
    }

    public function test_switching_labs_changes_visible_proposals_without_splitting_customers(): void
    {
        $first = VAPLab::factory()->create();
        $second = VAPLab::factory()->create();
        $user = $this->operator($first);
        DB::table('lab_user')->insert(['lab_id' => $second->id, 'user_id' => $user->id]);
        $customer = Customer::create(['name' => 'Shared directory customer']);
        $one = $this->proposal($first, $user, $customer);
        $two = $this->proposal($second, $user, $customer);
        $this->actingAs($user);
        foreach ([[$first, $one], [$second, $two]] as [$lab, $proposal]) {
            $this->withSession(['active_lab_id' => $lab->id])->getJson(route('vap-proposals.options.proposals'))
                ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $proposal->id);
            $this->getJson(route('customers.getCustomer', ['q' => 'Shared directory customer']))
                ->assertOk()->assertJsonFragment(['id' => $customer->id]);
        }
    }

    public function test_public_token_link_remains_available_but_numeric_id_is_not_a_token(): void
    {
        $lab = VAPLab::factory()->create();
        $proposal = $this->proposal($lab, $this->operator($lab));
        $this->get(route('vap-proposals.public.thankyou', $proposal->unique_hash))->assertOk();
        $this->get(route('vap-proposals.public.thankyou', $proposal->id))->assertNotFound();
    }

    public function test_ownership_is_not_mass_assignable_or_mutable(): void
    {
        $lab = VAPLab::factory()->create();
        $proposal = $this->proposal($lab, $this->operator($lab));
        $this->assertFalse($proposal->isFillable('lab_id'));
        $proposal->lab_id = VAPLab::factory()->create()->id;
        $this->expectException(\LogicException::class);
        $proposal->save();
    }

    public function test_sample_intake_rejects_peer_proposal_before_writing(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $peer = $this->proposal(VAPLab::factory()->create(), $user);
        $this->actingAs($user)->postJson(route('vap_samples.samples.store'), [
            'name' => 'Test sample', 'sample_type' => 'AGUA', 'lab_id' => $lab->id,
            'proposal_id' => $peer->id, 'customer_id' => $peer->customer_id,
            'warehouse_id' => $peer->warehouse_id, 'department_id' => $peer->department_id,
        ])->assertUnprocessable()->assertJsonValidationErrors('proposal_id');
    }

    public function test_request_context_does_not_leak_to_console_queries(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $local = $this->proposal($lab, $user);
        $peer = $this->proposal(VAPLab::factory()->create(), $user);
        $this->actingAs($user)->getJson(route('vap-proposals.options.proposals'))->assertOk()->assertJsonCount(1);
        $this->assertFalse(request()->attributes->has('proposal_laboratory_id'));
        $this->assertSame(2, VAPProposal::whereKey([$local->id, $peer->id])->count());
    }

    public function test_creation_uses_active_lab_not_client_supplied_owner(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $source = $this->proposal($peer, $user);
        $unit = Unit::create(['code' => 'T-'.Str::random(8), 'description' => 'Test unit']);
        $this->actingAs($user)->post(route('vap-proposals.store'), [
            'lab_id' => $peer->id, 'customer_id' => $source->customer_id,
            'warehouse_id' => $source->warehouse_id, 'department_id' => $source->department_id,
            'template_id' => $source->template_id, 'service_location' => 'Created for active lab',
            'tolerance_days' => 7, 'sub_total' => 10, 'total' => 10,
            'items' => [['item_description' => 'Analysis', 'unit_id' => $unit->id, 'qty' => 1, 'unit_price' => 10]],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $created = VAPProposal::where('service_location', 'Created for active lab')->firstOrFail();
        $this->assertSame($lab->id, $created->lab_id);
        $this->assertSame($source->customer_id, $created->customer_id);
        $this->assertSame(1, $created->items()->count());
        $this->get(route('vap-proposals.show', $created->id))->assertOk();
    }

    public function test_agreement_lookup_is_private_and_manual_updates_are_retired(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $local = $this->proposal($lab, $user);
        $peer = $this->proposal(VAPLab::factory()->create(), $user);
        $localAgreement = $local->complianceAgreement()->create(['confidentiality' => false, 'impartiality' => false, 'nondisclosure' => false]);
        $peerAgreement = $peer->complianceAgreement()->create(['confidentiality' => false, 'impartiality' => false, 'nondisclosure' => false]);
        $this->actingAs($user)->getJson(route('proposalcomplianceagreements.getProposalComplianceAgreement', ['q' => '']))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $localAgreement->id);
        $this->putJson('/proposalcomplianceagreements/'.$localAgreement->id, [
            'proposal_id' => $peer->id, 'confidentiality' => true, 'impartiality' => true,
            'nondisclosure' => true, 'acknowledged_at' => true, 'client_ip' => '127.0.0.1',
        ])->assertNotFound();
        $this->assertSame($local->id, $localAgreement->fresh()->proposal_id);
        $this->assertFalse($localAgreement->fresh()->confidentiality);
        $this->deleteJson(route('proposalcomplianceagreements.destroy'), ['recordIds' => [$peerAgreement->id]])->assertNotFound();
        $this->assertNull($peerAgreement->fresh()->deleted_at);
    }

    public function test_mixed_lab_bulk_delete_is_rejected_before_any_deletion(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $local = $this->proposal($lab, $user);
        $peer = $this->proposal(VAPLab::factory()->create(), $user);
        $this->actingAs($user)->deleteJson(route('proposals.destroy'), ['recordIds' => [$local->id, $peer->id]])->assertNotFound();
        $this->assertNull($local->fresh()->deleted_at);
        $this->assertNull($peer->fresh()->deleted_at);
        $this->deleteJson(route('proposals.destroy'), ['recordIds' => [$local->id]])->assertRedirect();
        $this->assertNotNull($local->fresh()->deleted_at);
        $this->patchJson(route('proposals.restore'), ['recordIds' => [$local->id]])->assertRedirect();
        $this->assertNull($local->fresh()->deleted_at);
    }

    public function test_proposal_audit_history_and_export_query_are_lab_private(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $user->givePermissionTo(Permission::findOrCreate('view_activity_log', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('export_activity_log', 'web'));
        $local = $this->proposal($lab, $user);
        $peer = $this->proposal(VAPLab::factory()->create(), $user);
        $localActivity = $local->activities()->firstOrFail();
        $peerActivity = $peer->activities()->firstOrFail();
        $peerActivity->update(['updated_at' => now()->addDay()]);
        $this->actingAs($user)->getJson(route('systemactivity.show', $peerActivity->id))->assertNotFound();
        $this->getJson(route('systemactivity.show', $localActivity->id))->assertOk();

        request()->attributes->set('proposal_laboratory_id', $lab->id);
        try {
            $ids = app(ExportHubQuery::class)->activityLog([])->pluck('activity_log.id');
            $this->assertTrue($ids->contains($localActivity->id));
            $this->assertFalse($ids->contains($peerActivity->id));
            $visibleCount = Activity::query()->count();
            $visibleUpdatedAt = Activity::query()->max('updated_at');
        } finally {
            request()->attributes->remove('proposal_laboratory_id');
        }

        $this->get(route('exports.index', ['dataset' => 'activity_log']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Exports/Index')
                ->where('datasets.0.key', 'activity_log')
                ->where('datasets.0.count', $visibleCount)
                ->where('datasets.0.updated_at', $visibleUpdatedAt)
                ->where('datasets', fn ($datasets) => ! collect($datasets)->contains('key', 'nonconformity_register'))
            );
    }
}
