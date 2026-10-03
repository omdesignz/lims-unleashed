<?php

namespace Tests\Feature;

use App\Models\CollectionProduct;
use App\Models\LabCode;
use App\Models\Matrix;
use App\Models\Parameter;
use App\Models\Product;
use App\Models\Profile;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProposalLabCodeAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_lookup_only_returns_unambiguous_live_local_codes_even_for_admins(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $local = $this->code($lab);
        $counteranalysis = $this->code($lab, 'counteranalysis');
        $peerCode = $this->code($peer);
        $orphan = $this->code(null);
        $unassigned = $this->code(null);
        DB::table('sample_entries')->insert([
            'name' => 'Legacy unassigned sample', 'lab_id' => null, 'collection_product_id' => $unassigned->collection_id,
        ]);
        DB::statement('SET CONSTRAINTS sample_entries_collection_product_unique DEFERRED');
        $conflict = $this->code($lab);
        VAPSampleEntry::factory()->create(['lab_id' => $peer->id, 'collection_product_id' => $conflict->collection_id]);
        $deletedConflict = $this->code($lab);
        VAPSampleEntry::factory()->create(['lab_id' => $peer->id, 'collection_product_id' => $deletedConflict->collection_id])->delete();
        $nullConflict = $this->code($lab);
        DB::table('sample_entries')->insert([
            'name' => 'Legacy ambiguous sample', 'lab_id' => null, 'collection_product_id' => $nullConflict->collection_id,
        ]);
        $deleted = $this->code($lab);
        $deleted->delete();
        $deletedSample = $this->code($lab);
        VAPSampleEntry::query()->where('collection_product_id', $deletedSample->collection_id)->delete();
        $deletedCollection = $this->code($lab);
        DB::table('collection_product')->where('id', $deletedCollection->collection_id)->update(['deleted_at' => now()]);

        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id])
            ->getJson(route('vap-proposals.options.lab-codes'))
            ->assertOk()->assertJsonCount(2)
            ->assertJsonFragment(['value' => $local->id])
            ->assertJsonFragment(['value' => $counteranalysis->id]);

        foreach ([$peerCode, $orphan, $unassigned, $conflict, $deletedConflict, $nullConflict, $deleted, $deletedSample, $deletedCollection] as $hidden) {
            $this->getJson(route('vap-proposals.options.lab-codes', ['q' => $hidden->code]))
                ->assertOk()->assertJsonMissing(['value' => $hidden->id]);
            foreach ([true, false] as $matrixPrice) {
                $this->getJson(route('vap-proposals.options.lab-code-parameters', [
                    'code_id' => $hidden->id, 'use_matrix_price' => $matrixPrice,
                ]))->assertOk()->assertExactJson([]);
            }
        }

        $this->getJson(route('vap-proposals.options.lab-code-parameters', ['code_id' => $local->id]))
            ->assertOk()->assertJsonPath('0.value', $local->collection->product->matrix_id);
        $this->assertTrue(Schema::hasIndex('sample_entries', 'sample_entries_collection_ownership_index'));
    }

    public function test_switching_labs_changes_both_code_search_and_parameter_access(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $user->id]);
        $local = $this->code($lab);
        $peerCode = $this->code($peer);

        $this->actingAs($user)->withSession(['active_lab_id' => $peer->id])
            ->getJson(route('vap-proposals.options.lab-codes', ['q' => $peerCode->code]))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.value', $peerCode->id);
        $this->getJson(route('vap-proposals.options.lab-code-parameters', ['code_id' => $local->id]))
            ->assertOk()->assertExactJson([]);
        $this->getJson(route('vap-proposals.options.lab-code-parameters', ['code_id' => $peerCode->id]))
            ->assertOk()->assertJsonPath('0.value', $peerCode->collection->product->matrix_id);
    }

    public function test_membership_and_proposal_permission_are_required(): void
    {
        $lab = VAPLab::factory()->create();
        $code = $this->code($lab);
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::findOrCreate('view_proposals', 'web'));
        $this->actingAs($user)->getJson(route('vap-proposals.options.lab-codes'))->assertForbidden();
        $this->getJson(route('vap-proposals.options.lab-code-parameters', ['code_id' => $code->id]))->assertForbidden();
        $user->revokePermissionTo('view_proposals');
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->getJson(route('vap-proposals.options.lab-codes'))->assertForbidden();
    }

    public function test_local_code_parameter_lookup_returns_only_active_parameters(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $code = $this->code($lab);
        $profile = Profile::query()->create(['name' => 'Lookup profile']);
        $active = Parameter::query()->create(['name' => 'Active lookup parameter', 'active' => true, 'price' => 25]);
        $inactive = Parameter::query()->create(['name' => 'Inactive lookup parameter', 'active' => false]);
        DB::table('matrix_profile')->insert(['matrix_id' => $code->collection->product->matrix_id, 'profile_id' => $profile->id]);
        foreach ([$active, $inactive] as $parameter) {
            DB::table('parameter_profile')->insert(['parameter_id' => $parameter->id, 'profile_id' => $profile->id]);
        }
        $removed = Parameter::query()->create(['name' => 'Removed lookup parameter', 'active' => true]);
        DB::table('parameter_profile')->insert([
            'parameter_id' => $removed->id, 'profile_id' => $profile->id, 'deleted_at' => now(),
        ]);

        $this->actingAs($user)->getJson(route('vap-proposals.options.lab-code-parameters', [
            'code_id' => $code->id, 'use_matrix_price' => false,
        ]))->assertOk()->assertJsonCount(1)->assertJsonPath('0.value', $active->id);

        DB::table('matrix_profile')->where('profile_id', $profile->id)->update(['deleted_at' => now()]);
        $this->getJson(route('vap-proposals.options.lab-code-parameters', [
            'code_id' => $code->id, 'use_matrix_price' => false,
        ]))->assertOk()->assertExactJson([]);
    }

    public function test_ownership_index_can_be_rolled_back_and_scope_fails_closed_without_lab(): void
    {
        $this->code(VAPLab::factory()->create());
        $this->assertSame(0, LabCode::query()->forLaboratory(0)->count());
        $migration = require database_path('migrations/2026_09_27_111907_add_collection_ownership_index_to_sample_entries_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasIndex('sample_entries', 'sample_entries_collection_ownership_index'));
        $migration->up();
        $this->assertTrue(Schema::hasIndex('sample_entries', 'sample_entries_collection_ownership_index'));
    }

    private function operator(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::findOrCreate('view_proposals', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    private function code(?VAPLab $lab, string $type = 'analysis'): LabCode
    {
        $matrix = Matrix::query()->create(['description' => 'Lookup matrix', 'fixed_price' => 125]);
        $product = Product::query()->create(['name' => 'Lookup product '.fake()->uuid(), 'matrix_id' => $matrix->id]);
        $collection = CollectionProduct::query()->create(['product_id' => $product->id]);
        $code = LabCode::query()->create([
            'collection_id' => $collection->id, 'cl_month' => now()->format('y/m'), 'codeable_type' => $type,
        ]);
        if ($lab) {
            VAPSampleEntry::factory()->create(['lab_id' => $lab->id, 'collection_product_id' => $collection->id]);
        }

        return $code;
    }
}
