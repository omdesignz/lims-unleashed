<?php

namespace Tests\Feature;

use App\Actions\RecordPublicProposalDecision;
use App\Actions\RecordPublicProposalView;
use App\Models\Customer;
use App\Models\Department;
use App\Models\ISOActivityLog;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Models\VAPProposalComplianceAgreement;
use App\Models\VAPProposalComplianceAgreementLog;
use App\Models\VAPProposalTemplate;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PublicProposalDecisionTest extends TestCase
{
    use DatabaseTransactions;

    private VAPProposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();
        config(['broadcasting.default' => 'null', 'mail.default' => 'array']);
        $owner = User::factory()->create();
        $lab = VAPLab::factory()->create();
        $customer = Customer::create(['name' => fake()->company()]);
        $site = Warehouse::create(['name' => fake()->company(), 'customer_id' => $customer->id]);
        $template = VAPProposalTemplate::create(['name' => 'Decision fixture', 'content' => '<p>Decision</p>', 'user_id' => $owner->id]);
        $this->proposal = new VAPProposal([
            'proposal_no' => 'DECISION-'.str()->uuid(), 'proposal_year' => now()->year,
            'customer_id' => $customer->id, 'warehouse_id' => $site->id, 'department_id' => Department::factory()->create()->id,
            'user_id' => $owner->id, 'template_id' => $template->id,
            'unique_hash' => (string) str()->uuid(), 'status' => 'SENT', 'details' => ['evidence' => 'retained'],
            'obs' => 'Original observation', 'sub_total' => 0, 'total' => 0,
        ]);
        $this->proposal->lab_id = $lab->id;
        $this->proposal->save();
        $this->proposal->refresh();
        Notification::fake();
    }

    private function payload(bool $accepted): array
    {
        return $accepted ? ['confidentiality' => true, 'impartiality' => false, 'nondisclosure' => false]
            : ['reason' => 'A proposta precisa de alteração comercial.'];
    }

    #[DataProvider('allowedDecisions')]
    public function test_current_allowed_states_preserve_existing_decision_contract(string $status, bool $accepted): void
    {
        $this->proposal->update(['status' => $status]);
        $before = $this->proposal->getRawOriginal();
        $this->postJson(route('proposals.api.'.($accepted ? 'accept' : 'reject'), $this->proposal->unique_hash), $this->payload($accepted))
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('redirect', route('vap-proposals.public.thankyou', $this->proposal->unique_hash));
        $current = $this->proposal->fresh();
        $this->assertSame($accepted ? 'ACCEPTED' : 'REJECTED', $current->status);
        foreach (['lab_id', 'customer_id', 'warehouse_id', 'unique_hash', 'proposal_no', 'details'] as $field) {
            $this->assertSame($before[$field], $current->getRawOriginal($field));
        }
        $agreement = $current->complianceAgreement()->sole();
        $this->assertSame($accepted, $agreement->confidentiality);
        $this->assertFalse($agreement->impartiality);
        $this->assertFalse($agreement->nondisclosure);
        $this->assertSame('127.0.0.1', $agreement->client_ip);
        $this->assertSame($accepted ? 1 : 0, $current->complianceAgreementLogs()->count());
        if ($accepted) {
            $this->assertFalse($current->complianceAgreementLogs()->sole()->nondisclosure);
            $this->assertNull($agreement->rejected_at);
        } else {
            $this->assertSame($this->payload(false)['reason'], $agreement->rejection_reason);
            $this->assertNull($agreement->acknowledged_at);
            $this->assertSame('Original observation'."\n\nMotivo da rejeição: ".$this->payload(false)['reason'], $current->obs);
        }
        $this->assertSame(1, Activity::where('subject_id', $current->id)->where('description', $accepted ? 'accepted' : 'rejected')->count());

        $this->postJson(route('proposals.api.'.($accepted ? 'reject' : 'accept'), $current->unique_hash), $this->payload(! $accepted))
            ->assertStatus(400)->assertJsonPath('success', false);
        $this->assertSame($current->getRawOriginal(), $this->proposal->fresh()->getRawOriginal());
        $this->assertSame($agreement->getRawOriginal(), $agreement->fresh()->getRawOriginal());
    }

    public static function allowedDecisions(): array
    {
        $cases = [];
        foreach (['SENT', 'VIEWED', 'REVISED'] as $status) {
            foreach ([true, false] as $accepted) {
                $cases[] = [$status, $accepted];
            }
        }

        return $cases;
    }

    #[DataProvider('blockedStates')]
    public function test_stale_bound_state_cannot_replace_a_terminal_decision(string $status, bool $accepted): void
    {
        $bound = $this->proposal->fresh();
        DB::table('proposals')->where('id', $bound->id)->update(['status' => $status]);
        try {
            app(RecordPublicProposalDecision::class)->execute($bound, $accepted, $this->payload($accepted), '127.0.0.1');
            $this->fail('Only current eligible states may receive a decision.');
        } catch (HttpException $exception) {
            $this->assertSame(400, $exception->getStatusCode());
        }
        $this->assertSame($status, $this->proposal->fresh()->status);
        $this->assertSame(0, $this->proposal->complianceAgreement()->count());
        $this->assertSame(0, $this->proposal->complianceAgreementLogs()->count());
    }

    public static function blockedStates(): array
    {
        $cases = [];
        foreach (['PENDING', 'ACCEPTED', 'REJECTED', 'EXPIRED'] as $status) {
            foreach ([true, false] as $accepted) {
                $cases[] = [$status, $accepted];
            }
        }

        return $cases;
    }

    #[DataProvider('sourceRevocations')]
    public function test_stale_tokens_and_source_identity_fail_closed(string $revocation, bool $accepted): void
    {
        $bound = $this->proposal->fresh();
        match ($revocation) {
            'token' => $this->proposal->update(['unique_hash' => (string) str()->uuid()]),
            'proposal' => $this->proposal->delete(),
            'lab' => $this->proposal->lab->delete(),
            'customer' => $this->proposal->customer->delete(),
            'site' => $this->proposal->warehouse->delete(),
            'site_customer' => $this->proposal->warehouse->update(['customer_id' => Customer::create(['name' => 'Peer customer'])->id]),
            'owner' => $this->proposal->update(['user_id' => User::factory()->create()->id]),
            'source' => $this->proposal->update(['warehouse_id' => Warehouse::create(['name' => 'Different site', 'customer_id' => $this->proposal->customer_id])->id]),
            'lab_identity' => DB::table('proposals')->where('id', $this->proposal->id)->update(['lab_id' => VAPLab::factory()->create()->id]),
        };
        $before = $this->proposal->fresh()->getRawOriginal();
        try {
            app(RecordPublicProposalDecision::class)->execute($bound, $accepted, $this->payload($accepted), '127.0.0.1');
            $this->fail('Revoked source must not accept a stale decision.');
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(VAPProposal::class, $exception->getModel());
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
        $this->assertSame($before, $this->proposal->fresh()->getRawOriginal());
        $this->assertSame(0, $this->proposal->complianceAgreement()->count());
        $this->assertSame(0, $this->proposal->complianceAgreementLogs()->count());
    }

    public static function sourceRevocations(): array
    {
        $cases = [];
        foreach (['token', 'proposal', 'lab', 'customer', 'site', 'site_customer', 'owner', 'source', 'lab_identity'] as $revocation) {
            foreach ([true, false] as $accepted) {
                $cases[] = [$revocation, $accepted];
            }
        }

        return $cases;
    }

    #[DataProvider('cancelledWrites')]
    public function test_cancelled_lifecycle_writes_roll_back_all_evidence(string $model, bool $accepted): void
    {
        $before = $this->proposal->getRawOriginal();
        $count = Activity::count();
        $event = 'eloquent.saving: '.$model;
        Event::listen($event, fn (): bool => false);
        try {
            $this->postJson(route('proposals.api.'.($accepted ? 'accept' : 'reject'), $this->proposal->unique_hash), $this->payload($accepted))
                ->assertStatus(409)->assertJsonPath('success', false);
            $this->assertSame($before, $this->proposal->fresh()->getRawOriginal());
            $this->assertSame(0, $this->proposal->complianceAgreement()->count());
            $this->assertSame(0, $this->proposal->complianceAgreementLogs()->count());
            $this->assertSame($count, Activity::count());
        } finally {
            Event::forget($event);
        }
    }

    public static function cancelledWrites(): array
    {
        return [[VAPProposal::class, true], [VAPProposal::class, false], [VAPProposalComplianceAgreement::class, true],
            [VAPProposalComplianceAgreement::class, false], [VAPProposalComplianceAgreementLog::class, true], [ISOActivityLog::class, true], [ISOActivityLog::class, false]];
    }

    public function test_invalid_payloads_fail_before_any_mutation(): void
    {
        $before = $this->proposal->getRawOriginal();
        $this->postJson(route('proposals.api.accept', $this->proposal->unique_hash), ['confidentiality' => 'not-boolean'])
            ->assertUnprocessable()->assertJsonValidationErrors(['confidentiality', 'impartiality', 'nondisclosure']);
        $this->postJson(route('proposals.api.reject', $this->proposal->unique_hash), ['reason' => 'short'])
            ->assertUnprocessable()->assertJsonValidationErrors('reason');
        try {
            app(RecordPublicProposalDecision::class)->execute($this->proposal, true, [], null);
            $this->fail('Direct callers require the same validated contract.');
        } catch (ValidationException $exception) {
            $this->assertCount(3, $exception->errors());
        }
        $this->assertSame($before, $this->proposal->fresh()->getRawOriginal());
        $this->assertSame(0, $this->proposal->complianceAgreement()->count());
    }

    public function test_ambiguous_active_agreements_cannot_be_overwritten(): void
    {
        foreach (['127.0.0.3', '127.0.0.4'] as $ip) {
            $this->proposal->complianceAgreement()->create(['confidentiality' => true, 'impartiality' => true, 'nondisclosure' => true, 'client_ip' => $ip]);
        }
        $before = $this->proposal->complianceAgreement()->get()->map->getRawOriginal()->all();
        $this->postJson(route('proposals.api.accept', $this->proposal->unique_hash), $this->payload(true))->assertStatus(409);
        $this->assertSame('SENT', $this->proposal->fresh()->status);
        $this->assertSame($before, $this->proposal->complianceAgreement()->get()->map->getRawOriginal()->all());
        $this->assertSame(0, $this->proposal->complianceAgreementLogs()->count());
    }

    public function test_existing_agreements_and_unknown_logs_keep_their_identity_and_history(): void
    {
        $archived = $this->proposal->complianceAgreement()->create(['confidentiality' => true, 'impartiality' => true, 'nondisclosure' => true, 'client_ip' => '127.0.0.3']);
        $archived->delete();
        $archivedBefore = $archived->fresh()->getRawOriginal();
        $active = $this->proposal->complianceAgreement()->create(['confidentiality' => false, 'impartiality' => false, 'nondisclosure' => false,
            'rejected_at' => now()->subDay(), 'rejection_reason' => 'Older reason', 'client_ip' => '127.0.0.4']);
        $oldLog = $this->proposal->complianceAgreementLogs()->create(['confidentiality' => false, 'impartiality' => false, 'client_ip' => '127.0.0.4']);
        $oldBefore = $oldLog->fresh()->getRawOriginal();
        $this->postJson(route('proposals.api.accept', $this->proposal->unique_hash), [
            ...$this->payload(true), 'status' => 'REJECTED', 'lab_id' => 0, 'proposal_id' => 0, 'client_ip' => 'forged',
        ])->assertOk();
        $this->assertSame($active->id, $this->proposal->complianceAgreement()->sole()->id);
        $this->assertNull($active->fresh()->rejected_at);
        $this->assertNull($active->fresh()->rejection_reason);
        $this->assertSame('127.0.0.1', $active->fresh()->client_ip);
        $this->assertSame($archivedBefore, $archived->fresh()->getRawOriginal());
        $this->assertSame($oldBefore, $oldLog->fresh()->getRawOriginal());
        $this->assertSame(2, $this->proposal->complianceAgreementLogs()->count());
    }

    public function test_public_views_do_not_replace_decisions_or_repeat_view_audits(): void
    {
        $view = app(RecordPublicProposalView::class);
        $view->execute($this->proposal->unique_hash, '127.0.0.1');
        $view->execute($this->proposal->unique_hash, '127.0.0.1');
        $this->assertSame('VIEWED', $this->proposal->fresh()->status);
        $this->assertSame(1, Activity::where('subject_id', $this->proposal->id)->where('description', 'viewed_by_client')->count());
        app(RecordPublicProposalDecision::class)->execute($this->proposal->fresh(), true, $this->payload(true), '127.0.0.1');
        $before = $this->proposal->fresh()->getRawOriginal();
        $view->execute($this->proposal->unique_hash, '127.0.0.1');
        $this->assertSame($before, $this->proposal->fresh()->getRawOriginal());
        $this->assertSame(1, Activity::where('subject_id', $this->proposal->id)->where('description', 'viewed_by_client')->count());
    }

    public function test_public_view_veto_rolls_back_the_tracking_write(): void
    {
        $event = 'eloquent.saving: '.ISOActivityLog::class;
        $before = $this->proposal->fresh()->getRawOriginal();
        Event::listen($event, fn (): bool => false);
        try {
            $this->getJson(route('vap-proposals.public.show', $this->proposal->unique_hash))->assertStatus(409);
            $this->assertSame($before, $this->proposal->fresh()->getRawOriginal());
        } finally {
            Event::forget($event);
        }
    }

    public function test_public_view_rejects_invalid_customer_site_lineage(): void
    {
        $this->proposal->warehouse->update(['customer_id' => Customer::create(['name' => 'Another customer'])->id]);
        $this->getJson(route('vap-proposals.public.show', $this->proposal->unique_hash))->assertNotFound();
        $this->assertSame('SENT', $this->proposal->fresh()->status);
        $this->assertSame(0, Activity::where('subject_id', $this->proposal->id)->where('description', 'viewed_by_client')->count());
    }
}
