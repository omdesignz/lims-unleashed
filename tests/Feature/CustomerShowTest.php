<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerShowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_show_exposes_only_active_laboratory_operations(): void
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $firstLab = VAPLab::factory()->create();
        $secondLab = VAPLab::factory()->create();
        DB::table('lab_user')->insert([
            ['lab_id' => $firstLab->id, 'user_id' => $user->id],
            ['lab_id' => $secondLab->id, 'user_id' => $user->id],
        ]);
        $customer = Customer::query()->create(['name' => 'Customer show regression']);
        $warehouse = Warehouse::query()->create([
            'name' => 'Customer show site',
            'customer_id' => $customer->id,
        ]);
        $customer->update(['warehouse_id' => $warehouse->id]);
        $department = Department::factory()->create();
        $template = VAPProposalTemplate::query()->create([
            'name' => 'Customer show template',
            'content' => '<p>Fixture</p>',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        foreach ([$firstLab, $secondLab] as $lab) {
            $proposal = new VAPProposal([
                'proposal_year' => now()->year,
                'proposal_no' => 'TEST-'.Str::uuid(),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'department_id' => $department->id,
                'template_id' => $template->id,
                'user_id' => $user->id,
                'status' => 'ACCEPTED',
                'details' => ['fixture' => true],
            ]);
            $proposal->lab_id = $lab->id;
            $proposal->save();
        }

        $firstSample = VAPSampleEntry::factory()->create([
            'lab_id' => $firstLab->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'POR_INICIAR',
        ]);
        $secondSample = VAPSampleEntry::factory()->create([
            'lab_id' => $secondLab->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'COMPLETADO',
        ]);

        $this->actingAs($user)->withSession(['active_lab_id' => $firstLab->id])
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Show')
                ->where('record.data.id', $customer->id)
                ->where('customerState.summary.accepted_proposals', 1)
                ->where('customerState.summary.samples_in_progress', 1)
                ->where('customerState.summary.completed_samples', 0)
                ->has('customerState.recent_samples', 1)
                ->where('customerState.recent_samples.0.id', $firstSample->id)
                ->missing('charts')
                ->missing('customerState.open_finance')
                ->missing('customerState.recent_requests')
                ->etc()
            );

        $this->withSession(['active_lab_id' => $secondLab->id])
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Show')
                ->where('record.data.id', $customer->id)
                ->where('customerState.summary.accepted_proposals', 1)
                ->where('customerState.summary.samples_in_progress', 0)
                ->where('customerState.summary.completed_samples', 1)
                ->has('customerState.recent_samples', 1)
                ->where('customerState.recent_samples.0.id', $secondSample->id)
                ->etc()
            );
    }

    public function test_customer_show_requires_direct_laboratory_membership(): void
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $customer = Customer::query()->create(['name' => 'Customer without laboratory']);

        $this->actingAs($user)->get(route('customers.show', $customer))->assertForbidden();
    }
}
