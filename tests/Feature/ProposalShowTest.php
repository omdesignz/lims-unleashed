<?php

namespace Tests\Feature;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProposalShowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_proposal_show_exposes_chart_payloads(): void
    {
        $user = User::query()->firstOrFail();
        $proposal = Proposal::query()->firstOrFail();

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
