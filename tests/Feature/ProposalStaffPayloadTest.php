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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProposalStaffPayloadTest extends TestCase
{
    use DatabaseTransactions;

    private User $operator;

    private User $owner;

    private User $author;

    private VAPLab $lab;

    private VAPProposal $proposal;

    private VAPProposalTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
        $this->lab = VAPLab::factory()->create();
        $this->operator = User::factory()->create(['is_active' => true]);
        foreach (['view_proposals', 'add_proposals', 'edit_proposals', 'delete_proposals', 'view_proposal_templates', 'edit_proposal_templates'] as $permission) {
            $this->operator->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->owner = $this->privateUser();
        $this->author = $this->privateUser();
        foreach ([$this->operator, $this->owner] as $user) {
            DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $user->id]);
        }
        $customer = Customer::create(['name' => fake()->company()]);
        $warehouse = Warehouse::create(['name' => 'Payload site '.str()->uuid(), 'address' => 'Shown site address', 'customer_id' => $customer->id]);
        $this->template = VAPProposalTemplate::create([
            'name' => 'Payload template '.str()->uuid(), 'content' => '<p>Commercial document</p>',
            'user_id' => $this->author->id, 'is_active' => true, 'theme_preset' => 'clean',
            'layout_schema' => ['footer_html' => '<p>Editor footer</p>'],
            'export_settings' => ['paper_size' => 'a4'],
        ]);
        $this->proposal = new VAPProposal([
            'proposal_year' => now()->year, 'proposal_no' => 'PAYLOAD-'.str()->uuid(),
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'department_id' => Department::factory()->create()->id, 'user_id' => $this->owner->id,
            'template_id' => $this->template->id, 'status' => 'PENDING',
            'unique_hash' => (string) str()->uuid(), 'service_location' => 'Commercial location',
            'details' => ['private_snapshot' => 'Not a browser prop'],
            'file_path' => 'vap-proposals/internal-evidence.pdf', 'sub_total' => 25, 'total' => 25,
        ]);
        $this->proposal->lab_id = $this->lab->id;
        $this->proposal->save();
        $this->proposal->items()->create([
            'item_description' => 'Shown analysis', 'qty' => 1, 'unit_price' => 25, 'total' => 25,
            'unit_id' => Unit::create(['code' => 'P-'.str()->uuid(), 'description' => 'Shown unit'])->id,
            'charge_tax' => false, 'withhold_tax' => false,
            'extra_data' => ['private_item_snapshot' => 'Not a browser prop'],
        ]);
        $this->proposal->complianceAgreement()->create([
            'confidentiality' => true, 'impartiality' => false, 'nondisclosure' => true,
            'acknowledged_at' => now(), 'client_ip' => '192.0.2.20',
        ]);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    private function privateUser(): User
    {
        return User::factory()->create([
            'is_active' => true, 'two_factor_secret' => 'private-totp',
            'two_factor_recovery_codes' => '["private-recovery"]',
            'microsoft_data' => ['token' => 'private-oauth'], 'dob' => '1988-01-01',
            'id_number' => (string) str()->uuid(), 'profile_photo_path' => 'private-photo',
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function assertIdentity(array $payload, User $user): void
    {
        $this->assertSame(['id', 'name'], array_keys($payload));
        $this->assertSame($user->id, $payload['id']);
        $this->assertSame($user->name, $payload['name']);
    }

    public function test_canonical_list_keeps_pagination_and_uses_minimal_identities_and_capabilities(): void
    {
        $response = $this->get(route('vap-proposals.index'))->assertOk();
        $this->assertSame(1, $response->inertiaProps('proposals.current_page'));
        $proposal = $response->inertiaProps('proposals.data.0');
        $this->assertIdentity($proposal['user'], $this->owner);
        $this->assertTrue($proposal['has_document']);
        $this->assertTrue($proposal['can_revise']);
        $this->assertTrue($proposal['can_archive']);
        $this->assertSame(1, $proposal['items_count']);
        foreach (['file_path', 'details', 'lab_id'] as $key) {
            $this->assertArrayNotHasKey($key, $proposal);
        }
    }

    public function test_canonical_show_preserves_commercial_and_consent_evidence_without_private_model_fields(): void
    {
        $response = $this->get(route('vap-proposals.show', $this->proposal))->assertOk();
        $proposal = $response->inertiaProps('proposal');
        $this->assertIdentity($proposal['user'], $this->owner);
        $this->assertIdentity($proposal['template']['user'], $this->author);
        $this->assertSame('Shown site address', $proposal['warehouse']['address']);
        $this->assertSame('Shown analysis', $proposal['items'][0]['item_description']);
        $this->assertSame('Shown unit', $proposal['items'][0]['unit']['description']);
        $this->assertSame('25.00', $proposal['items'][0]['unit_price']);
        $this->assertFalse($proposal['items'][0]['charge_tax']);
        $this->assertArrayNotHasKey('extra_data', $proposal['items'][0]);
        $this->assertArrayNotHasKey('file_path', $proposal);
        $this->assertArrayNotHasKey('details', $proposal);
        $this->assertArrayNotHasKey('layout_schema', $proposal['template']);
        $this->assertTrue($proposal['compliance_agreement']['confidentiality']);
        $this->assertFalse($proposal['compliance_agreement']['impartiality']);
        $this->assertSame('192.0.2.20', $proposal['compliance_agreement']['client_ip']);
        $this->assertArrayNotHasKey('compliance_agreement_logs', $proposal);
    }

    public function test_authoring_options_are_minimal_and_keep_the_selected_inactive_template(): void
    {
        $create = $this->get(route('vap-proposals.create'))->assertOk();
        $template = collect($create->inertiaProps('templates'))->firstWhere('id', $this->template->id);
        $this->assertIdentity($template['user'], $this->author);
        $this->assertArrayNotHasKey('layout_schema', $template);
        $this->assertSame($this->template->content, $template['content']);
        $this->template->update(['is_active' => false]);
        $edit = $this->get(route('vap-proposals.edit', $this->proposal))->assertOk();
        $selected = collect($edit->inertiaProps('templates'))->firstWhere('id', $this->template->id);
        $this->assertFalse($selected['is_active']);
        $this->assertIdentity($selected['user'], $this->author);
        $this->assertIdentity($edit->inertiaProps('proposal.user'), $this->owner);
        $this->assertSame('Shown site address', $edit->inertiaProps('proposal.warehouse.address'));
        $this->assertSame($this->proposal->customer_id, $edit->inertiaProps('proposal.customer.id'));
        $this->assertSame($this->proposal->department_id, $edit->inertiaProps('proposal.department.id'));
    }

    public function test_template_management_retains_editor_settings_but_not_author_secrets(): void
    {
        $index = $this->get(route('vap-proposals.templates.index'))->assertOk();
        $this->assertSame(1, $index->inertiaProps('templates.current_page'));
        $row = collect($index->inertiaProps('templates.data'))->firstWhere('id', $this->template->id);
        $this->assertIdentity($row['user'], $this->author);
        $this->assertSame(1, $row['proposals_count']);
        foreach (['show', 'edit'] as $action) {
            $response = $this->get(route('vap-proposals.templates.'.$action, $this->template))->assertOk();
            $this->assertIdentity($response->inertiaProps('template.user'), $this->author);
            $this->assertSame($this->template->layout_schema, $response->inertiaProps('template.layout_schema'));
            $this->assertSame($this->template->export_settings, $response->inertiaProps('template.export_settings'));
            if ($action === 'show') {
                $recent = $response->inertiaProps('recentProposals.0');
                $this->assertSame($this->proposal->id, $recent['id']);
                $this->assertArrayNotHasKey('file_path', $recent);
                $this->assertArrayNotHasKey('details', $recent);
                $this->assertSame(['id', 'proposal_number', 'status', 'total', 'customer', 'can_view'], array_keys($recent));
                $this->assertTrue($recent['can_view']);
            }
        }
    }

    public function test_view_only_staff_receive_no_write_capabilities(): void
    {
        $this->operator->syncPermissions([Permission::findOrCreate('view_proposals', 'web')]);
        $show = $this->get(route('vap-proposals.show', $this->proposal))->assertOk();
        $this->assertFalse($show->inertiaProps('canSend'));
        $this->assertFalse($show->inertiaProps('canRevise'));
        $this->assertFalse($show->inertiaProps('proposal.can_revise'));
        $this->assertFalse($show->inertiaProps('proposal.can_archive'));
        $index = $this->get(route('vap-proposals.index'))->assertOk();
        $this->assertFalse($index->inertiaProps('proposals.data.0.can_revise'));
        $this->assertFalse($index->inertiaProps('proposals.data.0.can_archive'));
        $this->get(route('vap-proposals.edit', $this->proposal))->assertForbidden();
    }

    public function test_revision_history_exposes_reason_and_comparison_only_and_preserves_stored_audit(): void
    {
        $causer = $this->privateUser();
        $properties = [
            'reason' => 'Changed technical scope',
            'old_values' => ['total' => 20, 'items_count' => 2, 'private_snapshot' => 'retained'],
            'new_values' => ['total' => 25, 'items_count' => 1, 'private_snapshot' => 'retained'],
            'old_items' => [['extra_data' => ['private_snapshot' => 'retained']]],
        ];
        $audit = activity()->performedOn($this->proposal)->causedBy($causer)
            ->withProperties($properties)->event('revised')->log('revised');
        $legacy = activity()->performedOn($this->proposal)->causedBy($causer)
            ->withProperties(['reason' => 'Earlier revision', 'old_values' => ['total' => 10]])->log('revised');
        $response = $this->get(route('vap-proposals.show', $this->proposal))->assertOk();
        $revisions = collect($response->inertiaProps('revisions'));
        $revision = $revisions->firstWhere('id', $audit->id);
        $this->assertIdentity($revision['causer'], $causer);
        $this->assertSame('revised', $revision['event']);
        $this->assertSame([
            'reason' => 'Changed technical scope', 'old_values' => ['total' => 20, 'items_count' => 2],
            'new_values' => ['total' => 25, 'items_count' => 1],
        ], $revision['properties']);
        $this->assertSame('revised', $revisions->firstWhere('id', $legacy->id)['event']);
        $this->assertSame($properties, $audit->fresh()->properties->all());
    }

    public function test_archived_references_serialize_as_null_without_lazy_identity_data(): void
    {
        $this->proposal->customer->delete();
        $this->proposal->warehouse->delete();
        $this->proposal->department->delete();
        $this->owner->delete();
        $this->template->delete();
        $response = $this->get(route('vap-proposals.show', $this->proposal))->assertOk();
        foreach (['customer', 'warehouse', 'department', 'user', 'template'] as $relation) {
            $this->assertNull($response->inertiaProps('proposal.'.$relation));
        }
    }

    public function test_deleted_template_authors_remain_null_across_options_and_management(): void
    {
        $this->author->delete();
        foreach (['show', 'edit'] as $action) {
            $response = $this->get(route('vap-proposals.templates.'.$action, $this->template))->assertOk();
            $this->assertNull($response->inertiaProps('template.user'));
        }
        $response = $this->get(route('vap-proposals.create'))->assertOk();
        $template = collect($response->inertiaProps('templates'))->firstWhere('id', $this->template->id);
        $this->assertArrayHasKey('user', $template);
        $this->assertNull($template['user']);
    }

    public function test_template_only_viewer_gets_no_proposal_token_or_authoring_fields(): void
    {
        $this->operator->syncPermissions([Permission::findOrCreate('view_proposal_templates', 'web')]);
        $response = $this->get(route('vap-proposals.templates.show', $this->template))->assertOk();
        $recent = $response->inertiaProps('recentProposals.0');
        $this->assertSame(['id', 'proposal_number', 'status', 'total', 'customer', 'can_view'], array_keys($recent));
        $this->assertFalse($recent['can_view']);
        $this->assertSame(['name'], array_keys($recent['customer']));
        $this->get(route('vap-proposals.show', $this->proposal))->assertForbidden();
    }

    public function test_retained_older_views_keep_evidence_without_item_snapshots_or_author_personal_data(): void
    {
        $response = $this->get(route('proposals.show', $this->proposal->id))->assertOk();
        $record = $response->inertiaProps('record.data');
        $this->assertSame($this->owner->name, $record['user']);
        $this->assertSame('Not a browser prop', $record['details']['private_snapshot']);
        $this->assertArrayNotHasKey('extra_data', $record['items'][0]);
        $this->assertArrayNotHasKey('file_path', $record);
        $this->assertTrue($record['has_document']);
        $index = $this->get(route('proposaltemplates.index'))->assertOk();
        $template = collect($index->inertiaProps('record.data'))->firstWhere('id', $this->template->id);
        $this->assertIdentity($template['user_id'], $this->author);
        $this->assertSame($this->author->name, $template['user']);
    }

    public function test_allowlisted_edit_items_round_trip_through_canonical_revision_with_visible_audit(): void
    {
        Notification::fake();
        $edit = $this->get(route('vap-proposals.edit', $this->proposal))->assertOk();
        $items = $edit->inertiaProps('proposal.items');
        $items[0]['qty'] = 2;
        $items[0]['extra_data'] = ['forged' => true];
        $this->put(route('vap-proposals.update', $this->proposal), [
            'service_location' => 'Revised location', 'revision_reason' => 'Updated commercial quantity',
            'tolerance_days' => 30, 'withhold_tax' => false, 'use_matrix_price' => true, 'items' => $items,
        ])->assertRedirect(route('vap-proposals.show', $this->proposal));
        $this->assertSame('50.00', $this->proposal->fresh()->total);
        $this->assertSame(1, $this->proposal->items()->count());
        $this->assertSame([], $this->proposal->items()->first()->extra_data ?? []);
        $this->assertTrue($this->proposal->complianceAgreement->fresh()->confidentiality);
        $show = $this->get(route('vap-proposals.show', $this->proposal))->assertOk();
        $revision = collect($show->inertiaProps('revisions'))->firstWhere('event', 'revised');
        $this->assertSame('Updated commercial quantity', $revision['properties']['reason']);
        $this->assertSame(1, $revision['properties']['new_values']['items_count']);
    }
}
