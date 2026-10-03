<?php

namespace Tests\Feature;

use App\Events\LaboratoryNotificationBroadcasted;
use App\Models\Customer;
use App\Models\Department;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\Warehouse;
use App\Notifications\OperationalNotification;
use App\Services\ProposalNotificationOwnership;
use App\Support\NotificationTemplateCatalog;
use App\Support\NotificationTemplateService;
use App\Support\ProposalWorkflowNotifier;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProposalNotificationOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private User $owner;

    private User $otherMember;

    private Customer $customer;

    private Warehouse $warehouse;

    private VAPProposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'database']);
        $this->lab = VAPLab::factory()->create();
        $this->owner = $this->member($this->lab);
        $this->otherMember = $this->member($this->lab);
        $this->customer = Customer::query()->create(['name' => fake()->company()]);
        $this->warehouse = Warehouse::query()->create([
            'name' => fake()->company(), 'email' => fake()->unique()->safeEmail(), 'customer_id' => $this->customer->id,
        ]);
        $this->proposal = $this->proposal($this->lab, $this->owner);
        foreach (['commercial.proposal.updated', 'commercial.proposal.sent_customer', 'commercial.proposal.compliance_acknowledged'] as $key) {
            $this->override($this->lab, $key);
        }
    }

    public function test_workflow_notifier_binds_the_canonical_proposal_for_owner_and_portal_delivery(): void
    {
        app(ProposalWorkflowNotifier::class)->notifySent($this->proposal);
        $this->assertSame(2, $this->jobs()->count());
        $this->deliver();
        $notices = DatabaseNotification::query()->whereIn('notifiable_type', [$this->owner->getMorphClass(), $this->warehouse->getMorphClass()])
            ->whereIn('notifiable_id', [$this->owner->id, $this->warehouse->id])->get();
        $this->assertCount(2, $notices);
        foreach ($notices as $notice) {
            $this->assertSame(app(ProposalNotificationOwnership::class)->context($this->proposal), collect($notice->data['context'])
                ->only(array_keys(app(ProposalNotificationOwnership::class)->context($this->proposal)))->all());
        }
    }

    public function test_shared_customer_and_site_can_receive_private_proposals_from_each_owning_lab(): void
    {
        $otherLab = VAPLab::factory()->create();
        $otherOwner = $this->member($otherLab);
        $peerProposal = $this->proposal($otherLab, $otherOwner);
        $this->override($otherLab, 'commercial.proposal.sent_customer');
        $this->override($otherLab, 'commercial.proposal.updated');
        request()->attributes->set('proposal_laboratory_id', $otherLab->id);
        try {
            app(ProposalWorkflowNotifier::class)->notifySent($this->proposal);
            app(ProposalWorkflowNotifier::class)->notifySent($peerProposal);
            $this->deliver();
        } finally {
            request()->attributes->remove('proposal_laboratory_id');
        }
        $this->assertSame([$this->lab->id, $otherLab->id], $this->warehouse->notifications()->get()
            ->pluck('data.context.lab_id')->sort()->values()->all());
        $this->assertSame(1, $this->owner->notifications()->count());
        $this->assertSame(1, $otherOwner->notifications()->count());
    }

    public function test_unrelated_users_and_sites_never_receive_a_proposal_at_enqueue_time(): void
    {
        $otherSite = Warehouse::query()->create(['name' => 'Other shared site', 'customer_id' => $this->customer->id]);
        $peer = $this->member(VAPLab::factory()->create());
        $sent = app(NotificationTemplateService::class)->notify([$this->otherMember, $peer, $otherSite], 'commercial.proposal.updated', $this->context());
        $this->assertSame(0, $sent);
        $this->assertSame(0, $this->jobs()->count());
    }

    #[DataProvider('sourceRevocations')]
    public function test_stale_or_archived_sources_are_suppressed_after_serialized_enqueue(string $change): void
    {
        app(ProposalWorkflowNotifier::class)->notifySent($this->proposal);
        $this->assertSame(2, $this->jobs()->count());
        $this->changeSource($change);
        $this->deliver();
        $this->assertSame(0, $this->owner->notifications()->count());
        $this->assertSame(0, $this->warehouse->notifications()->count());
    }

    /** @return array<string, array{string}> */
    public static function sourceRevocations(): array
    {
        return collect(['proposal', 'lab', 'customer', 'warehouse', 'site_customer', 'customer_and_site', 'token'])
            ->mapWithKeys(fn (string $change): array => [$change => [$change]])->all();
    }

    #[DataProvider('ownerRevocations')]
    public function test_staff_delivery_rechecks_exact_owner_and_live_membership(string $change): void
    {
        app(NotificationTemplateService::class)->notify([$this->owner], 'commercial.proposal.updated', $this->context());
        $this->assertSame(1, $this->jobs()->count());
        match ($change) {
            'owner' => $this->proposal->update(['user_id' => $this->otherMember->id]),
            'membership' => DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->owner->id)->delete(),
            'inactive' => $this->owner->update(['is_active' => false]),
            'unverified' => $this->owner->update(['email_verified_at' => null]),
        };
        $this->deliver();
        $this->assertSame(0, $this->owner->notifications()->count());
        $this->assertSame(0, $this->otherMember->notifications()->count());
    }

    /** @return array<string, array{string}> */
    public static function ownerRevocations(): array
    {
        return collect(['owner', 'membership', 'inactive', 'unverified'])
            ->mapWithKeys(fn (string $change): array => [$change => [$change]])->all();
    }

    #[DataProvider('invalidContexts')]
    public function test_missing_or_forged_source_context_never_enqueues_or_passes_delivery(array $changes): void
    {
        $context = [...$this->context(), ...$changes];
        $this->assertSame(0, app(NotificationTemplateService::class)->notify([$this->owner, $this->warehouse], 'commercial.proposal.updated', $context));
        $this->assertSame(0, $this->jobs()->count());
        $notice = new OperationalNotification(['key' => 'commercial.proposal.updated', 'context' => $context]);
        $this->assertFalse($notice->shouldSend($this->owner, 'database'));
        $this->assertFalse($notice->shouldSend($this->warehouse, 'mail'));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function invalidContexts(): array
    {
        return [
            'missing source' => [['proposal_id' => null]],
            'malformed source' => [['proposal_id' => 'invalid']],
            'missing owner' => [['lab_id' => null]],
            'malformed owner' => [['lab_id' => 'invalid']],
            'missing customer' => [['customer_id' => null]],
            'different customer' => [['customer_id' => PHP_INT_MAX]],
            'different site' => [['warehouse_id' => PHP_INT_MAX]],
            'different owner' => [['proposal_owner_id' => PHP_INT_MAX]],
            'different token' => [['proposal_hash' => 'forged']],
            'nested field' => [['proposal_hash' => ['forged']]],
        ];
    }

    public function test_peer_proposal_cannot_be_bound_to_the_wrong_lab(): void
    {
        $peerLab = VAPLab::factory()->create();
        $peerProposal = $this->proposal($peerLab, $this->member($peerLab));
        $context = [...$this->context(), 'proposal_id' => $peerProposal->id];
        $this->assertSame(0, app(NotificationTemplateService::class)->notify([$this->warehouse], 'commercial.proposal.sent_customer', $context));
        $notice = new OperationalNotification(['key' => 'commercial.proposal.sent_customer', 'context' => $context]);
        $this->assertFalse($notice->shouldSend($this->warehouse, 'mail'));
    }

    public function test_final_broadcast_rechecks_site_customer_binding_and_preserves_the_event_contract(): void
    {
        $notice = new OperationalNotification(['key' => 'commercial.proposal.updated', 'context' => $this->context()]);
        $notice->id = (string) Str::uuid();
        $event = new LaboratoryNotificationBroadcasted($this->warehouse, $notice, $notice->toDatabase($this->warehouse));
        $this->assertNotEmpty($event->broadcastOn());
        $this->changeSource('site_customer');
        $this->assertSame([], $event->broadcastOn());
    }

    public function test_serialized_final_broadcast_skips_the_transport_after_source_reassignment(): void
    {
        NotificationTemplate::query()->where('lab_id', $this->lab->id)->where('key', 'commercial.proposal.updated')
            ->firstOrFail()->update(['channels' => ['database', 'broadcast']]);
        $this->assertSame(1, app(NotificationTemplateService::class)->notify([$this->warehouse], 'commercial.proposal.updated', $this->context()));
        $this->assertSame(2, $this->jobs()->count());
        $this->deliver();
        $this->assertSame(1, $this->warehouse->notifications()->count());
        $this->assertSame(1, DB::table('jobs')->where('queue', 'broadcasts')->count());
        $this->changeSource('site_customer');
        $this->mock(BroadcastingFactory::class)->shouldNotReceive('connection');
        $job = Queue::connection('database')->pop('broadcasts');
        $this->assertInstanceOf(DatabaseJob::class, $job);
        $job->fire();
        $this->assertTrue($job->isDeleted());
        $this->assertSame(0, DB::table('jobs')->where('queue', 'broadcasts')->count());
    }

    public function test_unbound_legacy_context_and_archived_sources_cannot_enqueue(): void
    {
        $templates = app(NotificationTemplateService::class);
        $this->assertSame(0, $templates->notify([$this->warehouse], 'commercial.proposal.sent_customer', ['lab_id' => $this->lab->id]));
        $context = $this->context();
        $this->proposal->delete();
        $this->assertSame(0, $templates->notify([$this->warehouse], 'commercial.proposal.sent_customer', $context));
        $this->assertSame(0, $this->jobs()->count());
    }

    public function test_all_supported_proposal_notifier_events_bind_source_identity(): void
    {
        $notifier = app(ProposalWorkflowNotifier::class);
        foreach (['notifyRevised', 'notifyAccepted', 'notifyRejected'] as $method) {
            $notifier->{$method}($this->proposal);
            $this->assertSame(2, $this->jobs()->count());
            $this->deliver();
        }
        $this->assertSame(3, $this->owner->notifications()->count());
        $this->assertSame(3, $this->warehouse->notifications()->count());
    }

    private function member(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    private function proposal(VAPLab $lab, User $owner): VAPProposal
    {
        $template = VAPProposalTemplate::query()->create([
            'name' => 'Notification fixture', 'content' => '<p>Fixture</p>', 'user_id' => $owner->id, 'is_active' => true,
        ]);
        $proposal = new VAPProposal([
            'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id, 'user_id' => $owner->id,
            'department_id' => Department::factory()->create()->id, 'template_id' => $template->id,
            'unique_hash' => (string) Str::uuid(), 'status' => 'SENT', 'details' => ['fixture' => true],
        ]);
        $proposal->lab_id = $lab->id;
        $proposal->save();

        return $proposal;
    }

    private function override(VAPLab $lab, string $key): void
    {
        NotificationTemplate::factory()->create([
            'lab_id' => $lab->id, 'updated_by_id' => $this->owner->id, 'key' => $key,
            ...collect(app(NotificationTemplateCatalog::class)->definitions()[$key])->only(NotificationTemplate::EDITABLE_FIELDS)->all(),
            'channels' => ['database'],
        ]);
    }

    /** @return array<string, scalar|null> */
    private function context(): array
    {
        return [...app(ProposalNotificationOwnership::class)->context($this->proposal),
            'document_number' => $this->proposal->proposal_number, 'status' => 'enviada', 'detail' => 'Fixture',
            'document_url' => route('vap-proposals.public.show', $this->proposal->unique_hash),
        ];
    }

    private function changeSource(string $change): void
    {
        match ($change) {
            'proposal' => $this->proposal->delete(),
            'lab' => $this->lab->delete(),
            'customer' => $this->customer->delete(),
            'warehouse' => $this->warehouse->delete(),
            'site_customer' => $this->warehouse->update(['customer_id' => Customer::query()->create(['name' => 'Different customer'])->id]),
            'customer_and_site' => $this->replaceCustomerAndSite(),
            'token' => $this->proposal->update(['unique_hash' => (string) Str::uuid()]),
        };
    }

    private function replaceCustomerAndSite(): void
    {
        $customer = Customer::query()->create(['name' => 'Replacement customer']);
        $this->warehouse->update(['customer_id' => $customer->id]);
        $this->proposal->update(['customer_id' => $customer->id]);
    }

    private function jobs(): Builder
    {
        return DB::table('jobs')->where('queue', 'notifications');
    }

    private function deliver(): void
    {
        $count = 0;
        while ($job = Queue::connection('database')->pop('notifications')) {
            $this->assertInstanceOf(DatabaseJob::class, $job);
            $job->fire();
            $this->assertTrue($job->isDeleted());
            $this->assertLessThan(10, ++$count);
        }

        $this->assertGreaterThan(0, $count);
    }
}
