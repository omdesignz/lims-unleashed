<?php

namespace Tests\Feature;

use App\Jobs\SendSharedDocumentEmail;
use App\Models\DocumentDelivery;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Support\NotificationTemplateService;
use App\Support\ShareableDocumentRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\IsolatedPostgresTestCase;

class DocumentSharingTest extends IsolatedPostgresTestCase
{
    public function test_authorized_user_can_queue_an_invoice_pdf_delivery(): void
    {
        $admin = $this->verifiedAdmin();
        $invoice = $this->invoice($admin);
        Queue::fake();

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
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->invoice($user);
        Queue::fake();

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
        $admin = $this->verifiedAdmin();
        $invoice = $this->invoice($admin);
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

        $sent = Mail::mailer()->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertSame('finance@example.test', $sent->getTo()[0]->getAddress());
        $this->assertSame('audit@example.test', $sent->getCc()[0]->getAddress());
        $this->assertCount(1, $sent->getAttachments());

        $delivery->refresh();
        $this->assertSame('sent', $delivery->status);
        $this->assertNotNull($delivery->sent_at);
        $this->assertSame('factura-'.$invoice->id.'.pdf', $delivery->attachment_name);
    }

    public function test_failed_delivery_records_error_and_notifies_the_sender(): void
    {
        $admin = $this->verifiedAdmin();
        $invoice = $this->invoice($admin);
        $delivery = DocumentDelivery::query()->create([
            'sender_id' => $admin->id,
            'document_type' => 'invoice',
            'document_id' => $invoice->id,
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
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    public function test_financial_delivery_rechecks_membership_before_render_and_before_sending(): void
    {
        foreach ([false, true] as $revokeWhileRendering) {
            $admin = $this->verifiedAdmin();
            $invoice = $this->invoice($admin);
            Mail::fake();
            $delivery = DocumentDelivery::create([
                'sender_id' => $admin->id, 'document_type' => 'invoice', 'document_id' => $invoice->id,
                'recipients' => ['finance@example.test'], 'subject' => 'Private invoice',
                'message' => 'Private financial data', 'status' => 'queued',
            ]);
            $documents = Mockery::mock(ShareableDocumentRegistry::class);
            if ($revokeWhileRendering) {
                $documents->shouldReceive('render')->once()->with('invoice', $invoice->id)
                    ->andReturnUsing(function () use ($admin): array {
                        DB::table('lab_user')->where('user_id', $admin->id)->delete();

                        return ['content' => '%PDF', 'label' => 'Invoice', 'number' => 'Private', 'url' => '/private', 'filename' => 'private.pdf'];
                    });
            } else {
                DB::table('lab_user')->where('user_id', $admin->id)->delete();
                $documents->shouldNotReceive('render');
            }
            $templates = Mockery::mock(NotificationTemplateService::class);
            $templates->shouldNotReceive('notify');
            try {
                (new SendSharedDocumentEmail($delivery))->handle($documents, $templates);
                $this->fail('Revoked financial access must prevent delivery.');
            } catch (AuthorizationException) {
                Mail::assertNothingSent();
                $this->assertSame('queued', $delivery->fresh()->status);
                $this->assertNull($delivery->fresh()->sent_at);
            }
        }
    }

    private function invoice(User $user): Invoice
    {
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $invoice = new Invoice([
            'user_id' => $user->id,
            'inv_no' => fake()->unique()->bothify('SHARE-########'),
            'invoice_month' => now()->format('m/Y'),
            'date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
        ]);
        $invoice->lab_id = $lab->id;
        $invoice->saveQuietly();

        return $invoice;
    }
}
