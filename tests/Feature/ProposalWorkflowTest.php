<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Warehouse;
use App\Notifications\GlobalNotification;
use App\Notifications\ProposalSentNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProposalWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->first();

        $this->assertNotNull($admin, 'Expected at least one verified admin user for proposal workflow testing.');

        return $admin;
    }

    private function draftProposal(User $user): VAPProposal
    {
        $customer = Customer::query()->firstOrFail();
        $department = Department::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $template = VAPProposalTemplate::query()->first();

        if (! $template) {
            $template = VAPProposalTemplate::query()->create([
                'name' => 'Smoke Template',
                'content' => '<p>Smoke template</p>',
                'user_id' => $user->id,
                'is_active' => true,
            ]);
        }

        /** @var VAPProposal $proposal */
        $proposal = VAPProposal::query()->create([
            'proposal_year' => now()->year,
            'service_location' => 'Smoke Workflow',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'department_id' => $department->id,
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'PENDING',
            'details' => [],
            'obs' => 'Smoke proposal',
            'sub_total' => 0,
            'total' => 0,
            'tax' => 0,
            'discount' => 0,
            'unique_hash' => (string) str()->uuid(),
            'tolerance_days' => 30,
            'withhold_tax' => false,
            'use_matrix_price' => true,
            'withholding_tax_amount' => 0,
            'withholding_tax_percentage' => 0,
            'global_discount_amount' => 0,
            'global_discount_percentage' => 0,
            'converted_to_invoice' => false,
        ]);

        $proposal->complianceAgreement()->create([
            'confidentiality' => false,
            'impartiality' => false,
            'nondisclosure' => false,
        ]);

        return $proposal->fresh(['warehouse', 'user']);
    }

    public function test_sending_a_vap_proposal_notifies_customer_and_internal_owner(): void
    {
        Notification::fake();

        $user = $this->verifiedAdmin();
        $proposal = $this->draftProposal($user);

        ob_start();

        try {
            $response = $this->withoutMiddleware(VerifyCsrfToken::class)
                ->actingAs($user)
                ->post(route('vap-proposals.send', $proposal));
            ob_end_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        $response->assertRedirect();

        $proposal->refresh();

        $this->assertSame('SENT', $proposal->status);
        $this->assertNotNull($proposal->file_path);
        $this->assertStringContainsString('vap-proposals/', $proposal->file_path);

        Notification::assertSentTo($proposal->warehouse, ProposalSentNotification::class);
        Notification::assertSentTo($user, GlobalNotification::class);
    }

    public function test_public_thank_you_route_uses_vap_proposal_binding(): void
    {
        $proposal = $this->draftProposal($this->verifiedAdmin());

        $response = $this->get(route('vap-proposals.public.thankyou', $proposal));

        $response->assertSuccessful();
    }

    public function test_public_accept_returns_vap_thank_you_redirect(): void
    {
        $proposal = $this->draftProposal($this->verifiedAdmin());
        $proposal->update(['status' => 'SENT']);

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->postJson(
                route('proposals.api.accept', $proposal),
                [
                    'confidentiality' => true,
                    'impartiality' => true,
                    'nondisclosure' => true,
                ]
            );

        $response
            ->assertSuccessful()
            ->assertJsonPath('redirect', route('vap-proposals.public.thankyou', $proposal));
    }
}
