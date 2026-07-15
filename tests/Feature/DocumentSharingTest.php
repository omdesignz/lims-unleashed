<?php

namespace Tests\Feature;

use App\Jobs\SendSharedDocumentEmail;
use App\Mail\SharedDocumentMail;
use App\Models\DocumentDelivery;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\User;
use App\Support\NotificationTemplateService;
use App\Support\ShareableDocumentRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DocumentSharingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authorized_user_can_queue_an_invoice_pdf_delivery(): void
    {
        Queue::fake();
        $admin = $this->verifiedAdmin();
        $invoice = Invoice::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('documents.share'), [
                'document_type' => 'invoice',
                'document_id' => $invoice->id,
                'recipients' => ['finance@example.test'],
                'cc' => ['audit@example.test'],
                'subject' => 'Factura '.$invoice->inv_no,
                'message' => 'Segue a factura solicitada.',
            ])
            ->assertRedirect()
            ->assertSessionHas('toast.variant', 'info');

        $delivery = DocumentDelivery::query()->latest('id')->firstOrFail();
        $this->assertSame($admin->id, $delivery->sender_id);
        $this->assertSame('queued', $delivery->status);
        $this->assertSame(['finance@example.test'], $delivery->recipients);

        Queue::assertPushed(SendSharedDocumentEmail::class, fn (SendSharedDocumentEmail $job): bool => $job->delivery->is($delivery));
    }

    public function test_user_without_document_permission_cannot_queue_delivery(): void
    {
        Queue::fake();
        $user = User::factory()->create(['is_active' => true]);
        $invoice = Invoice::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('documents.share'), [
                'document_type' => 'invoice',
                'document_id' => $invoice->id,
                'recipients' => ['finance@example.test'],
                'subject' => 'Restricted invoice',
                'message' => 'This must not be sent.',
            ])
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_invalid_document_cannot_be_queued_for_delivery(): void
    {
        Queue::fake();

        $this->actingAs($this->verifiedAdmin())
            ->post(route('documents.share'), [
                'document_type' => 'invoice',
                'document_id' => PHP_INT_MAX,
                'recipients' => ['finance@example.test'],
                'subject' => 'Factura inexistente',
                'message' => 'Este envio deve ser rejeitado.',
            ])
            ->assertSessionHasErrors('document_id');

        Queue::assertNothingPushed();
    }

    public function test_delivery_job_sends_pdf_attachment_and_records_completion(): void
    {
        Mail::fake();
        $admin = $this->verifiedAdmin();
        $invoice = Invoice::query()->firstOrFail();
        $delivery = DocumentDelivery::query()->create([
            'sender_id' => $admin->id,
            'document_type' => 'invoice',
            'document_id' => $invoice->id,
            'recipients' => ['finance@example.test'],
            'cc' => ['audit@example.test'],
            'subject' => 'Factura '.$invoice->inv_no,
            'message' => 'Segue a factura solicitada.',
            'status' => 'queued',
        ]);

        $documents = Mockery::mock(ShareableDocumentRegistry::class);
        $documents->shouldReceive('render')->once()->with('invoice', $invoice->id)->andReturn([
            'content' => '%PDF-1.4 test',
            'filename' => 'factura-'.$invoice->id.'.pdf',
            'label' => 'Factura',
            'number' => $invoice->inv_no,
            'url' => route('invoices.show', $invoice),
            'default_recipients' => [],
        ]);

        $templates = Mockery::mock(NotificationTemplateService::class);
        $templates->shouldReceive('notify')->once()->andReturn(1);

        (new SendSharedDocumentEmail($delivery))->handle($documents, $templates);

        Mail::assertSent(SharedDocumentMail::class, function (SharedDocumentMail $mail): bool {
            return $mail->hasTo('finance@example.test')
                && $mail->hasCc('audit@example.test')
                && count($mail->attachments()) === 1;
        });

        $delivery->refresh();
        $this->assertSame('sent', $delivery->status);
        $this->assertNotNull($delivery->sent_at);
        $this->assertSame('factura-'.$invoice->id.'.pdf', $delivery->attachment_name);
    }

    public function test_failed_delivery_records_error_and_notifies_the_sender(): void
    {
        $delivery = DocumentDelivery::query()->create([
            'sender_id' => $this->verifiedAdmin()->id,
            'document_type' => 'invoice',
            'document_id' => Invoice::query()->value('id'),
            'recipients' => ['finance@example.test'],
            'subject' => 'Factura indisponível',
            'message' => 'Segue o documento.',
            'status' => 'queued',
        ]);

        $templates = Mockery::mock(NotificationTemplateService::class);
        $templates->shouldReceive('notify')
            ->once()
            ->withArgs(fn ($recipients, string $key): bool => $key === 'documents.share_failed'
                && collect($recipients)->first()->is($delivery->sender))
            ->andReturn(1);
        $this->app->instance(NotificationTemplateService::class, $templates);

        (new SendSharedDocumentEmail($delivery))->failed(new RuntimeException('Servidor SMTP indisponível'));

        $delivery->refresh();
        $this->assertSame('failed', $delivery->status);
        $this->assertStringContainsString('SMTP', $delivery->failure_message);
    }

    private function verifiedAdmin(): User
    {
        return Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
    }
}
