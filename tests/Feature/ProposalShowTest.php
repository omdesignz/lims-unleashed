<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Proposal;
use App\Models\ProposalTemplate;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProposalShowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_proposal_show_exposes_chart_payloads(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::findOrCreate('view_proposals', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $template = ProposalTemplate::query()->create([
            'name' => 'Proposal chart fixture', 'content' => '<p>Fixture</p>', 'user_id' => $user->id,
        ]);
        $customer = Customer::query()->create(['name' => 'Chart customer']);
        $warehouse = Warehouse::query()->create(['name' => 'Chart site', 'customer_id' => $customer->id]);
        $proposal = new Proposal([
            'proposal_year' => now()->year, 'user_id' => $user->id,
            'department_id' => Department::factory()->create()->id, 'template_id' => $template->id,
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
        ]);
        $proposal->lab_id = $lab->id;
        $proposal->save();

        $this->actingAs($user)
            ->get(route('proposals.show', $proposal->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Proposals/Show')
                ->where('record.data.id', $proposal->id)
                ->where('charts.financial_breakdown.labels.0', 'Subtotal')
                ->where('charts.item_composition.labels.0', 'Itens tributáveis')
                ->where('charts.workflow_summary.labels.0', 'Revisões')
            );
    }
}
