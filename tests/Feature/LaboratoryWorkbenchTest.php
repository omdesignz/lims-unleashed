<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LaboratoryWorkbenchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_workbench_never_returns_another_labs_samples(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $lab = VAPLab::factory()->create();
        $other = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['user_id' => $user->id, 'lab_id' => $lab->id]);
        DB::table('sample_entries')->insert([
            ['name' => 'Local sample', 'lab_id' => $lab->id, 'status' => 'EN_PAUSA'],
            ['name' => 'Private sample', 'lab_id' => $other->id, 'status' => 'POR_INICIAR'],
        ]);
        $this->actingAs($user)->withSession(['active_lab_id' => $other->id])->get(route('dashboard'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('LaboratoryWorkbench')
            ->has('samples.data', 1)->where('samples.data.0.name', 'Local sample')
            ->where('metrics.on_hold', 1)->where('metrics.waiting', 0));
    }

    public function test_no_membership_produces_a_safe_empty_workbench(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))->get(route('dashboard'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->where('lab', null)->has('samples.data', 0));
    }
}
