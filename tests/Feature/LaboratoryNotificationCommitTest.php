<?php

namespace Tests\Feature;

use App\Actions\IssueLaboratoryNotification;
use App\Actions\RecordPublicProposalDecision;
use App\Actions\ReviseProposal;
use App\Actions\SendProposal;
use App\Models\BroadcastNotification;
use App\Models\Customer;
use App\Models\Department;
use App\Models\InventorySupplierAssessment;
use App\Models\NotificationTemplate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Notifications\GlobalNotification;
use App\Services\ProposalNotificationOwnership;
use App\Support\LaboratoryWorkflowNotifier;
use App\Support\NotificationTemplateCatalog;
use App\Support\NotificationTemplateService;
use App\Support\ProposalWorkflowNotifier;
use App\Support\ReportStudioPdfRenderer;
use App\Support\SupplierAssessmentNotifier;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use Throwable;

class LaboratoryNotificationCommitTest extends TestCase
{
    private ?string $schema = null;

    /** @var array<string, mixed> */
    private array $originalConnection = [];

    private VAPLab $lab;

    private User $sender;

    private User $recipient;

    private User $peer;

    private VAPSampleEntry $entry;

    private NotificationTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = DB::connection();
        $this->assertSame('pgsql', $connection->getDriverName());
        $this->assertSame('lims_unleashed_test', $connection->getDatabaseName());
        $this->assertSame(0, $connection->transactionLevel(), 'These tests require real root commits, not DatabaseTransactions.');
        $this->originalConnection = config('database.connections.pgsql');
        $this->schema = 'notification_commit_'.bin2hex(random_bytes(8));
        DB::statement('CREATE SCHEMA "'.$this->schema.'"');
        try {
            config([
                'database.connections.pgsql.search_path' => $this->schema,
                'queue.default' => 'database',
                'cache.default' => 'array',
                'mail.default' => 'array',
            ]);
            DB::purge('pgsql');
            $this->assertSame(0, Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]), Artisan::output());
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->lab = VAPLab::factory()->create();
            $this->sender = $this->member($this->lab);
            $this->recipient = $this->member($this->lab);
            $this->peer = $this->member(VAPLab::factory()->create());
            $permission = Permission::findOrCreate('view_analysis', 'web');
            $this->recipient->givePermissionTo($permission);
            $this->peer->givePermissionTo($permission);
            $this->entry = VAPSampleEntry::factory()->create([
                'lab_id' => $this->lab->id, 'received_by_id' => $this->recipient->id,
            ]);
            $this->template = $this->override('lab.sample.stale');
        } catch (Throwable $exception) {
            $this->removeSchema();
            throw $exception;
        }
    }

    protected function tearDown(): void
    {
        try {
            $this->removeSchema();
        } finally {
            parent::tearDown();
        }
    }

    private function removeSchema(): void
    {
        if ($this->schema === null) {
            return;
        }
        while (DB::connection()->transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::statement('DROP SCHEMA "'.$this->schema.'" CASCADE');
        config(['database.connections.pgsql' => $this->originalConnection]);
        DB::purge('pgsql');
        $this->schema = null;
    }

    #[DataProvider('nestedOutcomes')]
    public function test_manual_issuance_dispatch_waits_for_the_root_transaction(bool $rollback): void
    {
        $this->sender->assignRole(Role::findOrCreate('admin', 'web'));
        DB::beginTransaction();
        $result = app(IssueLaboratoryNotification::class)->execute($this->sender->id, $this->lab->id, $this->manualNotificationPayload());
        $this->assertModelExists($result['notification']);
        $this->assertSame(0, DB::table('jobs')->count());
        $rollback ? DB::rollBack() : DB::commit();

        $this->assertSame($rollback ? 0 : 1, BroadcastNotification::query()->count());
        $this->assertSame($rollback ? 0 : 2, DB::table('jobs')->where('queue', 'notifications')->count());
        if (! $rollback) {
            $this->deliverInFreshWorker(2);
            $notice = DatabaseNotification::query()->sole();
            $this->assertSame($this->recipient->id, (int) $notice->notifiable_id);
            $this->assertSame($this->lab->id, $notice->data['lab_id']);
            $this->assertSame('Manual fixture notification', $notice->data['title']);
        }
    }

    #[DataProvider('manualQueueFailures')]
    public function test_manual_queue_failure_preserves_issuance_and_returns_an_honest_warning(int $failingInsert): void
    {
        $this->sender->assignRole(Role::findOrCreate('admin', 'web'));
        Exceptions::fake();
        $inserts = 0;
        DB::connection()->beforeExecuting(function (string $query) use (&$inserts, $failingInsert): void {
            if (str_starts_with($query, 'insert into "jobs"') && ++$inserts === $failingInsert) {
                throw new RuntimeException('Manual notification queue unavailable');
            }
        });

        $this->actingAs($this->sender)->withSession(['active_lab_id' => $this->lab->id])
            ->post(route('admin.notifications.store'), $this->manualNotificationPayload())
            ->assertRedirect(route('admin.notifications.index'))
            ->assertSessionHas('toast.type', 'warning')
            ->assertSessionHas('toast.title', 'Emissão registada; envio por confirmar');

        $this->assertSame(0, DB::connection()->transactionLevel());
        $this->assertSame(1, BroadcastNotification::query()->count());
        $this->assertSame($failingInsert - 1, DB::table('jobs')->count());
        $this->assertSame(0, DatabaseNotification::query()->count());
        Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Manual notification queue unavailable');
    }

    /** @return array<string, array{int}> */
    public static function manualQueueFailures(): array
    {
        return ['before first channel' => [1], 'after one channel' => [2]];
    }

    public function test_manual_issuance_does_not_dispatch_if_sender_loses_authority_before_outer_commit(): void
    {
        $this->sender->assignRole(Role::findOrCreate('admin', 'web'));
        Exceptions::fake();
        DB::beginTransaction();
        app(IssueLaboratoryNotification::class)->execute($this->sender->id, $this->lab->id, $this->manualNotificationPayload());
        DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->sender->id)->delete();
        DB::commit();

        $this->assertSame(1, BroadcastNotification::query()->count());
        $this->assertSame(0, DB::table('jobs')->count());
        Exceptions::assertNotReported(AuthorizationException::class);
    }

    /** @return array<string, mixed> */
    private function manualNotificationPayload(): array
    {
        return ['title' => 'Manual fixture notification', 'message' => 'Local isolated delivery.', 'type' => 'info',
            'priority' => 'normal', 'recipient_type' => 'specific', 'recipients' => [$this->recipient->id]];
    }

    public function test_rollback_does_not_claim_the_window_and_a_committed_retry_delivers_once_in_a_fresh_worker(): void
    {
        DB::beginTransaction();
        $this->notifySample();
        $this->assertNotClaimed();
        DB::rollBack();
        $this->assertNotClaimed();

        DB::beginTransaction();
        $this->notifySample();
        $this->notifySample();
        $this->assertNotClaimed();
        DB::commit();

        $this->assertTrue(Cache::has($this->sampleCacheKey()));
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame(0, DatabaseNotification::query()->count());
        $this->deliverInFreshWorker();

        $notification = DatabaseNotification::query()->sole();
        $this->assertSame($this->recipient->id, (int) $notification->notifiable_id);
        $this->assertSame($this->recipient->getMorphClass(), $notification->notifiable_type);
        $this->assertSame('lab.sample.stale', $notification->data['key']);
        $this->assertSame($this->lab->id, $notification->data['context']['lab_id']);
        $this->assertSame($this->entry->id, $notification->data['context']['sample_entry_id']);
        $this->assertSame(0, DB::table('jobs')->count());
    }

    #[DataProvider('nestedOutcomes')]
    public function test_nested_callbacks_wait_for_root_commit_and_rolled_back_callbacks_are_discarded(bool $rollback): void
    {
        DB::beginTransaction();
        DB::beginTransaction();
        $this->notifySample();
        $rollback ? DB::rollBack() : DB::commit();
        $this->assertNotClaimed();
        if ($rollback) {
            $this->notifySample();
        }
        $this->assertNotClaimed();
        DB::commit();
        $this->assertSame(1, DB::table('jobs')->count());
        $this->deliver('notifications');
        $this->assertSame(1, DatabaseNotification::query()->count());
    }

    /** @return array<string, array{bool}> */
    public static function nestedOutcomes(): array
    {
        return ['inner commit' => [false], 'inner rollback' => [true]];
    }

    public function test_enqueue_failure_releases_the_claim_for_a_valid_retry(): void
    {
        $failNextInsert = true;
        DB::connection()->beforeExecuting(function (string $query) use (&$failNextInsert): void {
            if ($failNextInsert && str_starts_with($query, 'insert into "jobs"')) {
                $failNextInsert = false;
                throw new RuntimeException('Simulated queue insertion failure');
            }
        });
        DB::beginTransaction();
        $this->notifySample();
        try {
            DB::commit();
            $this->fail('Queue insertion must fail after the root transaction commits.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated queue insertion failure', $exception->getMessage());
        }
        $this->assertSame(0, DB::connection()->transactionLevel());
        $this->assertNotClaimed();
        $this->notifySample();
        $this->deliver('notifications');
        $this->assertSame(1, DatabaseNotification::query()->count());
    }

    public function test_disabled_or_muted_templates_do_not_claim_the_window(): void
    {
        $this->template->update(['enabled' => false]);
        DB::transaction(fn () => $this->notifySample());
        $this->assertNotClaimed();
        $this->template->update(['enabled' => true]);
        $preference = $this->recipient->notificationPreferences()->create([
            'category' => 'laboratory', 'database_enabled' => false,
            'broadcast_enabled' => false, 'mail_enabled' => false,
        ]);
        DB::transaction(fn () => $this->notifySample());
        $this->assertNotClaimed();
        $preference->update(['database_enabled' => true]);
        $this->notifySample();
        $this->deliver('notifications');
        $this->assertSame(1, DatabaseNotification::query()->count());
    }

    #[DataProvider('deliveryRevocations')]
    public function test_serialized_sample_delivery_rechecks_the_source_and_recipient(string $revocation): void
    {
        $this->notifySample();
        $this->assertSame(1, DB::table('jobs')->count());
        $this->revoke($revocation);
        $this->deliverInFreshWorker();
        $this->assertSame(0, DatabaseNotification::query()->count());
        $this->assertSame(0, DB::table('jobs')->count());
    }

    /** @return array<string, array{string}> */
    public static function deliveryRevocations(): array
    {
        return collect(['membership', 'inactive', 'unverified', 'lab', 'source'])
            ->mapWithKeys(fn (string $revocation): array => [$revocation => [$revocation]])->all();
    }

    public function test_supplier_alert_rollback_and_disabled_override_leave_a_retry_available(): void
    {
        $permission = Permission::findOrCreate('view_isuppliers', 'web');
        $this->recipient->givePermissionTo($permission);
        $this->peer->givePermissionTo($permission);
        $assessment = InventorySupplierAssessment::factory()->create([
            'lab_id' => $this->lab->id, 'assessed_by_user_id' => $this->sender->id,
        ]);
        $template = $this->override('inventory.supplier_assessment');
        $key = 'supplier-assessment-notification:lab:'.$this->lab->id.':supplier-assessment-due-soon:'.$assessment->id.':'.now()->format('Ymd');
        $notifier = app(SupplierAssessmentNotifier::class);
        DB::beginTransaction();
        $notifier->notifyDueSoon($assessment);
        DB::rollBack();
        $this->assertFalse(Cache::has($key));
        $this->assertSame(0, DB::table('jobs')->count());
        $template->update(['enabled' => false]);
        DB::transaction(fn () => $notifier->notifyDueSoon($assessment));
        $this->assertFalse(Cache::has($key));
        $this->assertSame(0, DB::table('jobs')->count());
        $template->update(['enabled' => true]);
        DB::transaction(function () use ($notifier, $assessment): void {
            $notifier->notifyDueSoon($assessment);
            $notifier->notifyDueSoon($assessment);
        });
        $this->assertTrue(Cache::has($key));
        $this->assertSame(1, DB::table('jobs')->count());
        $this->deliver('notifications');
        $notification = DatabaseNotification::query()->sole();
        $this->assertSame($this->recipient->id, (int) $notification->notifiable_id);
        $this->assertSame($this->lab->id, $notification->data['context']['lab_id']);
        $this->assertSame('Sistema', $notification->data['context']['actor_name']);
    }

    #[DataProvider('genericNotifications')]
    public function test_lab_tagged_staff_delivery_is_suppressed_after_membership_removal(string $kind): void
    {
        $this->enqueueGeneric($kind);
        $this->revoke('membership');
        $this->deliver('notifications');
        $this->assertSame(0, DatabaseNotification::query()->count());
        $this->assertSame(0, DB::table('jobs')->count());
    }

    #[DataProvider('genericNotifications')]
    public function test_final_broadcast_worker_rechecks_membership_without_calling_the_transport(string $kind): void
    {
        $this->enqueueGeneric($kind);
        $this->deliver('notifications');
        $this->assertSame(1, DatabaseNotification::query()->count());
        $this->assertSame(1, DB::table('jobs')->where('queue', 'broadcasts')->count());
        $this->revoke('membership');
        $this->mock(BroadcastingFactory::class)->shouldNotReceive('connection');
        $this->deliver('broadcasts');
        $this->assertSame(0, DB::table('jobs')->count());
    }

    /** @return array<string, array{string}> */
    public static function genericNotifications(): array
    {
        return ['manual lab alert' => ['global'], 'quality alert' => ['operational']];
    }

    #[DataProvider('proposalDeliveryOutcomes')]
    public function test_proposal_portal_delivery_waits_for_a_real_commit_and_rechecks_source_in_a_fresh_worker(bool $reassignSite): void
    {
        $customer = Customer::query()->create(['name' => 'Proposal worker customer']);
        $warehouse = Warehouse::query()->create([
            'name' => 'Proposal worker site', 'customer_id' => $customer->id, 'email' => fake()->unique()->safeEmail(),
        ]);
        $template = VAPProposalTemplate::query()->create([
            'name' => 'Proposal worker template', 'content' => '<p>Fixture</p>', 'user_id' => $this->sender->id, 'is_active' => true,
        ]);
        $proposal = new VAPProposal([
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id, 'user_id' => $this->sender->id,
            'department_id' => Department::factory()->create()->id, 'template_id' => $template->id,
            'status' => 'SENT', 'unique_hash' => (string) str()->uuid(), 'details' => ['fixture' => true],
        ]);
        $proposal->lab_id = $this->lab->id;
        $proposal->save();
        $this->override('commercial.proposal.sent_customer');
        DB::beginTransaction();
        $this->assertSame(1, app(NotificationTemplateService::class)->notify([$warehouse], 'commercial.proposal.sent_customer', [
            ...app(ProposalNotificationOwnership::class)->context($proposal),
            'document_number' => $proposal->proposal_number, 'document_url' => route('vap-proposals.public.show', $proposal->unique_hash),
        ]));
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DatabaseNotification::query()->count());
        DB::commit();
        $this->assertSame(1, DB::table('jobs')->count());
        if ($reassignSite) {
            $warehouse->update(['customer_id' => Customer::query()->create(['name' => 'Different customer'])->id]);
        }
        $this->deliverInFreshWorker();
        $this->assertSame($reassignSite ? 0 : 1, DatabaseNotification::query()->count());
        if (! $reassignSite) {
            $notice = DatabaseNotification::query()->sole();
            $this->assertSame($warehouse->getMorphClass(), $notice->notifiable_type);
            $this->assertSame($warehouse->id, (int) $notice->notifiable_id);
            $this->assertSame($proposal->id, $notice->data['context']['proposal_id']);
            $this->assertSame($this->lab->id, $notice->data['context']['lab_id']);
        }
        $this->assertSame(0, DB::table('jobs')->count());
    }

    /** @return array<string, array{bool}> */
    public static function proposalDeliveryOutcomes(): array
    {
        return ['current recipient' => [false], 'reassigned site' => [true]];
    }

    #[DataProvider('publicDecisionOutcomes')]
    public function test_public_decision_notifications_wait_for_the_root_commit_and_are_discarded_on_rollback(bool $accepted, bool $rollback): void
    {
        $proposal = $this->publicDecisionProposal();
        $this->override('commercial.proposal.updated');
        DB::beginTransaction();
        app(RecordPublicProposalDecision::class)->execute($proposal, $accepted, $this->decisionPayload($accepted), '127.0.0.8');
        $this->assertSame(1, DB::connection()->transactionLevel());
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DatabaseNotification::count());
        if ($rollback) {
            DB::rollBack();
            $this->assertSame('SENT', $proposal->fresh()->status);
            $this->assertSame(0, $proposal->complianceAgreement()->count());
            $this->assertSame(0, $proposal->complianceAgreementLogs()->count());
            $this->assertSame(0, DB::table('jobs')->count());
        } else {
            DB::commit();
            $this->assertSame($accepted ? 'ACCEPTED' : 'REJECTED', $proposal->fresh()->status);
            $this->assertSame(2, DB::table('jobs')->count());
            $this->deliverInFreshWorker(2);
            $notices = DatabaseNotification::all();
            $this->assertCount(2, $notices);
            $this->assertTrue($notices->contains(fn (DatabaseNotification $notice): bool => $notice->notifiable_type === $this->sender->getMorphClass() && (int) $notice->notifiable_id === $this->sender->id));
            $this->assertTrue($notices->contains(fn (DatabaseNotification $notice): bool => $notice->notifiable_type === $proposal->warehouse->getMorphClass() && (int) $notice->notifiable_id === $proposal->warehouse_id));
            foreach ($notices as $notice) {
                $this->assertSame($proposal->id, $notice->data['context']['proposal_id']);
                $this->assertSame($this->lab->id, $notice->data['context']['lab_id']);
            }
        }
        $this->assertSame(0, DB::connection()->transactionLevel());
    }

    public static function publicDecisionOutcomes(): array
    {
        return [[true, true], [true, false], [false, true], [false, false]];
    }

    #[DataProvider('decisionKinds')]
    public function test_postcommit_notification_failure_does_not_undo_or_misreport_a_recorded_decision(bool $accepted): void
    {
        $proposal = $this->publicDecisionProposal();
        Exceptions::fake();
        $notifier = $this->mock(ProposalWorkflowNotifier::class);
        $notifier->shouldReceive($accepted ? 'notifyAccepted' : 'notifyRejected')->once()
            ->andThrow(new RuntimeException('Postcommit decision notification failure'));
        $response = $this->postJson(route('proposals.api.'.($accepted ? 'accept' : 'reject'), $proposal->unique_hash), $this->decisionPayload($accepted));
        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame($accepted ? 'ACCEPTED' : 'REJECTED', $proposal->fresh()->status);
        $this->assertSame(1, $proposal->complianceAgreement()->count());
        $this->assertSame($accepted ? 1 : 0, $proposal->complianceAgreementLogs()->count());
        $this->assertSame(0, DB::connection()->transactionLevel());
        Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Postcommit decision notification failure');
    }

    public static function decisionKinds(): array
    {
        return ['acceptance' => [true], 'rejection' => [false]];
    }

    public function test_public_decision_callback_rechecks_the_token_before_enqueueing(): void
    {
        $proposal = $this->publicDecisionProposal();
        $this->override('commercial.proposal.updated');
        DB::beginTransaction();
        app(RecordPublicProposalDecision::class)->execute($proposal, true, $this->decisionPayload(true), '127.0.0.8');
        DB::table('proposals')->where('id', $proposal->id)->update(['unique_hash' => (string) str()->uuid()]);
        DB::commit();
        $this->assertSame('ACCEPTED', $proposal->fresh()->status);
        $this->assertSame(1, $proposal->complianceAgreementLogs()->count());
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DatabaseNotification::count());
    }

    private function decisionPayload(bool $accepted): array
    {
        return $accepted ? ['confidentiality' => true, 'impartiality' => false, 'nondisclosure' => true]
            : ['reason' => 'Private client rejection reason'];
    }

    #[DataProvider('authoringDepths')]
    public function test_sent_child_transactions_remove_only_their_artifact_on_ancestor_rollback(int $depth, bool $rollbackRoot): void
    {
        $proposal = $this->authoringProposal();
        $previousPath = $proposal->file_path;
        $auditCount = DB::table('activity_log')->count();
        $notifier = $this->mock(ProposalWorkflowNotifier::class);
        $notifier->shouldNotReceive('notifySent');
        for ($level = 0; $level < $depth; $level++) {
            DB::beginTransaction();
        }
        $sent = app(SendProposal::class)->execute($this->lab->id, $this->sender->id, $proposal);
        $this->assertNotSame($previousPath, $sent->file_path);
        Storage::assertExists($sent->file_path);
        Storage::assertExists($previousPath);
        while (DB::connection()->transactionLevel() > ($rollbackRoot ? 1 : 2)) {
            DB::commit();
        }
        DB::rollBack();
        if (! $rollbackRoot) {
            DB::commit();
        }
        $this->assertSame('PENDING', $proposal->fresh()->status);
        $this->assertSame($previousPath, $proposal->fresh()->file_path);
        $this->assertSame($auditCount, DB::table('activity_log')->count());
        $this->assertSame([$previousPath], Storage::allFiles());
        $this->assertSame(0, DB::connection()->transactionLevel());
    }

    public static function authoringDepths(): array
    {
        return ['root' => [1, true], 'three levels then root' => [3, true], 'three levels then inner' => [3, false]];
    }

    #[DataProvider('decisionKinds')]
    public function test_send_notifies_and_retires_the_old_pdf_only_after_root_commit(bool $notificationFails): void
    {
        $proposal = $this->authoringProposal();
        $previousPath = $proposal->file_path;
        $calls = 0;
        Exceptions::fake();
        $notifier = $this->mock(ProposalWorkflowNotifier::class);
        $notifier->shouldReceive('notifySent')->once()->andReturnUsing(function (VAPProposal $current) use (&$calls, $notificationFails): void {
            $calls++;
            $this->assertSame(0, DB::connection()->transactionLevel());
            $this->assertSame('SENT', $current->fresh()->status);
            if ($notificationFails) {
                throw new RuntimeException('Staff postcommit notification failure');
            }
        });
        DB::beginTransaction();
        DB::beginTransaction();
        $sent = app(SendProposal::class)->execute($this->lab->id, $this->sender->id, $proposal);
        DB::commit();
        $this->assertSame(0, $calls);
        Storage::assertExists($previousPath);
        Storage::assertExists($sent->file_path);
        DB::commit();
        $this->assertSame(1, $calls);
        $this->assertSame('SENT', $proposal->fresh()->status);
        $this->assertSame([$sent->file_path], Storage::allFiles());
        if ($notificationFails) {
            Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Staff postcommit notification failure');
        } else {
            Exceptions::assertNothingReported();
        }
    }

    public function test_revision_rollback_preserves_items_consent_and_the_old_pdf(): void
    {
        $proposal = $this->authoringProposal(false);
        $unit = Unit::create(['code' => 'root-test', 'description' => 'Root test']);
        $item = $proposal->items()->create(['item_description' => 'Original', 'qty' => 1, 'unit_price' => 1, 'total' => 1, 'unit_id' => $unit->id]);
        $proposal->complianceAgreement()->create(['confidentiality' => true, 'nondisclosure' => true, 'impartiality' => false]);
        $before = $proposal->fresh()->getRawOriginal();
        $agreement = $proposal->complianceAgreement()->sole()->getRawOriginal();
        $auditCount = DB::table('activity_log')->count();
        $this->mock(ProposalWorkflowNotifier::class)->shouldNotReceive('notifyRevised');
        DB::beginTransaction();
        DB::beginTransaction();
        app(ReviseProposal::class)->execute($this->lab->id, $this->sender->id, $proposal, [
            'service_location' => 'Changed', 'revision_reason' => 'A recorded correction.', 'tolerance_days' => 30,
            'items' => [['item_description' => 'Replacement', 'qty' => 2, 'unit_price' => 1, 'unit_id' => $unit->id]],
        ]);
        DB::commit();
        Storage::assertExists($proposal->file_path);
        DB::rollBack();
        $this->assertSame($before, $proposal->fresh()->getRawOriginal());
        $this->assertSame([$item->id], $proposal->items()->pluck('id')->all());
        $this->assertSame($agreement, $proposal->complianceAgreement()->sole()->getRawOriginal());
        $this->assertSame($auditCount, DB::table('activity_log')->count());
        $this->assertSame([$proposal->file_path], Storage::allFiles());
    }

    private function authoringProposal(bool $renders = true): VAPProposal
    {
        Storage::fake(config('filesystems.default', 'local'));
        $this->sender->givePermissionTo(Permission::findOrCreate('edit_proposals', 'web'));
        $proposal = $this->publicDecisionProposal();
        $proposal->update(['status' => 'PENDING', 'file_path' => "vap-proposals/{$proposal->id}/retained.pdf"]);
        Storage::put($proposal->file_path, 'Retained PDF');
        if ($renders) {
            $this->mock(ReportStudioPdfRenderer::class)->shouldReceive('renderDocument')->once()->andReturn(['content' => '%PDF-root', 'renderer' => 'test']);
        }

        return $proposal->fresh();
    }

    private function publicDecisionProposal(): VAPProposal
    {
        $customer = Customer::create(['name' => 'Public decision customer']);
        $warehouse = Warehouse::create(['name' => 'Public decision site', 'customer_id' => $customer->id, 'email' => fake()->unique()->safeEmail()]);
        $template = VAPProposalTemplate::create(['name' => 'Public decision template', 'content' => '<p>Fixture</p>', 'user_id' => $this->sender->id]);
        $proposal = new VAPProposal([
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id, 'user_id' => $this->sender->id,
            'department_id' => Department::factory()->create()->id, 'template_id' => $template->id,
            'status' => 'SENT', 'unique_hash' => (string) str()->uuid(), 'details' => ['fixture' => true],
        ]);
        $proposal->lab_id = $this->lab->id;
        $proposal->save();

        return $proposal;
    }

    private function member(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    private function override(string $key): NotificationTemplate
    {
        return NotificationTemplate::factory()->create([
            'lab_id' => $this->lab->id, 'updated_by_id' => $this->sender->id, 'key' => $key,
            ...collect(app(NotificationTemplateCatalog::class)->definitions()[$key])->only(NotificationTemplate::EDITABLE_FIELDS)->all(),
            'channels' => ['database'],
        ]);
    }

    private function sampleCacheKey(): string
    {
        return 'workflow-notification:lab:'.$this->lab->id.':stale-sample:'.$this->entry->id.':'.now()->format('Ymd');
    }

    private function notifySample(): void
    {
        app(LaboratoryWorkflowNotifier::class)->notifyStaleSample($this->entry, $this->sender);
    }

    private function assertNotClaimed(): void
    {
        $this->assertFalse(Cache::has($this->sampleCacheKey()));
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DatabaseNotification::query()->count());
    }

    private function revoke(string $revocation): void
    {
        match ($revocation) {
            'membership' => DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->recipient->id)->delete(),
            'inactive' => $this->recipient->update(['is_active' => false]),
            'unverified' => $this->recipient->update(['email_verified_at' => null]),
            'lab' => $this->lab->delete(),
            'source' => $this->entry->delete(),
        };
    }

    private function enqueueGeneric(string $kind): void
    {
        if ($kind === 'global') {
            $this->recipient->notify(new GlobalNotification('Lab alert', 'Private message', $this->sender, labId: $this->lab->id));
        } else {
            $template = $this->override('quality.complaint.created');
            $template->update(['channels' => ['database', 'broadcast']]);
            app(NotificationTemplateService::class)->notify([$this->recipient, $this->peer], 'quality.complaint.created', [
                'lab_id' => $this->lab->id, 'document_number' => 'C-1', 'severity' => 'high', 'document_url' => route('complaints.index'),
            ]);
        }

        $this->assertSame(2, DB::table('jobs')->where('queue', 'notifications')->count());
    }

    private function deliver(string $queue): void
    {
        $processed = 0;
        while ($job = Queue::connection('database')->pop($queue)) {
            $this->assertInstanceOf(DatabaseJob::class, $job);
            $this->assertNotEmpty($job->payload()['data']['command']);
            $job->fire();
            $this->assertTrue($job->isDeleted());
            $this->assertLessThan(10, ++$processed, 'Unexpected recursive notification queue.');
        }

        $this->assertGreaterThan(0, $processed);
    }

    private function deliverInFreshWorker(int $expectedProcessed = 1): void
    {
        $connection = DB::connection();
        $worker = <<<'PHP'
        require getcwd().'/vendor/autoload.php';
        $app = require getcwd().'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $connection = Illuminate\Support\Facades\DB::connection();
        if (! $app->environment('testing') || $connection->getDatabaseName() !== 'lims_unleashed_test'
            || $connection->getConfig('search_path') !== $argv[1]) {
            throw new RuntimeException('Notification worker requires its disposable test schema.');
        }
        $processed = 0;
        Illuminate\Support\Facades\Queue::after(function (Illuminate\Queue\Events\JobProcessed $event) use (&$processed): void {
            if ($event->connectionName !== 'database' || $event->job->getQueue() !== 'notifications') {
                throw new RuntimeException('Notification worker left its isolated queue.');
            }
            $processed++;
        });
        $status = Illuminate\Support\Facades\Artisan::call('queue:work', [
            'connection' => 'database', '--queue' => 'notifications', '--stop-when-empty' => true,
            '--max-jobs' => (int) $argv[2], '--sleep' => 0, '--tries' => 1, '--timeout' => 20,
        ]);
        if ($status !== 0) {
            throw new RuntimeException('Isolated notification worker failed.');
        }
        echo json_encode(['processed' => $processed], JSON_THROW_ON_ERROR);
        PHP;
        $process = new Process([PHP_BINARY, '-r', $worker, $this->schema, (string) $expectedProcessed], base_path(), [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '',
            'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'),
            'DB_DATABASE' => $connection->getDatabaseName(), 'DB_USERNAME' => $connection->getConfig('username'),
            'DB_PASSWORD' => $connection->getConfig('password') ?? '', 'DB_SCHEMA' => $this->schema,
            'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array', 'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'database', 'MAIL_MAILER' => 'array',
        ], timeout: 30);
        $process->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
        $this->assertSame(['processed' => $expectedProcessed], json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR));
    }
}
