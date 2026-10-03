<?php

namespace Tests\Feature;

use App\Actions\QueueSharedDocumentDelivery;
use App\Jobs\SendSharedDocumentEmail;
use App\Models\CollectionProduct;
use App\Models\Customer;
use App\Models\DocumentDelivery;
use App\Models\InvoiceItem;
use App\Models\ISOActivityLog;
use App\Models\LabCode;
use App\Models\PaymentCategory;
use App\Models\Sample;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Notifications\OperationalNotification;
use App\Support\NotificationTemplateService;
use App\Support\ShareableDocumentRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use LogicException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\IsolatedPostgresTestCase;

class SharedDocumentDeliveryBoundaryTest extends IsolatedPostgresTestCase
{
    public static function types(): array
    {
        return array_map(fn (string $type): array => [$type], [
            'invoice', 'credit_note', 'receipt', 'quote', 'import_certificate', 'export_certificate', 'quality_certificate',
        ]);
    }

    /** @return array{VAPLab, User, Model} */
    private function fixture(string $type): array
    {
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $definition = app(ShareableDocumentRegistry::class)->definition($type);
        $user->givePermissionTo(Permission::findOrCreate($definition['permission'], 'web'));
        $site = Warehouse::create(['name' => 'Delivery '.Str::uuid(), 'customer_id' => Customer::create(['name' => 'Shared delivery customer'])->id]);
        $document = new $definition['model'];
        $document->forceFill(['user_id' => $user->id, ...match ($type) {
            'invoice' => ['lab_id' => $lab->id, 'inv_no' => 'DEL-'.Str::uuid(), 'invoice_month' => now()->format('Y'), 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id],
            'credit_note' => ['lab_id' => $lab->id, 'note_no' => 'DEL-'.Str::uuid(), 'note_month' => now()->format('Y'), 'reason' => 'Correction', 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id],
            'receipt' => ['lab_id' => $lab->id, 'rec_no' => 'DEL-'.Str::uuid(), 'rec_month' => now()->format('Y'), 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id],
            'quote' => ['lab_id' => $lab->id, 'quote_no' => 'DEL-'.Str::uuid(), 'quote_month' => now()->format('Y'), 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id],
            'import_certificate' => ['lab_id' => $lab->id, 'cert_no' => 'DEL-'.Str::uuid(), 'importer_id' => $site->customer_id, 'importer_warehouse_id' => $site->id],
            'export_certificate' => ['lab_id' => $lab->id, 'cert_no' => 'DEL-'.Str::uuid(), 'exporter_id' => $site->customer_id, 'exporter_warehouse_id' => $site->id],
            default => $this->qualitySource($lab, $site),
        }])->saveQuietly();

        return [$lab, $user, $document->fresh()];
    }

    /** @return array<string, mixed> */
    private function qualitySource(VAPLab $lab, Warehouse $site): array
    {
        $collection = new CollectionProduct(['customer_id' => $site->customer_id, 'warehouse_id' => $site->id]);
        $collection->saveQuietly();
        VAPSampleEntry::factory()->createQuietly(['lab_id' => $lab->id, 'collection_product_id' => $collection->id,
            'customer_id' => $site->customer_id, 'warehouse_id' => $site->id]);
        $code = new LabCode(['collection_id' => $collection->id, 'code' => 'DEL-'.Str::uuid(),
            'cl_month' => now()->format('Y'), 'codeable_type' => 'analysis']);
        $code->saveQuietly();

        return ['code' => 'CERT-'.Str::uuid(), 'collection_id' => $collection->id, 'cl_id' => $code->id,
            'customer_id' => $site->customer_id, 'warehouse_id' => $site->id];
    }

    private function data(string $type, Model $document): array
    {
        return ['document_type' => $type, 'document_id' => $document->id,
            'recipients' => ['recipient@example.test'], 'cc' => ['audit@example.test'],
            'subject' => 'Private document', 'message' => 'Test only.'];
    }

    private function delivery(VAPLab $lab, User $user, string $type, Model $document): DocumentDelivery
    {
        return DocumentDelivery::create([...$this->data($type, $document), 'sender_id' => $user->id, 'status' => 'queued']);
    }

    private function pdf(): array
    {
        return ['content' => '%PDF-test-only', 'filename' => 'document.pdf', 'number' => 'TEST',
            'label' => 'Document', 'url' => '/documents', 'default_recipients' => []];
    }

    private function renderer(string $type, Model $document, ?callable $duringRender = null): ShareableDocumentRegistry
    {
        $renderer = Mockery::mock(ShareableDocumentRegistry::class);
        $renderer->shouldReceive('render')->once()->with($type, $document->id)->andReturnUsing(function () use ($duringRender): array {
            $duringRender?->__invoke();

            return $this->pdf();
        });

        return $renderer;
    }

    #[DataProvider('types')]
    public function test_queue_preparation_binds_owned_source_and_preserves_arbitrary_valid_recipients(string $type): void
    {
        [$lab, $user, $document] = $this->fixture($type);
        Queue::fake();
        $delivery = app(QueueSharedDocumentDelivery::class)->execute($user->id, $lab->id, $this->data($type, $document));
        $this->assertSame($lab->id, $delivery->lab_id);
        $this->assertSame($user->id, $delivery->sender_id);
        $this->assertSame(['recipient@example.test'], $delivery->recipients);
        $this->assertSame(['audit@example.test'], $delivery->cc);
        $this->assertTrue(ISOActivityLog::withoutGlobalScopes()->where('subject_type', $document->getMorphClass())
            ->where('subject_id', $document->id)->where('event', 'delivery_queued')->where('properties->lab_id', $lab->id)->exists());
        $peer = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $user->id]);
        try {
            app(QueueSharedDocumentDelivery::class)->execute($user->id, $peer->id, $this->data($type, $document));
            $this->fail('A joined peer lab cannot queue this private document.');
        } catch (AuthorizationException) {
            $this->assertSame(1, DocumentDelivery::count());
        }
    }

    #[DataProvider('types')]
    public function test_each_document_type_sends_once_and_completion_notifications_remain_permissioned(string $type): void
    {
        [$lab, $user, $document] = $this->fixture($type);
        $delivery = $this->delivery($lab, $user, $type, $document);
        $job = new SendSharedDocumentEmail($delivery);
        $templates = Mockery::mock(NotificationTemplateService::class);
        $templates->shouldReceive('notify')->once()->withArgs(fn ($recipients, string $key, array $context): bool => $key === 'documents.shared' && $context['lab_id'] === $lab->id && $context['document_delivery_id'] === $delivery->id)->andReturn(1);
        $job->handle($this->renderer($type, $document), $templates);
        $this->assertSame('sent', $delivery->fresh()->status);
        $this->assertSame($job->attemptId, $delivery->fresh()->attempt_id);
        $this->assertNotNull($delivery->fresh()->dispatch_started_at);
        $empty = Mockery::mock(ShareableDocumentRegistry::class);
        $empty->shouldNotReceive('render');
        $job->handle($empty, $templates);
        $this->assertCount(1, Mail::mailer()->getSymfonyTransport()->messages());
        $notice = new OperationalNotification(['key' => 'documents.shared', 'context' => [
            'lab_id' => $lab->id, 'document_delivery_id' => $delivery->id,
        ]]);
        $this->assertTrue($notice->shouldSend($user, 'database'));
        $user->revokePermissionTo(app(ShareableDocumentRegistry::class)->definition($type)['permission']);
        $this->assertFalse($notice->shouldSend($user, 'database'));
        $this->assertFalse($notice->shouldSend($user, 'broadcast'));
        $this->assertFalse($notice->shouldSend($user, 'mail'));
    }

    public function test_enqueue_save_audit_identity_and_authority_failures_roll_back(): void
    {
        [$lab, $user, $document] = $this->fixture('invoice');
        foreach (['save', 'audit', 'delivery_identity', 'audit_identity', 'permission'] as $failure) {
            Queue::fake();
            $event = match ($failure) {
                'save', 'delivery_identity' => 'eloquent.creating: '.DocumentDelivery::class,
                default => 'eloquent.creating: '.ISOActivityLog::class,
            };
            $listeners = Event::getRawListeners()[$event] ?? [];
            $called = false;
            Event::listen($event, function (Model $record) use ($failure, $user, &$called): ?bool {
                if ($record instanceof ISOActivityLog && $record->event !== 'delivery_queued') {
                    return null;
                }
                $called = true;
                if (in_array($failure, ['save', 'audit'], true)) {
                    return false;
                }
                if ($failure === 'delivery_identity') {
                    $record->recipients = ['forged@example.test'];
                } elseif ($failure === 'audit_identity') {
                    $record->subject_id = null;
                } else {
                    DB::table('model_has_permissions')->where('model_id', $user->id)->delete();
                }

                return null;
            });
            try {
                try {
                    app(QueueSharedDocumentDelivery::class)->execute($user->id, $lab->id, $this->data('invoice', $document));
                    $this->fail('Unsafe queue preparation committed.');
                } catch (LogicException|AuthorizationException) {
                    $this->assertTrue($called);
                    $this->assertSame(0, DocumentDelivery::count());
                    $this->assertSame(0, ISOActivityLog::withoutGlobalScopes()->where('event', 'delivery_queued')->count());
                    Queue::assertNothingPushed();
                }
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    public function test_identity_and_content_changes_during_render_prevent_transport(): void
    {
        foreach (['recipient', 'subject', 'source', 'owner', 'observation', 'archive', 'party', 'permission', 'membership', 'account', 'lab'] as $change) {
            [$lab, $user, $document] = $this->fixture('invoice');
            $delivery = $this->delivery($lab, $user, 'invoice', $document);
            $job = new SendSharedDocumentEmail($delivery);
            Mail::fake();
            $templates = Mockery::mock(NotificationTemplateService::class);
            $templates->shouldNotReceive('notify');
            $render = $this->renderer('invoice', $document, function () use ($change, $lab, $user, $document, $delivery): void {
                match ($change) {
                    'recipient' => DB::table('document_deliveries')->where('id', $delivery->id)->update(['recipients' => json_encode(['forged@example.test'])]),
                    'subject' => DB::table('document_deliveries')->where('id', $delivery->id)->update(['subject' => 'Forged']),
                    'source' => DB::table('document_deliveries')->where('id', $delivery->id)->update(['document_type' => 'quote']),
                    'owner' => DB::table('document_deliveries')->where('id', $delivery->id)->update(['lab_id' => VAPLab::factory()->create()->id]),
                    'observation' => DB::table('invoices')->where('id', $document->id)->update(['obs' => 'Changed']),
                    'archive' => $document->delete(),
                    'party' => $document->customer->update(['name' => 'Changed customer']),
                    'permission' => DB::table('model_has_permissions')->where('model_id', $user->id)->delete(),
                    'membership' => DB::table('lab_user')->where('user_id', $user->id)->delete(),
                    'account' => DB::table('users')->where('id', $user->id)->update(['is_active' => false]),
                    'lab' => $lab->delete(),
                };
            });
            try {
                $job->handle($render, $templates);
                $this->fail('Stale '.$change.' was delivered.');
            } catch (LogicException|AuthorizationException|ModelNotFoundException) {
                Mail::assertNothingSent();
                $this->assertSame('queued', $delivery->fresh()->status);
                $this->assertNull($delivery->fresh()->attempt_id);
            }
        }
    }

    public function test_transport_ambiguity_blocks_automatic_replay_and_stale_failure_cannot_downgrade_sent(): void
    {
        [$lab, $user, $document] = $this->fixture('invoice');
        $delivery = $this->delivery($lab, $user, 'invoice', $document);
        $job = new SendSharedDocumentEmail($delivery);
        $templates = Mockery::mock(NotificationTemplateService::class);
        $templates->shouldReceive('notify')->once()->withArgs(fn ($recipients, string $key): bool => $key === 'documents.share_uncertain')->andReturn(1);
        Mail::shouldReceive('to')->once()->with(['recipient@example.test'])->andReturnSelf();
        Mail::shouldReceive('cc')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP connection interrupted.'));
        try {
            $job->handle($this->renderer('invoice', $document), $templates);
            $this->fail('Transport must fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('SMTP', $exception->getMessage());
            $this->assertSame('uncertain', $delivery->fresh()->status);
        }
        $render = Mockery::mock(ShareableDocumentRegistry::class);
        $render->shouldNotReceive('render');
        $job->handle($render, $templates);
        $job->failed(new RuntimeException('Retry exhausted'));
        $this->assertSame('uncertain', $delivery->fresh()->status);
        DB::table('document_deliveries')->where('id', $delivery->id)->update(['status' => 'sent', 'sent_at' => now()]);
        $before = $delivery->fresh()->getAttributes();
        $job->failed(new RuntimeException('Stale failed callback'));
        $this->assertSame($before, $delivery->fresh()->getAttributes());
    }

    public function test_notification_failure_does_not_turn_successful_transport_into_failed_delivery(): void
    {
        [$lab, $user, $document] = $this->fixture('invoice');
        $delivery = $this->delivery($lab, $user, 'invoice', $document);
        $templates = Mockery::mock(NotificationTemplateService::class);
        $templates->shouldReceive('notify')->once()->andThrow(new RuntimeException('Notification broker unavailable'));
        (new SendSharedDocumentEmail($delivery))->handle($this->renderer('invoice', $document), $templates);
        $this->assertCount(1, Mail::mailer()->getSymfonyTransport()->messages());
        $this->assertSame('sent', $delivery->fresh()->status);
        $this->assertNull($delivery->fresh()->failure_message);
    }

    public function test_delivery_payload_and_owner_are_immutable_and_required_in_postgresql(): void
    {
        [$lab, $user, $document] = $this->fixture('invoice');
        $delivery = $this->delivery($lab, $user, 'invoice', $document);
        foreach (['lab_id' => 0, 'document_type' => 'quote', 'document_id' => 0, 'sender_id' => null,
            'recipients' => ['forged@example.test'], 'cc' => [], 'subject' => 'Forged', 'message' => 'Forged'] as $field => $value) {
            try {
                $delivery->fresh()->forceFill([$field => $value])->save();
                $this->fail('Delivery identity changed.');
            } catch (LogicException) {
                $this->assertSame('Private document', $delivery->fresh()->subject);
            }
        }
        try {
            DB::transaction(fn () => DB::table('document_deliveries')->where('id', $delivery->id)->update(['lab_id' => null]));
            $this->fail('PostgreSQL accepted an unowned delivery.');
        } catch (QueryException $exception) {
            $this->assertSame('23502', $exception->errorInfo[0]);
        }
    }

    public function test_claim_and_completion_vetoes_or_mutations_do_not_falsely_record_or_repeat_transport(): void
    {
        foreach (['claim_veto', 'claim_mutation', 'completion_veto', 'completion_mutation'] as $failure) {
            Mail::mailer()->getSymfonyTransport()->flush();
            [$lab, $user, $document] = $this->fixture('invoice');
            $delivery = $this->delivery($lab, $user, 'invoice', $document);
            $job = new SendSharedDocumentEmail($delivery);
            $completion = str_starts_with($failure, 'completion');
            $templates = Mockery::mock(NotificationTemplateService::class);
            if ($completion) {
                $templates->shouldReceive('notify')->once()->withArgs(fn ($recipients, string $key): bool => $key === 'documents.share_uncertain')->andReturn(1);
            } else {
                $templates->shouldNotReceive('notify');
            }
            $event = 'eloquent.updating: '.DocumentDelivery::class;
            $listeners = Event::getRawListeners()[$event] ?? [];
            $called = false;
            Event::listen($event, function (DocumentDelivery $record) use ($failure, $completion, &$called): ?bool {
                if ($record->status !== ($completion ? 'sent' : 'sending')) {
                    return null;
                }
                $called = true;
                if (str_ends_with($failure, 'veto')) {
                    return false;
                }
                $record->attempt_id = (string) Str::uuid();

                return null;
            });
            try {
                try {
                    $job->handle($this->renderer('invoice', $document), $templates);
                    $this->fail('Lifecycle failure was accepted.');
                } catch (LogicException) {
                    $this->assertTrue($called);
                    $this->assertSame($completion ? 'uncertain' : 'queued', $delivery->fresh()->status);
                    $this->assertCount($completion ? 1 : 0, Mail::mailer()->getSymfonyTransport()->messages());
                    if ($completion) {
                        $this->assertSame($job->attemptId, $delivery->fresh()->attempt_id);
                        $renderer = Mockery::mock(ShareableDocumentRegistry::class);
                        $renderer->shouldNotReceive('render');
                        $job->handle($renderer, $templates);
                        $job->failed(new RuntimeException('Stale completion failure'));
                        $this->assertCount(1, Mail::mailer()->getSymfonyTransport()->messages());
                        $this->assertSame('uncertain', $delivery->fresh()->status);
                    } else {
                        $this->assertNull($delivery->fresh()->attempt_id);
                    }
                }
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    public function test_native_message_cancellation_does_not_record_sent_or_allow_automatic_replay(): void
    {
        [$lab, $user, $document] = $this->fixture('invoice');
        $delivery = $this->delivery($lab, $user, 'invoice', $document);
        $job = new SendSharedDocumentEmail($delivery);
        $templates = Mockery::mock(NotificationTemplateService::class);
        $templates->shouldReceive('notify')->once()->withArgs(fn ($recipients, string $key): bool => $key === 'documents.share_uncertain')->andReturn(1);
        Event::listen(MessageSending::class, fn (): bool => false);
        try {
            $job->handle($this->renderer('invoice', $document), $templates);
            $this->fail('Cancelled mail was marked sent.');
        } catch (LogicException) {
            $this->assertSame('uncertain', $delivery->fresh()->status);
            $this->assertNull($delivery->fresh()->sent_at);
            $this->assertCount(0, Mail::mailer()->getSymfonyTransport()->messages());
        }
        $renderer = Mockery::mock(ShareableDocumentRegistry::class);
        $renderer->shouldNotReceive('render');
        $job->handle($renderer, $templates);
    }

    public function test_ambient_transaction_cannot_claim_or_dispatch_mail(): void
    {
        [$lab, $user, $document] = $this->fixture('invoice');
        $delivery = $this->delivery($lab, $user, 'invoice', $document);
        $renderer = Mockery::mock(ShareableDocumentRegistry::class);
        $renderer->shouldNotReceive('render');
        $templates = Mockery::mock(NotificationTemplateService::class);
        $templates->shouldNotReceive('notify');
        DB::beginTransaction();
        try {
            (new SendSharedDocumentEmail($delivery))->handle($renderer, $templates);
            $this->fail('Transport ran with a rollbackable claim.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('durable root', $exception->getMessage());
        } finally {
            DB::rollBack();
        }
        $this->assertSame('queued', $delivery->fresh()->status);
        $this->assertNull($delivery->fresh()->attempt_id);
        $this->assertCount(0, Mail::mailer()->getSymfonyTransport()->messages());
    }

    public function test_rendered_line_and_payment_type_changes_are_not_dispatched(): void
    {
        foreach (['invoice', 'receipt'] as $type) {
            [$lab, $user, $document] = $this->fixture($type);
            if ($type === 'invoice') {
                $related = new InvoiceItem(['invoice_id' => $document->id, 'item_description' => 'Original', 'qty' => 1, 'unit_price' => 20, 'total' => 20]);
                $related->forceFill(['lab_id' => $lab->id])->saveQuietly();
                $field = 'item_description';
            } else {
                $related = PaymentCategory::create(['name' => 'Cash', 'description' => 'Original', 'code' => 'CASH']);
                DB::table('receipts')->where('id', $document->id)->update(['payment_type' => $related->id]);
                $field = 'description';
            }
            $delivery = $this->delivery($lab, $user, $type, $document);
            $renderer = $this->renderer($type, $document, fn () => DB::table($related->getTable())->where('id', $related->id)->update([$field => 'Changed']));
            $templates = Mockery::mock(NotificationTemplateService::class);
            $templates->shouldNotReceive('notify');
            try {
                (new SendSharedDocumentEmail($delivery))->handle($renderer, $templates);
                $this->fail('Changed rendered relation was dispatched.');
            } catch (LogicException) {
                $this->assertSame('queued', $delivery->fresh()->status);
                $this->assertCount(0, Mail::mailer()->getSymfonyTransport()->messages());
            }
        }
    }

    public function test_delivery_queue_and_horizon_timeout_configuration_are_compatible(): void
    {
        [$lab, $user, $document] = $this->fixture('invoice');
        $job = new SendSharedDocumentEmail($this->delivery($lab, $user, 'invoice', $document));
        $supervisor = config('horizon.defaults.supervisor-1');
        $this->assertContains('mail', $supervisor['queue']);
        $this->assertContains('notifications', $supervisor['queue']);
        $this->assertContains('broadcasts', $supervisor['queue']);
        $this->assertGreaterThan($job->timeout, $supervisor['timeout']);
        foreach (['database', 'redis', 'beanstalkd'] as $driver) {
            $this->assertGreaterThan($supervisor['timeout'], config('queue.connections.'.$driver.'.retry_after'));
        }
    }

    public function test_quality_delivery_rejects_cross_lab_or_inconsistent_result_lineage_and_changed_results(): void
    {
        [$lab, $user, $document] = $this->fixture('quality_certificate');
        [, , $peer] = $this->fixture('quality_certificate');
        $sample = new Sample(['cl_id' => $peer->cl_id, 'code' => 'TEST', 'sample_month' => now()->format('Y')]);
        $sample->saveQuietly();
        $resultId = DB::table('results')->insertGetId(['sample_id' => $sample->id, 'code_id' => $document->cl_id,
            'collection_id' => $document->collection_id, 'inserted_value' => 'Original']);
        $delivery = $this->delivery($lab, $user, 'quality_certificate', $document);
        $templates = Mockery::mock(NotificationTemplateService::class);
        $templates->shouldNotReceive('notify');
        $renderer = Mockery::mock(ShareableDocumentRegistry::class);
        $renderer->shouldNotReceive('render');
        try {
            (new SendSharedDocumentEmail($delivery))->handle($renderer, $templates);
            $this->fail('Foreign result lineage was rendered.');
        } catch (AuthorizationException) {
            $this->assertSame('queued', $delivery->fresh()->status);
        }
        DB::table('samples')->where('id', $sample->id)->update(['cl_id' => $document->cl_id]);
        $render = $this->renderer('quality_certificate', $document,
            fn () => DB::table('results')->where('id', $resultId)->update(['inserted_value' => 'Changed']));
        try {
            (new SendSharedDocumentEmail($delivery))->handle($render, $templates);
            $this->fail('Changed result values were sent.');
        } catch (LogicException) {
            $this->assertCount(0, Mail::mailer()->getSymfonyTransport()->messages());
            $this->assertSame('queued', $delivery->fresh()->status);
        }
    }

    public function test_failed_callback_for_own_claim_reports_uncertainty_and_foreign_attempt_cannot_replace_it(): void
    {
        [$lab, $user, $document] = $this->fixture('invoice');
        $delivery = $this->delivery($lab, $user, 'invoice', $document);
        $job = new SendSharedDocumentEmail($delivery);
        $foreign = new SendSharedDocumentEmail($delivery);
        $delivery->forceFill(['status' => 'sending', 'attempt_id' => $job->attemptId, 'dispatch_started_at' => now()])->save();
        $foreign->failed(new RuntimeException('Overlapping attempt'));
        $this->assertSame('sending', $delivery->fresh()->status);
        $this->assertSame($job->attemptId, $delivery->fresh()->attempt_id);
        $templates = Mockery::mock(NotificationTemplateService::class);
        $templates->shouldReceive('notify')->once()->withArgs(fn ($recipients, string $key): bool => $key === 'documents.share_uncertain')->andReturn(1);
        $this->app->instance(NotificationTemplateService::class, $templates);
        $job->failed(new RuntimeException('Worker timeout'));
        $this->assertSame('uncertain', $delivery->fresh()->status);
        $this->assertSame('Worker timeout', $delivery->fresh()->failure_message);
        $job->failed(new RuntimeException('Stale failure'));
        $this->assertSame('Worker timeout', $delivery->fresh()->failure_message);
    }
}
