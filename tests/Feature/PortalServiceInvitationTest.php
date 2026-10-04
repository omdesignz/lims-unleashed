<?php

namespace Tests\Feature;

use App\Actions\IssuePortalServiceInvitation;
use App\Models\Customer;
use App\Models\CustomerRequest;
use App\Models\CustomerRequestCategory;
use App\Models\PortalServiceInvitation;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\Warehouse;
use App\Notifications\OperationalNotification;
use App\Support\ExportHubQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PortalServiceInvitationTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private VAPLab $peer;

    private User $operator;

    private User $peerOperator;

    private Warehouse $recipient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lab = VAPLab::factory()->create();
        $this->peer = VAPLab::factory()->create();
        $this->operator = $this->member($this->lab);
        $this->peerOperator = $this->member($this->peer);
        $fixture = PortalServiceInvitation::factory()->create(['lab_id' => $this->lab->id, 'issued_by_id' => $this->operator->id]);
        $this->recipient = Warehouse::findOrFail($fixture->warehouse_id);
        $fixture->delete();
        Notification::fake();
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    public function test_issuing_requires_local_authority_and_is_idempotent(): void
    {
        $payload = ['recipient_email' => $this->recipient->email];
        $this->post(route('customerrequests.invitations.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $invitation = PortalServiceInvitation::where('warehouse_id', $this->recipient->id)->sole();
        $this->assertSame($this->lab->id, $invitation->lab_id);
        $this->assertSame($this->operator->id, $invitation->issued_by_id);
        $this->assertSame($this->recipient->customer_id, $invitation->customer_id);
        $this->post(route('customerrequests.invitations.store'), $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, PortalServiceInvitation::where('warehouse_id', $this->recipient->id)->count());
        $this->post(route('customerrequests.invitations.store'), $payload + ['lab_id' => $this->peer->id])->assertSessionHasErrors('lab_id');
        $this->actingAs($this->member($this->lab, false))->post(route('customerrequests.invitations.store'), $payload)->assertForbidden();
        Notification::assertNothingSent();
    }

    public function test_submission_consumes_one_invitation_and_notifies_only_authorized_lab_members(): void
    {
        $invitation = $this->issue();
        $unprivileged = $this->member($this->lab, false);
        $this->portal()->post(route('portal.request.store'), $this->payload($invitation))->assertRedirect()->assertSessionHasNoErrors();
        $record = CustomerRequest::findOrFail($invitation->fresh()->customer_request_id);
        $this->assertSame($this->lab->id, $record->lab_id);
        $this->assertSame($this->recipient->id, $record->warehouse_id);
        $this->assertSame($this->recipient->customer_id, $record->customer_id);
        $this->assertNotNull($invitation->fresh()->consumed_at);
        Notification::assertSentTo($this->operator, OperationalNotification::class, fn ($notification): bool => $notification->payload['context']['lab_id'] === $this->lab->id);
        Notification::assertNotSentTo([$this->peerOperator, $unprivileged], OperationalNotification::class);
        $this->post(route('portal.request.store'), $this->payload($invitation))->assertSessionHasErrors('invitation');
        $this->assertSame(1, CustomerRequest::where('warehouse_id', $this->recipient->id)->count());
        Notification::assertSentToTimes($this->operator, OperationalNotification::class, 1);
    }

    #[DataProvider('invalidInvitations')]
    public function test_invalid_invitation_cannot_create_request(string $condition): void
    {
        $invitation = $this->issue();
        match ($condition) {
            'expired' => DB::table('portal_service_invitations')->where('id', $invitation->id)->update(['expires_at' => now()->subMinute()]),
            'revoked' => $invitation->update(['revoked_at' => now()]),
            'archived lab' => $this->lab->delete(),
            'changed customer' => $this->recipient->update(['customer_id' => Customer::create(['name' => 'Changed customer'])->id]),
            'wrong recipient' => $this->recipient = Warehouse::findOrFail(PortalServiceInvitation::factory()->create()->warehouse_id),
        };
        $this->portal()->post(route('portal.request.store'), $this->payload($invitation))->assertSessionHasErrors('invitation');
        $this->assertNull($invitation->fresh()->consumed_at);
        $this->assertNull($invitation->fresh()->customer_request_id);
        Notification::assertNothingSent();
    }

    public static function invalidInvitations(): array
    {
        return array_map(fn (string $condition): array => [$condition], ['expired', 'revoked', 'archived lab', 'changed customer', 'wrong recipient']);
    }

    public function test_missing_invitation_and_forged_identity_are_rejected(): void
    {
        $invitation = $this->issue();
        $payload = $this->payload($invitation);
        unset($payload['invitation']);
        $this->portal()->post(route('portal.request.store'), $payload)->assertSessionHasErrors('invitation');
        $this->post(route('portal.request.store'), $this->payload($invitation) + ['lab_id' => $this->peer->id])->assertSessionHasErrors('lab_id');
        $this->assertNull($invitation->fresh()->consumed_at);
        Notification::assertNothingSent();
    }

    public function test_write_veto_rolls_back_request_and_keeps_invitation_available(): void
    {
        $invitation = $this->issue();
        $listener = 'eloquent.updating: '.PortalServiceInvitation::class;
        Event::listen($listener, fn (PortalServiceInvitation $record): ?bool => $record->id === $invitation->id ? false : null);
        try {
            $this->portal()->post(route('portal.request.store'), $this->payload($invitation))->assertStatus(409);
        } finally {
            Event::forget($listener);
        }
        $this->assertNull($invitation->fresh()->consumed_at);
        $this->assertSame(0, CustomerRequest::where('warehouse_id', $this->recipient->id)->count());
        Notification::assertNothingSent();
    }

    public function test_staff_lists_lookup_exports_and_mutations_are_lab_private(): void
    {
        $local = $this->requestRecord($this->lab->id, 'Local private request');
        $peer = $this->requestRecord($this->peer->id, 'Peer private request');
        $unowned = $this->requestRecord(null, 'Unowned private request');
        $this->get(route('customerrequests.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('record.data', 1)->where('record.data.0.id', $local->id));
        $this->get(route('customerrequests.getCustomerRequest', ['q' => 'private']))->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $local->id);
        $this->assertSame([$local->id], app(ExportHubQuery::class)->customerRequests([])->pluck('customer_requests.id')->all());
        foreach ([$peer, $unowned] as $record) {
            $this->get(route('customerrequests.edit', $record))->assertNotFound();
            $this->delete(route('customerrequests.destroy'), ['recordIds' => [$local->id, $record->id]])->assertNotFound();
            $this->assertNull($local->fresh()->deleted_at);
            $this->assertNull($record->fresh()->deleted_at);
        }
        $this->get(route('customerrequests.destroy', ['recordIds' => [$local->id]]))->assertStatus(405);
        $this->delete(route('customerrequests.destroy'), ['recordIds' => [$local->id]])->assertRedirect();
        $this->post(route('customerrequests.restore'), ['recordIds' => [$local->id]])->assertRedirect();
        $this->assertNull($local->fresh()->deleted_at);
    }

    public function test_portal_lists_only_its_available_invitations(): void
    {
        $invitation = $this->issue();
        PortalServiceInvitation::factory()->create();
        PortalServiceInvitation::factory()->create(['warehouse_id' => $this->recipient->id, 'customer_id' => $this->recipient->customer_id, 'expires_at' => now()->subDay()]);
        $this->portal()->get(route('portal.requests.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('invitations', 1)->where('invitations.0.token', $invitation->token)->where('invitations.0.lab_name', $this->lab->name));
    }

    private function member(VAPLab $lab, bool $admin = true): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        if ($admin) {
            $user->assignRole(Role::findOrCreate('admin', 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    public function test_staff_authoring_derives_owner_and_preserves_recipient_identity(): void
    {
        $category = CustomerRequestCategory::firstOrCreate(['name' => 'Service authoring test']);
        $payload = ['description' => 'Staff recorded request', 'email' => $this->recipient->email, 'contact' => '900000000',
            'category_id' => ['value' => $category->id], 'customer_id' => ['value' => $this->recipient->customer_id],
            'warehouse_id' => ['value' => $this->recipient->id]];
        $this->post(route('customerrequests.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $record = CustomerRequest::where('warehouse_id', $this->recipient->id)->sole();
        $this->assertSame($this->lab->id, $record->lab_id);
        $this->put(route('customerrequests.update', $record), array_replace($payload, ['description' => 'Corrected contact request']))->assertSessionHasNoErrors();
        $this->assertSame('Corrected contact request', $record->fresh()->description);
        $other = PortalServiceInvitation::factory()->create();
        $this->put(route('customerrequests.update', $record), array_replace($payload, ['warehouse_id' => $other->warehouse_id]))->assertSessionHasErrors('warehouse_id');
        $this->assertSame($this->recipient->id, $record->fresh()->warehouse_id);
        $this->post(route('customerrequests.store'), $payload + ['lab_id' => $this->peer->id])->assertSessionHasErrors('lab_id');
    }

    public function test_portal_completion_is_idempotent_and_cancellation_is_owned_and_atomic(): void
    {
        $record = $this->requestRecord($this->lab->id, 'Portal workflow');
        $other = PortalServiceInvitation::factory()->create();
        $foreign = CustomerRequest::create(['lab_id' => $this->lab->id, 'warehouse_id' => $other->warehouse_id, 'customer_id' => $other->customer_id]);
        $this->portal()->post(route('portal.request.markAsDone', $foreign))->assertNotFound();
        $this->delete(route('portal.request.destroy', $foreign))->assertNotFound();
        $this->get(route('portal.request.markAsDone', $record))->assertStatus(405);
        $this->post(route('portal.request.markAsDone', $record))->assertRedirect();
        $before = $record->fresh()->getRawOriginal();
        $this->travel(1)->hours();
        $this->post(route('portal.request.markAsDone', $record))->assertRedirect();
        $this->assertSame($before, $record->fresh()->getRawOriginal());
        $listener = 'eloquent.deleting: '.CustomerRequest::class;
        Event::listen($listener, fn (): bool => false);
        try {
            $this->delete(route('portal.request.destroy', $record))->assertStatus(409);
        } finally {
            Event::forget($listener);
        }
        $this->assertSame($before, $record->fresh()->getRawOriginal());
        $this->delete(route('portal.request.destroy', $record))->assertRedirect();
        $this->assertSame('cancelled', CustomerRequest::withTrashed()->findOrFail($record->id)->status);
    }

    public function test_reassigned_portal_site_cannot_access_former_customer_requests(): void
    {
        $record = $this->requestRecord($this->lab->id, 'Former customer request');
        $this->recipient->update(['customer_id' => Customer::create(['name' => 'New site owner'])->id]);
        $this->portal()->get(route('portal.requests.index'))->assertInertia(fn (Assert $page) => $page->has('record.data', 0));
        $this->post(route('portal.request.markAsDone', $record))->assertNotFound();
        $this->delete(route('portal.request.destroy', $record))->assertNotFound();
    }

    public function test_revoked_membership_cannot_issue_from_stale_action_context(): void
    {
        DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->operator->id)->delete();
        $this->expectException(AuthorizationException::class);
        $this->issue();
    }

    private function issue(): PortalServiceInvitation
    {
        return app(IssuePortalServiceInvitation::class)->execute($this->lab->id, $this->operator->id, ['recipient_email' => $this->recipient->email]);
    }

    private function portal(): static
    {
        inertia()->flushShared();

        return $this->actingAs($this->recipient, 'portal');
    }

    private function payload(PortalServiceInvitation $invitation): array
    {
        return ['invitation' => $invitation->token, 'request_type' => 'general_support', 'title' => 'Private service request',
            'description' => 'Please review this request.', 'email' => $this->recipient->email, 'contact' => '900000000'];
    }

    private function requestRecord(?int $labId, string $description): CustomerRequest
    {
        return CustomerRequest::create(['lab_id' => $labId, 'description' => $description, 'title' => $description,
            'warehouse_id' => $this->recipient->id, 'customer_id' => $this->recipient->customer_id,
            'category_id' => CustomerRequestCategory::firstOrCreate(['name' => 'Service test'])->id]);
    }
}
