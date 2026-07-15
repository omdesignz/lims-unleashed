<?php

namespace App\Jobs;

use App\Mail\SharedDocumentMail;
use App\Models\DocumentDelivery;
use App\Support\NotificationTemplateService;
use App\Support\ShareableDocumentRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendSharedDocumentEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 180];

    public function __construct(public readonly DocumentDelivery $delivery)
    {
        $this->onQueue('mail');
        $this->afterCommit();
    }

    public function handle(ShareableDocumentRegistry $documents, NotificationTemplateService $templates): void
    {
        if ($this->delivery->fresh()?->status === 'sent') {
            return;
        }

        $document = $documents->render($this->delivery->document_type, $this->delivery->document_id);
        $mail = Mail::to($this->delivery->recipients);

        if (filled($this->delivery->cc)) {
            $mail->cc($this->delivery->cc);
        }

        $mail->send(new SharedDocumentMail(
            $this->delivery->subject,
            $this->delivery->message,
            $document,
            $document['content']
        ));

        $this->delivery->update([
            'status' => 'sent',
            'sent_at' => now(),
            'attachment_name' => $document['filename'],
            'failure_message' => null,
        ]);

        if ($this->delivery->sender) {
            $templates->notify([$this->delivery->sender], 'documents.shared', [
                'document_label' => $document['label'],
                'document_number' => $document['number'],
                'document_url' => $document['url'],
                'recipients' => implode(', ', $this->delivery->recipients),
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->delivery->update([
            'status' => 'failed',
            'failure_message' => str($exception?->getMessage() ?? 'Falha desconhecida')->limit(2000),
        ]);

        $sender = $this->delivery->sender;

        if ($sender) {
            app(NotificationTemplateService::class)->notify([$sender], 'documents.share_failed', [
                'document_label' => str($this->delivery->document_type)->replace('_', ' ')->headline()->toString(),
                'detail' => str($exception?->getMessage() ?? 'Falha desconhecida')->limit(300)->toString(),
                'document_url' => route('notifications.index'),
            ]);
        }
    }
}
