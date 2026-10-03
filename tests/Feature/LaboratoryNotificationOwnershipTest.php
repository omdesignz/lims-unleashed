<?php

namespace Tests\Feature;

use App\Events\AnalysisResultsInserted;
use App\Events\AnalysisResultsValidated;
use App\Events\CollectionProcessed;
use App\Events\CounterAnalysisResultsInserted;
use App\Events\LaboratoryNotificationBroadcasted;
use App\Jobs\CheckLaboratoryWorkflowStaleness;
use App\Jobs\CheckSampleRetentionDeadlines;
use App\Listeners\SendOperationalEventNotification;
use App\Models\CollectionProduct;
use App\Models\CounterAnalysis;
use App\Models\Customer;
use App\Models\LabCode;
use App\Models\LabNetwork;
use App\Models\NotificationTemplate;
use App\Models\Permission;
use App\Models\Result;
use App\Models\Role;
use App\Models\Sample;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Notifications\GlobalNotification;
use App\Notifications\LaboratoryBroadcastChannel;
use App\Notifications\OperationalNotification;
use App\Support\LaboratoryWorkflowNotifier;
use App\Support\NotificationTemplateService;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LaboratoryNotificationOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private VAPLab $peerLab;

    private User $sender;

    private User $local;

    private User $peer;

    private Warehouse $warehouse;

    private VAPSampleEntry $entry;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $network = LabNetwork::query()->create(['name' => 'Notification test network']);
        $this->lab = VAPLab::factory()->create(['network_id' => $network->id]);
        $this->peerLab = VAPLab::factory()->create(['network_id' => $network->id]);
        $network->update(['main_lab_id' => $this->peerLab->id]);
        $this->sender = $this->member($this->lab);
        $this->local = $this->member($this->lab);
        $this->peer = $this->member($this->peerLab);
        DB::table('lab_user')->where('lab_id', $this->peerLab->id)->where('user_id', $this->peer->id)
            ->update(['can_view_network' => true]);
        $customer = Customer::query()->create(['name' => 'Notification test customer']);
        $this->warehouse = Warehouse::query()->create([
            'name' => 'Notification test site', 'email' => fake()->unique()->safeEmail(), 'customer_id' => $customer->id,
        ]);
        $this->entry = VAPSampleEntry::factory()->create([
            'lab_id' => $this->lab->id, 'customer_id' => $customer->id, 'warehouse_id' => $this->warehouse->id,
            'received_by_id' => $this->local->id,
        ]);

        Notification::fake();
    }

    public function test_sample_alerts_exclude_peer_labs_and_ineligible_direct_receivers(): void
    {
        $removed = $this->member($this->lab);
        DB::table('lab_user')->where('user_id', $removed->id)->delete();
        $inactive = $this->member($this->lab);
        $inactive->update(['is_active' => false]);
        $unverified = $this->member($this->lab);
        $unverified->update(['email_verified_at' => null]);

        foreach ([$removed, $inactive, $unverified, $this->peer] as $receiver) {
            Cache::flush();
            $this->entry->update(['received_by_id' => $receiver->id]);
            app(LaboratoryWorkflowNotifier::class)->notifyStaleSample($this->entry, $this->sender);
            Notification::assertNotSentTo($receiver, OperationalNotification::class);
        }

        Notification::assertSentTo($this->local, OperationalNotification::class);
        Notification::assertSentTo($this->warehouse, OperationalNotification::class);
        Notification::assertNotSentTo($this->peer, OperationalNotification::class);
    }

    public function test_result_alerts_use_canonical_local_ownership_not_legacy_collection_id(): void
    {
        [$result] = $this->createResultFixture();
        $foreignProduct = CollectionProduct::query()->create();
        $result->update(['collection_id' => $foreignProduct->id]);

        app(LaboratoryWorkflowNotifier::class)->notifyStaleResult($result, 'verify', $this->sender);

        Notification::assertSentTo($this->local, OperationalNotification::class, fn (OperationalNotification $notice): bool => $notice->payload['context']['lab_id'] === $this->lab->id
            && $notice->payload['context']['notification_source_type'] === 'result'
            && $notice->payload['context']['notification_source_id'] === $result->id
        );
        Notification::assertSentTo($this->warehouse, OperationalNotification::class);
        Notification::assertNotSentTo($this->peer, OperationalNotification::class);
    }

    public function test_counter_analysis_alerts_remain_local(): void
    {
        [$result, $code] = $this->createResultFixture();
        $counter = CounterAnalysis::query()->create(['result_id' => $result->id, 'sample_id' => $result->sample_id, 'cl_id' => $code->id]);
        $notifier = app(LaboratoryWorkflowNotifier::class);

        $notifier->notifyCounterAnalysisRequested($result, $this->sender);
        $notifier->notifyStaleCounterAnalysis($counter, $this->sender);

        Notification::assertSentTo($this->local, OperationalNotification::class, fn (OperationalNotification $notice): bool => $notice->payload['key'] === 'lab.counter_analysis.requested');
        Notification::assertSentTo($this->local, OperationalNotification::class, fn (OperationalNotification $notice): bool => $notice->payload['key'] === 'lab.counter_analysis.stale');
        Notification::assertNotSentTo($this->peer, OperationalNotification::class);
    }

    #[DataProvider('invalidOwnership')]
    public function test_invalid_or_archived_result_sources_send_nothing(string $mutation): void
    {
        [$result, $code, $product] = $this->createResultFixture();
        if (in_array($mutation, ['foreign_archived_link', 'unassigned_archived_link'], true)) {
            DB::statement('SET CONSTRAINTS sample_entries_collection_product_unique DEFERRED');
        }

        match ($mutation) {
            'unassigned' => DB::table('sample_entries')->where('id', $this->entry->id)->update(['lab_id' => null]),
            'lab_deleted' => $this->lab->delete(),
            'entry_deleted' => $this->entry->delete(),
            'product_deleted' => $product->delete(),
            'code_deleted' => $code->delete(),
            'sample_deleted' => $result->sample->delete(),
            'result_deleted' => $result->delete(),
            'foreign_archived_link' => VAPSampleEntry::factory()->create([
                'lab_id' => $this->peerLab->id, 'customer_id' => $this->entry->customer_id, 'collection_product_id' => $product->id,
            ])->delete(),
            'unassigned_archived_link' => DB::table('sample_entries')->insert([
                'name' => 'Unassigned legacy sample', 'code' => 'LEGACY-UNASSIGNED-'.$product->id, 'collection_product_id' => $product->id, 'lab_id' => null, 'deleted_at' => now(),
            ]),
        };

        app(LaboratoryWorkflowNotifier::class)->notifyStaleResult($result, 'verify', $this->sender);
        Notification::assertNothingSent();
    }

    /** @return array<string, array{string}> */
    public static function invalidOwnership(): array
    {
        return collect(['unassigned', 'lab_deleted', 'entry_deleted', 'product_deleted', 'code_deleted', 'sample_deleted', 'result_deleted', 'foreign_archived_link', 'unassigned_archived_link'])
            ->mapWithKeys(fn (string $mutation): array => [$mutation => [$mutation]])->all();
    }

    public function test_ordinary_result_and_collection_events_use_local_permission_audiences(): void
    {
        [$result, $code, $product] = $this->createResultFixture();
        $listener = app(SendOperationalEventNotification::class);

        foreach ([
            new AnalysisResultsInserted($this->sender, $code),
            new CounterAnalysisResultsInserted($this->sender, $code),
            new AnalysisResultsValidated($result, $this->sender->id),
            new CollectionProcessed($this->sender, $this->entry->customer, $product->id),
        ] as $event) {
            $listener->handle($event);
        }

        foreach (['lab.results.inserted', 'lab.counter_results.inserted', 'lab.results.validated', 'lab.collection.processed'] as $key) {
            Notification::assertSentTo($this->local, OperationalNotification::class, fn (OperationalNotification $notice): bool => $notice->payload['key'] === $key);
        }
        Notification::assertNotSentTo($this->peer, OperationalNotification::class);
    }

    public function test_unscoped_or_forged_laboratory_context_fails_closed(): void
    {
        $templates = app(NotificationTemplateService::class);
        $this->assertSame(0, $templates->notify([$this->local], 'lab.sample.created', ['sample_code' => 'Unknown']));
        $this->assertSame(0, $templates->notify([$this->peer], 'lab.sample.created', ['sample_entry_id' => $this->entry->id, 'lab_id' => $this->peerLab->id]));
        app(SendOperationalEventNotification::class)->handle(new CollectionProcessed($this->sender, $this->entry->customer));
        Notification::assertNothingSent();
    }

    public function test_client_recipient_must_still_own_the_sample_site(): void
    {
        $otherCustomer = Customer::query()->create(['name' => 'Unrelated notification customer']);
        $this->warehouse->update(['customer_id' => $otherCustomer->id]);

        app(LaboratoryWorkflowNotifier::class)->notifyStaleSample($this->entry, $this->sender);

        Notification::assertNotSentTo($this->warehouse, OperationalNotification::class);
        Notification::assertSentTo($this->local, OperationalNotification::class);
    }

    public function test_deleting_an_unaccessioned_product_is_safe_and_blocks_new_source_notices(): void
    {
        $product = CollectionProduct::query()->create(['customer_id' => $this->entry->customer_id]);
        $this->entry->update(['collection_product_id' => $product->id]);
        $product->delete();

        $this->assertSoftDeleted($product);
        app(LaboratoryWorkflowNotifier::class)->notifyStaleSample($this->entry, $this->sender);
        Notification::assertNothingSent();
    }

    #[DataProvider('revokedRecipients')]
    public function test_queued_database_delivery_rechecks_recipient_eligibility(string $change): void
    {
        $notice = $this->sampleNotice();
        $job = new SendQueuedNotifications($this->local, $notice, ['database']);
        match ($change) {
            'membership_removed' => DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->local->id)->delete(),
            'inactive' => $this->local->update(['is_active' => false]),
            'unverified' => $this->local->update(['email_verified_at' => null]),
            'deleted' => $this->local->delete(),
        };
        Notification::swap(new ChannelManager($this->app));

        $job->handle(app(ChannelManager::class));

        $this->assertSame(0, $this->local->notifications()->count());
        $this->assertFalse($notice->shouldSend($this->local, 'mail'));
        $this->assertFalse($notice->shouldSend($this->local, 'broadcast'));
    }

    /** @return array<string, array{string}> */
    public static function revokedRecipients(): array
    {
        return collect(['membership_removed', 'inactive', 'unverified', 'deleted'])
            ->mapWithKeys(fn (string $change): array => [$change => [$change]])->all();
    }

    public function test_queued_delivery_rechecks_source_deletion(): void
    {
        $notice = $this->sampleNotice();
        $this->entry->delete();
        Notification::swap(new ChannelManager($this->app));

        (new SendQueuedNotifications($this->local, $notice, ['database']))->handle(app(ChannelManager::class));

        $this->assertSame(0, $this->local->notifications()->count());
    }

    public function test_queued_database_delivery_still_works_for_a_current_member(): void
    {
        $notice = $this->sampleNotice();
        $notice->id = (string) Str::uuid();
        Notification::swap(new ChannelManager($this->app));

        (new SendQueuedNotifications($this->local, $notice, ['database']))->handle(app(ChannelManager::class));

        $this->assertSame(1, $this->local->notifications()->count());
    }

    public function test_final_broadcast_job_rechecks_membership_and_keeps_the_existing_event_contract(): void
    {
        $notice = $this->sampleNotice();
        $notice->id = 'broadcast-ownership-test';
        $event = new LaboratoryNotificationBroadcasted($this->local, $notice, $notice->toDatabase($this->local));
        $this->assertSame('private-users.'.$this->local->id, $event->broadcastOn()[0]->name);
        $this->assertSame(BroadcastNotificationCreated::class, $event->broadcastAs());
        $this->assertSame(OperationalNotification::class, $event->broadcastWith()['type']);

        DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->local->id)->delete();
        $manager = \Mockery::mock(BroadcastingFactory::class);
        $manager->shouldNotReceive('connection');
        (new BroadcastEvent($event))->handle($manager);

        $this->assertSame([], $event->broadcastOn());
    }

    public function test_laboratory_broadcast_channel_dispatches_the_guarded_event(): void
    {
        Event::fake([LaboratoryNotificationBroadcasted::class]);
        $notice = $this->sampleNotice();

        app(LaboratoryBroadcastChannel::class)->send($this->local, $notice);

        Event::assertDispatched(LaboratoryNotificationBroadcasted::class, fn (LaboratoryNotificationBroadcasted $event): bool => $event->queue === 'broadcasts');
    }

    public function test_disabled_template_does_not_consume_the_notification_window(): void
    {
        $template = NotificationTemplate::factory()->create(['lab_id' => $this->lab->id, 'key' => 'lab.sample.stale', 'enabled' => false]);
        app(LaboratoryWorkflowNotifier::class)->notifyStaleSample($this->entry, $this->sender);
        Notification::assertNothingSent();

        $template->update(['enabled' => true]);
        app(LaboratoryWorkflowNotifier::class)->notifyStaleSample($this->entry, $this->sender);
        app(LaboratoryWorkflowNotifier::class)->notifyStaleSample($this->entry, $this->sender);

        Notification::assertSentToTimes($this->local, OperationalNotification::class, 1);
    }

    public function test_retention_job_does_not_notify_other_laboratories(): void
    {
        $this->entry->update(['retention_status' => 'active', 'retention_due_at' => now()->subDay()]);
        app(CheckSampleRetentionDeadlines::class)->handle(app(NotificationTemplateService::class));

        Notification::assertSentTo($this->local, OperationalNotification::class);
        Notification::assertNotSentTo($this->peer, OperationalNotification::class);
    }

    public function test_retention_job_can_notify_a_local_receiver_without_seeded_admin_roles(): void
    {
        Role::query()->where('name', 'admin')->delete();
        $this->entry->update(['retention_status' => 'active', 'retention_due_at' => now()->subDay()]);

        app(CheckSampleRetentionDeadlines::class)->handle(app(NotificationTemplateService::class));

        Notification::assertSentTo($this->local, OperationalNotification::class);
        Notification::assertNotSentTo($this->peer, OperationalNotification::class);
    }

    public function test_empty_recipient_attempt_does_not_suppress_a_later_eligible_alert(): void
    {
        $this->sender->update(['is_active' => false]);
        $this->local->update(['is_active' => false]);
        $this->warehouse->delete();
        app(LaboratoryWorkflowNotifier::class)->notifyStaleSample($this->entry, $this->sender);
        Notification::assertNothingSent();

        $this->local->update(['is_active' => true]);
        app(LaboratoryWorkflowNotifier::class)->notifyStaleSample($this->entry, $this->sender);
        Notification::assertSentToTimes($this->local, OperationalNotification::class, 1);
    }

    public function test_staleness_job_does_not_require_seeded_roles_or_an_inactive_sender(): void
    {
        Role::query()->where('name', 'admin')->delete();
        Permission::query()->where('name', 'view_analysis')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->sender->update(['is_active' => false]);
        $this->entry->forceFill(['updated_at' => now()->subDays(5)])->saveQuietly();

        app(CheckLaboratoryWorkflowStaleness::class)->handle(app(LaboratoryWorkflowNotifier::class));

        Notification::assertSentTo($this->local, OperationalNotification::class, fn (OperationalNotification $notice): bool => $notice->payload['context']['actor_name'] !== $this->sender->name);
    }

    public function test_stored_lab_alerts_are_hidden_from_inbox_and_shared_feed_after_membership_removal(): void
    {
        $localNotice = $this->storeNotice($this->local, $this->lab, 'Local private alert');
        $foreignNotice = $this->storeNotice($this->local, $this->peerLab, 'Foreign private alert');
        $publicNotice = $this->local->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => GlobalNotification::class,
            'data' => ['title' => 'Public system alert', 'message' => 'System maintenance'],
        ]);

        $this->actingAs($this->local)->getJson(route('notifications.index'))
            ->assertOk()->assertJsonPath('total', 2)->assertJsonMissing(['title' => 'Foreign private alert']);
        DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->local->id)->delete();

        $this->actingAs($this->local)->get(route('notifications.index'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('notifications', 1)->where('notifications.0.id', $publicNotice->id)
            ->has('auth.user.unread_notifications', 1)->where('pagination.total', 1));
        $this->post(route('notifications.read', $localNotice))->assertForbidden();
        $this->delete(route('notifications.delete', $foreignNotice))->assertForbidden();
        $this->assertModelExists($localNotice);
        $this->assertModelExists($foreignNotice);
    }

    public function test_explicitly_lab_owned_generic_and_broadcast_alerts_follow_membership(): void
    {
        $localOperational = $this->local->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => OperationalNotification::class,
            'data' => [
                'key' => 'quality.complaint.created', 'title' => 'Local quality alert',
                'context' => ['lab_id' => $this->lab->id],
            ],
        ]);
        $this->local->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => OperationalNotification::class,
            'data' => [
                'key' => 'quality.complaint.created', 'title' => 'Foreign quality alert',
                'context' => ['lab_id' => $this->peerLab->id],
            ],
        ]);
        $localBroadcast = $this->local->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => GlobalNotification::class,
            'data' => ['lab_id' => $this->lab->id, 'title' => 'Local broadcast'],
        ]);
        $this->local->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => GlobalNotification::class,
            'data' => ['lab_id' => $this->peerLab->id, 'title' => 'Foreign broadcast'],
        ]);
        $this->local->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => GlobalNotification::class,
            'data' => [
                'lab_id' => $this->lab->id,
                'title' => 'Conflicting ownership',
                'context' => ['lab_id' => $this->peerLab->id],
            ],
        ]);
        $personal = $this->local->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => GlobalNotification::class,
            'data' => ['title' => 'Personal system notice'],
        ]);

        $this->actingAs($this->local)->getJson(route('notifications.index'))
            ->assertOk()->assertJsonPath('total', 3)
            ->assertJsonMissing(['title' => 'Foreign quality alert'])
            ->assertJsonMissing(['title' => 'Foreign broadcast'])
            ->assertJsonMissing(['title' => 'Conflicting ownership']);

        DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->local->id)->delete();
        $this->get(route('notifications.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('notifications', 1)->where('notifications.0.id', $personal->id));
        $this->post(route('notifications.read', $localOperational))->assertForbidden();
        $this->delete(route('notifications.delete', $localBroadcast))->assertForbidden();
    }

    public function test_generic_lab_context_limits_direct_and_permission_based_delivery(): void
    {
        $templates = app(NotificationTemplateService::class);

        $sent = $templates->notify([$this->local, $this->peer], 'quality.complaint.created', [
            'lab_id' => $this->lab->id,
            'document_number' => 'CMP-LOCAL',
            'severity' => 'high',
            'document_url' => route('complaints.index'),
        ]);

        $this->assertSame(1, $sent);
        Notification::assertSentTo($this->local, OperationalNotification::class, fn (OperationalNotification $notice): bool => $notice->payload['key'] === 'quality.complaint.created'
            && $notice->payload['context']['lab_id'] === $this->lab->id);
        Notification::assertNotSentTo($this->peer, OperationalNotification::class);

        Notification::fake();
        $this->local->givePermissionTo(Permission::findOrCreate('view_inventory', 'web'));
        $this->peer->givePermissionTo(Permission::findOrCreate('view_inventory', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $sent = $templates->notifyPermission('inventory.low_stock', [
            'lab_id' => $this->lab->id,
            'item_name' => 'Local reagent',
            'quantity' => 1,
            'minimum' => 2,
            'document_url' => route('inventory.index'),
        ], $this->sender->id);

        $this->assertSame(1, $sent);
        Notification::assertSentTo($this->local, OperationalNotification::class);
        Notification::assertNotSentTo($this->peer, OperationalNotification::class);
        $this->assertSame(0, $templates->notify([$this->local], 'quality.complaint.created', ['lab_id' => -1]));
    }

    public function test_admin_notification_lists_details_statistics_and_exports_cannot_reveal_another_lab(): void
    {
        $localNotice = $this->storeNotice($this->local, $this->lab, 'Local private alert');
        $foreignNotice = $this->storeNotice($this->peer, $this->peerLab, 'Foreign private alert');

        $this->actingAs($this->local)->withSession(['active_lab_id' => $this->peerLab->id])
            ->get(route('admin.notifications.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('notifications.data', 1)->where('notifications.data.0.id', $localNotice->id));
        $this->get(route('admin.notifications.show', $foreignNotice->id))->assertNotFound();
        $this->get(route('admin.notifications.show', $localNotice->id))->assertOk();
        $this->get(route('admin.notifications.dashboard'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('stats.total', 1));
        $this->get(route('admin.notifications.analytics'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('stats.total_sent', 1));
        $export = $this->get(route('admin.notifications.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Local private alert', $export);
        $this->assertStringNotContainsString('Foreign private alert', $export);
        $this->get(route('admin.notifications.index', ['search' => 'Foreign private alert']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('notifications.data', 0)->where('notifications.total', 0));
    }

    public function test_unowned_legacy_laboratory_notices_remain_stored_but_are_not_exposed(): void
    {
        $notice = $this->local->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => OperationalNotification::class,
            'data' => ['key' => 'lab.sample.stale', 'title' => 'Legacy private alert', 'context' => []],
        ]);

        $this->actingAs($this->local)->getJson(route('notifications.index'))->assertOk()->assertJsonPath('total', 0);
        $this->assertModelExists($notice);
    }

    private function storeNotice(User $recipient, VAPLab $lab, string $title): DatabaseNotification
    {
        return $recipient->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => OperationalNotification::class,
            'data' => [
                'key' => 'lab.sample.stale', 'category' => 'laboratory', 'title' => $title, 'message' => $title,
                'sender_id' => $this->sender->id, 'context' => ['lab_id' => $lab->id],
            ],
        ]);
    }

    private function member(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        foreach (['view_analysis', 'verify_results', 'approve_results', 'insert_results', 'view_counter_analysis', 'view_quality_certificates'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    /** @return array{Result, LabCode, CollectionProduct} */
    private function createResultFixture(): array
    {
        $product = CollectionProduct::query()->create(['customer_id' => $this->entry->customer_id, 'warehouse_id' => $this->warehouse->id]);
        $this->entry->update(['collection_product_id' => $product->id]);
        $code = LabCode::query()->create(['collection_id' => $product->id, 'cl_month' => now()->format('y/m')]);
        $sample = Sample::query()->create(['cl_id' => $code->id, 'sample_month' => now()->format('y/m')]);
        $result = Result::query()->create(['sample_id' => $sample->id, 'code_id' => $code->id, 'code_label' => $sample->code]);

        return [$result, $code, $product];
    }

    private function sampleNotice(): OperationalNotification
    {
        app(NotificationTemplateService::class)->notify([$this->local], 'lab.sample.created', ['sample_entry_id' => $this->entry->id, 'sample_code' => $this->entry->code]);

        return Notification::sent($this->local, OperationalNotification::class)->first();
    }
}
