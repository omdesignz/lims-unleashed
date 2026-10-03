<?php

namespace App\Jobs;

use App\Mail\SharedDocumentMail;
use App\Models\DocumentDelivery;
use App\Services\SharedDocumentDeliveryAccess;
use App\Support\NotificationTemplateService;
use App\Support\ShareableDocumentRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\SentMessage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

class SendSharedDocumentEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var list<int> */
    public array $backoff = [10, 60, 180];

    public readonly string $attemptId;

    /** @var array<string, mixed> */
    public readonly array $identity;

    public function __construct(public readonly DocumentDelivery $delivery)
    {
        $this->attemptId = (string) Str::uuid();
        $this->identity = $delivery->only(['lab_id', 'sender_id', 'document_type', 'document_id', 'recipients', 'cc', 'subject', 'message']);
        $this->onQueue('mail');
        $this->afterCommit();
    }

    public function handle(ShareableDocumentRegistry $documents, NotificationTemplateService $templates): void
    {
        $access = app(SharedDocumentDeliveryAccess::class);
        $delivery = $this->delivery->fresh();
        if (! $delivery || ! in_array($delivery->status, ['queued', 'failed'], true)) {
            return;
        }
        $this->assertIdentity($delivery, $access);
        $source = $access->authorize((int) $delivery->sender_id, (int) $delivery->lab_id, $delivery->document_type, (int) $delivery->document_id);
        if (DB::connection()->transactionLevel() !== 0) {
            throw new LogicException('Document transport requires a durable root dispatch claim, not an ambient transaction.');
        }
        $document = $documents->render($delivery->document_type, (int) $delivery->document_id);
        $claimed = DB::transaction(function () use ($access, $source): ?DocumentDelivery {
            $fresh = DocumentDelivery::query()->lockForUpdate()->find($this->delivery->id);
            if (! $fresh || ! in_array($fresh->status, ['queued', 'failed'], true)) {
                return null;
            }
            $this->assertIdentity($fresh, $access);
            $current = $access->authorize((int) $fresh->sender_id, (int) $fresh->lab_id, $fresh->document_type, (int) $fresh->document_id, true);
            if ($current['fingerprint'] !== $source['fingerprint']) {
                throw new LogicException('Document content changed during PDF generation; no email was dispatched.');
            }
            if (! $fresh->forceFill(['status' => 'sending', 'attempt_id' => $this->attemptId, 'dispatch_started_at' => now(),
                'failure_message' => null])->save()) {
                throw new LogicException('Document dispatch claim was not persisted.');
            }
            $persisted = $fresh->fresh();
            $this->assertIdentity($persisted, $access);
            if ($persisted->status !== 'sending' || $persisted->attempt_id !== $this->attemptId
                || $access->authorize((int) $fresh->sender_id, (int) $fresh->lab_id, $fresh->document_type, (int) $fresh->document_id, true)['fingerprint'] !== $source['fingerprint']) {
                throw new LogicException('Document dispatch claim changed before commit.');
            }

            return $persisted;
        }, 3);
        if (! $claimed) {
            return;
        }

        try {
            $mail = Mail::to($claimed->recipients);
            if (filled($claimed->cc)) {
                $mail->cc($claimed->cc);
            }
            $sent = $mail->send(new SharedDocumentMail($claimed->subject, $claimed->message, $document, $document['content']));
            if (! $sent instanceof SentMessage) {
                throw new LogicException('Document transport did not confirm message acceptance.');
            }
        } catch (Throwable $exception) {
            try {
                $this->markUncertain($exception);
            } catch (Throwable $persistenceFailure) {
                report($persistenceFailure);
            }
            $this->notifyUncertain($templates);
            throw $exception;
        }

        try {
            DB::transaction(function () use ($access, $document): void {
                $fresh = DocumentDelivery::query()->lockForUpdate()->findOrFail($this->delivery->id);
                $this->assertIdentity($fresh, $access);
                if ($fresh->attempt_id !== $this->attemptId || ! in_array($fresh->status, ['sending', 'uncertain'], true)
                    || ! $fresh->update(['status' => 'sent', 'sent_at' => now(), 'attachment_name' => $document['filename'], 'failure_message' => null])) {
                    throw new LogicException('Document delivery completion was not persisted; do not automatically resend.');
                }
                $persisted = $fresh->fresh();
                $this->assertIdentity($persisted, $access);
                if ($persisted->status !== 'sent' || $persisted->attempt_id !== $this->attemptId
                    || ! $persisted->sent_at || $persisted->attachment_name !== $document['filename'] || $persisted->failure_message !== null) {
                    throw new LogicException('Document delivery completion changed during persistence.');
                }
            }, 3);
        } catch (Throwable $exception) {
            try {
                $this->markUncertain($exception);
            } catch (Throwable $persistenceFailure) {
                report($persistenceFailure);
            }
            $this->notifyUncertain($templates);
            throw $exception;
        }
        $this->notifySender($templates, 'documents.shared', [
            'document_label' => $document['label'], 'document_number' => $document['number'],
            'document_url' => $document['url'], 'recipients' => implode(', ', $claimed->recipients),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $status = DB::transaction(function () use ($exception): ?string {
            $delivery = DocumentDelivery::query()->lockForUpdate()->find($this->delivery->id);
            if (! $delivery || $delivery->status === 'sent' || $delivery->status === 'uncertain') {
                return null;
            }
            if ($delivery->status === 'sending' && $delivery->attempt_id !== $this->attemptId) {
                return null;
            }
            $access = app(SharedDocumentDeliveryAccess::class);
            $this->assertIdentity($delivery, $access);
            $state = ['status' => $delivery->status === 'sending' ? 'uncertain' : 'failed',
                'failure_message' => str($exception?->getMessage() ?? 'Falha desconhecida')->limit(2000)->toString()];
            $attempt = $delivery->attempt_id;
            if (! $delivery->update($state)) {
                throw new LogicException('Document delivery failure state was not persisted.');
            }
            $persisted = $delivery->fresh();
            $this->assertIdentity($persisted, $access);
            if ($persisted->status !== $state['status'] || $persisted->failure_message !== $state['failure_message'] || $persisted->attempt_id !== $attempt) {
                throw new LogicException('Document delivery failure state changed during persistence.');
            }

            return $persisted->status;
        }, 3);
        if ($status === 'uncertain') {
            $this->notifyUncertain(app(NotificationTemplateService::class));
        } elseif ($status === 'failed') {
            $this->notifySender(app(NotificationTemplateService::class), 'documents.share_failed', [
                'document_label' => str($this->identity['document_type'])->replace('_', ' ')->headline()->toString(),
                'detail' => str($exception?->getMessage() ?? 'Falha desconhecida')->limit(300)->toString(),
                'document_url' => route('notifications.index'),
            ]);
        }
    }

    private function markUncertain(Throwable $exception): void
    {
        $changed = DocumentDelivery::query()->whereKey($this->delivery->id)->where('status', 'sending')->where('attempt_id', $this->attemptId)
            ->update(['status' => 'uncertain', 'failure_message' => str($exception->getMessage())->limit(2000)->toString()]);
        if ($changed !== 1) {
            throw new LogicException('The uncertain transport outcome was not persisted; do not automatically resend.');
        }
    }

    private function assertIdentity(DocumentDelivery $delivery, SharedDocumentDeliveryAccess $access): void
    {
        if ($access->deliveryIdentity($delivery) !== $this->identity) {
            throw new LogicException('Queued document ownership, source or recipient instructions changed.');
        }
    }

    private function notifyUncertain(NotificationTemplateService $templates): void
    {
        $this->notifySender($templates, 'documents.share_uncertain', [
            'document_label' => str($this->identity['document_type'])->replace('_', ' ')->headline()->toString(),
            'document_url' => route('notifications.index'),
        ]);
    }

    /** @param array<string, scalar|null> $context */
    private function notifySender(NotificationTemplateService $templates, string $key, array $context): void
    {
        try {
            $delivery = $this->delivery->fresh();
            if (! $delivery) {
                return;
            }
            $access = app(SharedDocumentDeliveryAccess::class);
            $this->assertIdentity($delivery, $access);
            $source = $access->authorize((int) $delivery->sender_id, (int) $delivery->lab_id, $delivery->document_type, (int) $delivery->document_id);
            $templates->notify([$source['operator']], $key, [
                ...$context, 'lab_id' => $delivery->lab_id, 'document_delivery_id' => $delivery->id,
            ]);
        } catch (AuthorizationException|ModelNotFoundException) {
            return;
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
