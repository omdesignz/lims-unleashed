<?php

namespace Tests\Feature;

use App\Models\LabNetwork;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LaboratorySampleQueueTest extends TestCase
{
    use DatabaseTransactions;

    public function test_queue_indexes_can_be_applied_and_reversed_without_changing_records(): void
    {
        $migration = require database_path('migrations/2026_09_27_052804_add_laboratory_queue_indexes_to_sample_entries_table.php');
        $schema = Schema::getFacadeRoot();
        if ($schema->hasIndex('sample_entries', 'sample_entries_lab_queue_index')) {
            $migration->down();
        }
        $count = VAPSampleEntry::count();
        $migration->up();
        $this->assertTrue($schema->hasIndex('sample_entries', ['lab_id', 'id']));
        $this->assertTrue($schema->hasIndex('sample_entries', ['lab_id', 'status', 'id']));
        $migration->down();
        $this->assertFalse($schema->hasIndex('sample_entries', 'sample_entries_lab_queue_index'));
        $this->assertFalse($schema->hasIndex('sample_entries', 'sample_entries_lab_status_queue_index'));
        $this->assertSame($count, VAPSampleEntry::count());
    }

    private function operator(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::findOrCreate('view_samples', 'web'));
        DB::table('lab_user')->insert(['user_id' => $user->id, 'lab_id' => $lab->id]);

        return $user;
    }

    public function test_queue_is_paginated_and_search_cannot_escape_the_active_lab(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        VAPSampleEntry::factory()->count(27)->create(['lab_id' => $lab->id, 'name' => 'Water sample']);
        VAPSampleEntry::factory()->create(['code' => 'Water-secret', 'name' => 'Private sample']);
        $this->actingAs($user)->get(route('vap_samples.queue', ['search' => 'Water']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('VAPSamples/Queue')
            ->has('samples.data', 25)->where('samples.meta.total', 27)
            ->where('filters.search', 'Water')
            ->missing('samples.data.0.obs')->missing('samples.data.0.details'));
        $this->get(route('vap_samples.queue', ['search' => 'Water', 'page' => 2]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->has('samples.data', 2)
            ->where('samples.meta.current_page', 2)->where('samples.meta.total', 27));
    }

    public function test_status_counts_cover_only_the_active_lab_and_ignore_filters(): void
    {
        $lab = VAPLab::factory()->create();
        VAPSampleEntry::factory()->count(2)->create(['lab_id' => $lab->id, 'status' => 'EN_PAUSA']);
        VAPSampleEntry::factory()->create(['lab_id' => $lab->id, 'status' => 'EN_PROGRESO']);
        VAPSampleEntry::factory()->create(['status' => 'EN_PAUSA']);
        $this->actingAs($this->operator($lab))->get(route('vap_samples.queue', ['status' => 'EN_PROGRESO']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('counts.EN_PAUSA', 2)->where('counts.EN_PROGRESO', 1)
                ->where('counts.POR_INICIAR', 0)->where('counts.COMPLETADO', 0)->where('counts.CANCELADO', 0)
                ->has('samples.data', 1));
    }

    public function test_filters_are_validated_and_wildcards_are_literal(): void
    {
        $lab = VAPLab::factory()->create();
        $this->actingAs($this->operator($lab));
        VAPSampleEntry::factory()->create(['lab_id' => $lab->id, 'name' => 'Water 100%', 'status' => 'EN_PAUSA']);
        VAPSampleEntry::factory()->create(['lab_id' => $lab->id, 'name' => 'Water 100X']);
        $this->get(route('vap_samples.queue', ['search' => '%', 'status' => 'EN_PAUSA']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->has('samples.data', 1)->where('samples.data.0.name', 'Water 100%'));
        $this->getJson(route('vap_samples.queue', ['per_page' => 10000, 'page' => -1, 'status' => 'invalid', 'search' => str_repeat('x', 101)]))
            ->assertUnprocessable()->assertJsonValidationErrors(['per_page', 'page', 'status', 'search']);
    }

    public function test_forged_lab_session_falls_back_to_a_direct_membership(): void
    {
        $lab = VAPLab::factory()->create();
        $other = VAPLab::factory()->create();
        $local = VAPSampleEntry::factory()->create(['lab_id' => $lab->id]);
        VAPSampleEntry::factory()->create(['lab_id' => $other->id]);
        $this->actingAs($this->operator($lab))->withSession(['active_lab_id' => $other->id])
            ->get(route('vap_samples.queue'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('lab.id', $lab->id)
                ->has('samples.data', 1)->where('samples.data.0.id', $local->id));
    }

    public function test_network_overview_never_grants_peer_sample_details(): void
    {
        $network = LabNetwork::factory()->create();
        $main = VAPLab::factory()->create(['network_id' => $network->id]);
        $peer = VAPLab::factory()->create(['network_id' => $network->id]);
        $network->update(['main_lab_id' => $main->id]);
        $user = $this->operator($main);
        DB::table('lab_user')->where('user_id', $user->id)->update(['can_view_network' => true]);
        $sample = VAPSampleEntry::factory()->create(['lab_id' => $peer->id]);
        $this->actingAs($user)->getJson(route('vap_samples.queue.show', $sample))->assertNotFound();
    }

    public function test_admin_without_membership_cannot_bypass_lab_boundary(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $sample = VAPSampleEntry::factory()->create();
        $this->actingAs($admin)->get(route('vap_samples.queue'))->assertForbidden();
        $this->getJson(route('vap_samples.queue.show', $sample))->assertForbidden();
    }

    public function test_permission_is_required_for_queue_and_quick_view(): void
    {
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true]);
        DB::table('lab_user')->insert(['user_id' => $user->id, 'lab_id' => $lab->id]);
        $sample = VAPSampleEntry::factory()->create(['lab_id' => $lab->id]);
        $this->actingAs($user)->get(route('vap_samples.queue'))->assertForbidden();
        $this->getJson(route('vap_samples.queue.show', $sample))->assertForbidden();
    }

    public function test_quick_view_exposes_only_its_allowlisted_fields_and_handles_null_dates(): void
    {
        $lab = VAPLab::factory()->create();
        $sample = VAPSampleEntry::factory()->create([
            'lab_id' => $lab->id, 'received_at' => null, 'retention_due_at' => null,
            'obs' => 'Transport verified', 'client_submitted_info' => ['private_note' => 'Not for the queue'],
        ]);
        $this->actingAs($this->operator($lab))->getJson(route('vap_samples.queue.show', $sample))
            ->assertOk()->assertJsonPath('data.id', $sample->id)
            ->assertJsonPath('data.received_at', null)
            ->assertJsonPath('data.details.observations', 'Transport verified')
            ->assertJsonMissingPath('data.client_submitted_info');
    }

    public function test_deleted_samples_are_absent_from_queue_and_quick_view(): void
    {
        $lab = VAPLab::factory()->create();
        $sample = VAPSampleEntry::factory()->create(['lab_id' => $lab->id]);
        $sample->delete();
        $this->actingAs($this->operator($lab))->get(route('vap_samples.queue'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('samples.data', 0));
        $this->getJson(route('vap_samples.queue.show', $sample))->assertNotFound();
    }
}
