<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerShowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_show_exposes_chart_payloads(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->firstOrFail();

        $this->actingAs($user)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Show')
                ->where('record.data.id', $customer->id)
                ->where('charts.commercial_health.labels.0', 'Propostas aceites')
                ->where('charts.execution_mix.labels.0', 'Amostras em curso')
                ->where('charts.financial_pressure.labels.0', 'Montante em aberto')
                ->has('customerState.summary')
            );
    }
}
