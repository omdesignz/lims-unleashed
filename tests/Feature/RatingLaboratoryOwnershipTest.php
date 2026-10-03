<?php

namespace Tests\Feature;

use App\Actions\IssuePortalRatingInvitation;
use App\Actions\SubmitRating;
use App\Models\CriteriaRating;
use App\Models\Customer;
use App\Models\Rating;
use App\Models\RatingRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Notifications\OperationalNotification;
use App\Support\NotificationTemplateService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class RatingLaboratoryOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private VAPLab $peer;

    private User $operator;

    private User $peerOperator;

    private Warehouse $recipient;

    private CriteriaRating $criterion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lab = VAPLab::factory()->create();
        $this->peer = VAPLab::factory()->create();
        $this->operator = $this->member($this->lab);
        $this->peerOperator = $this->member($this->peer);
        $this->recipient = $this->portalAccount();
        CriteriaRating::withTrashed()->where('type', 'service')->forceDelete();
        $this->criterion = CriteriaRating::query()->create(['name' => 'Survey criterion', 'type' => 'service']);
        Notification::fake();
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    public function test_invitation_issue_is_local_recipient_bound_and_idempotent(): void
    {
        $this->post(route('ratings.invitations.store'), $this->invitationPayload())->assertSessionHasNoErrors()->assertRedirect(route('ratings.index'));
        $invitation = RatingRequest::query()->where('lab_id', $this->lab->id)->sole();
        $this->assertSame($this->operator->id, $invitation->issued_by_id);
        $this->assertSame($this->recipient->id, $invitation->rater_id);
        $this->assertSame($this->recipient->customer_id, $invitation->recipient_customer_id);
        $this->assertSame($this->criterion->id, $invitation->criteria_snapshot[0]['id']);
        $this->post(route('ratings.invitations.store'), $this->invitationPayload())->assertSessionHasNoErrors();
        $this->assertSame(1, RatingRequest::query()->where('lab_id', $this->lab->id)->count());
        Notification::assertSentToTimes($this->recipient, OperationalNotification::class, 1);
        Notification::assertSentTo($this->recipient, OperationalNotification::class, fn ($notification): bool => $notification->payload['context']['lab_id'] === $this->lab->id
            && $notification->payload['context']['invitation'] === $invitation->invitation);
    }

    public function test_portal_requires_invitation_and_exact_authenticated_recipient(): void
    {
        $invitation = $this->issue();
        $this->asPortal($this->recipient)->get('/portal/rate/service')->assertNotFound();
        $this->post('/portal/rate/service/0', $this->scores())->assertNotFound();
        $other = $this->portalAccount();
        $this->asPortal($other)->get(route('portal.rating.create', ['invitation' => $invitation->invitation]))->assertNotFound();
        $this->post(route('portal.rating.store', ['invitation' => $invitation->invitation]), $this->scores())->assertNotFound();
        $this->assertFalse(Rating::query()->where('rating_request_id', $invitation->id)->exists());

        $page = $this->asPortal($this->recipient)->get(route('portal.rating.create', ['invitation' => $invitation->invitation]))->assertOk()->viewData('page');
        $this->assertSame($this->lab->name, data_get($page, 'props.laboratoryName'));
        $this->assertSame(['invitation' => $invitation->invitation], data_get($page, 'props.storeParameters'));
        $this->assertArrayNotHasKey('rater_id', data_get($page, 'props.ratingRequest'));
    }

    public function test_portal_response_uses_issued_criteria_not_a_changed_catalogue_and_cannot_replay(): void
    {
        $invitation = $this->issue();
        $this->criterion->update(['name' => 'Changed later', 'type' => 'order']);
        CriteriaRating::query()->create(['name' => 'New question', 'type' => 'service']);
        $page = $this->asPortal($this->recipient)->get(route('portal.rating.create', ['invitation' => $invitation->invitation]))->assertOk()->viewData('page');
        $this->assertSame('Survey criterion', data_get($page, 'props.criteria.0.name'));
        $this->post(route('portal.rating.store', ['invitation' => $invitation->invitation]), $this->scores())
            ->assertSessionHasNoErrors()->assertRedirect(route('portal.home'));
        $rating = Rating::query()->where('rating_request_id', $invitation->id)->sole();
        $this->assertSame($this->lab->id, $rating->lab_id);
        $this->assertSame(5, $rating->criteria[$this->criterion->id]);
        $this->assertSame('Survey criterion', $rating->metadata['criteria_snapshot'][0]['name']);
        $this->assertSame('completed', $invitation->fresh()->status);
        $this->post(route('portal.rating.store', ['invitation' => $invitation->invitation]), $this->scores())->assertNotFound();
        $this->assertSame(1, Rating::query()->where('rating_request_id', $invitation->id)->count());
        Notification::assertNotSentTo($this->peerOperator, OperationalNotification::class);
    }

    public function test_general_internal_survey_is_owned_by_active_lab_and_once_per_lab(): void
    {
        $url = route('rating.store', ['rateableType' => 'service']);
        $this->post($url, $this->scores())->assertSessionHasNoErrors()->assertRedirect();
        $this->post($url, $this->scores())->assertSessionHasErrors('criteria');
        DB::table('lab_user')->insert(['lab_id' => $this->peer->id, 'user_id' => $this->operator->id]);
        $this->withSession(['active_lab_id' => $this->peer->id])->post($url, $this->scores())->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEqualsCanonicalizing([$this->lab->id, $this->peer->id], Rating::query()->where('rater_id', $this->operator->id)->pluck('lab_id')->all());
    }

    public function test_lists_charts_and_pending_portal_list_do_not_disclose_peer_feedback(): void
    {
        $local = $this->issue();
        $foreign = $this->issue($this->peer, $this->peerOperator);
        app(SubmitRating::class)->internal($this->lab->id, $this->operator->id, 'service', 0, $this->scores());
        app(SubmitRating::class)->internal($this->peer->id, $this->peerOperator->id, 'service', 0, ['criteria' => [$this->criterion->id => 1], 'review' => 'Private peer comment']);
        $page = $this->get(route('ratings.index'))->assertOk()->viewData('page');
        $this->assertSame(1, data_get($page, 'props.stats.total'));
        $this->assertSame(5.0, data_get($page, 'props.stats.average'));
        $this->assertSame(1, array_sum(data_get($page, 'props.charts.by_type.series')));
        $this->assertSame([$local->invitation], array_column(data_get($page, 'props.invitations'), 'invitation'));
        $this->assertStringNotContainsString('Private peer comment', json_encode(data_get($page, 'props')));
        $this->assertArrayNotHasKey('user', data_get($page, 'props.ratings.data.0'));
        $this->assertArrayNotHasKey('rater', data_get($page, 'props.ratings.data.0'));
        $page = $this->asPortal($this->recipient)->get(route('portal.ratings.index'))->assertOk()->viewData('page');
        $this->assertEqualsCanonicalizing([$local->invitation, $foreign->invitation], array_column(data_get($page, 'props.invitations.data'), 'invitation'));
        $other = $this->portalAccount();
        $page = $this->asPortal($other)->get(route('portal.ratings.index'))->assertOk()->viewData('page');
        $this->assertSame([], data_get($page, 'props.invitations.data'));
    }

    public function test_peer_or_wrong_customer_subjects_and_unowned_types_are_closed(): void
    {
        $localSample = VAPSampleEntry::factory()->create(['lab_id' => $this->lab->id, 'customer_id' => $this->recipient->customer_id, 'warehouse_id' => $this->recipient->id]);
        $peerSample = VAPSampleEntry::factory()->create(['lab_id' => $this->peer->id, 'customer_id' => $this->recipient->customer_id, 'warehouse_id' => $this->recipient->id]);
        $wrongAccountSample = VAPSampleEntry::factory()->create(['lab_id' => $this->lab->id, 'customer_id' => $this->recipient->customer_id, 'warehouse_id' => $this->portalAccount()->id]);
        foreach ([$peerSample, $wrongAccountSample] as $sample) {
            $this->post(route('ratings.invitations.store'), array_replace($this->invitationPayload(), ['rateable_type' => 'sample_entry', 'rateable_id' => $sample->id]))->assertNotFound();
        }
        $this->get(route('rating.create', ['rateableType' => 'sample_entry', 'rateableId' => $peerSample->id]))->assertNotFound();
        $this->post(route('rating.store', ['rateableType' => 'sample_entry', 'rateableId' => $peerSample->id]), $this->scores())->assertNotFound();
        foreach (['customer_request', 'quality_certificate', 'unknown'] as $type) {
            $this->get(route('rating.create', ['rateableType' => $type, 'rateableId' => 1]))->assertNotFound();
        }
        $this->post(route('ratings.invitations.store'), array_replace($this->invitationPayload(), ['rateable_type' => 'sample_entry', 'rateable_id' => $localSample->id]))->assertSessionHasNoErrors();
        $this->assertSame(1, RatingRequest::query()->where('lab_id', $this->lab->id)->count());
    }

    public function test_revoked_expired_and_changed_recipient_customer_invitations_cannot_respond(): void
    {
        $invitation = $this->issue();
        $this->post(route('ratings.invitations.revoke', ['invitation' => $invitation->invitation]))->assertRedirect();
        $this->assertSame('revoked', $invitation->fresh()->status);
        $this->asPortal($this->recipient)->get(route('portal.rating.create', ['invitation' => $invitation->invitation]))->assertNotFound();
        $this->post(route('portal.rating.store', ['invitation' => $invitation->invitation]), $this->scores())->assertNotFound();

        $next = $this->issue();
        $this->assertNotSame($invitation->invitation, $next->invitation);
        $this->travel(31)->days();
        $this->get(route('portal.rating.create', ['invitation' => $next->invitation]))->assertNotFound();
        $renewed = $this->issue();
        $this->assertSame('expired', $next->fresh()->status);
        $this->assertNotSame($renewed->invitation, $next->invitation);
        $this->recipient->update(['customer_id' => Customer::query()->create(['name' => 'Changed customer', 'code' => fake()->unique()->bothify('CU-######')])->id]);
        $this->get(route('portal.rating.create', ['invitation' => $renewed->invitation]))->assertNotFound();
        $page = $this->get(route('portal.ratings.index'))->assertOk()->viewData('page');
        $this->assertSame([], data_get($page, 'props.invitations.data'));
    }

    public function test_permission_membership_and_recipient_eligibility_are_rechecked(): void
    {
        $plain = $this->member($this->lab, false);
        $this->actingAs($plain)->get(route('ratings.index'))->assertForbidden();
        $this->post(route('ratings.invitations.store'), $this->invitationPayload())->assertForbidden();
        $invitation = $this->issue();
        $this->post(route('ratings.invitations.revoke', ['invitation' => $invitation->invitation]))->assertForbidden();
        DB::table('lab_user')->where('user_id', $this->operator->id)->delete();
        try {
            $this->issue();
            $this->fail('Direct action must recheck membership.');
        } catch (AuthorizationException) {
            $this->assertSame(1, RatingRequest::query()->where('lab_id', $this->lab->id)->count());
        }
        $this->recipient->update(['email_verified_at' => null]);
        $this->asPortal($this->recipient)->get(route('portal.rating.create', ['invitation' => $invitation->invitation]))->assertNotFound();
    }

    public function test_forged_identity_and_invalid_or_missing_criteria_do_not_complete_invitation(): void
    {
        $invitation = $this->issue();
        foreach (['lab_id' => $this->peer->id, 'rater_id' => 999, 'criteria_snapshot' => [['id' => 999]]] as $field => $value) {
            $this->post(route('ratings.invitations.store'), $this->invitationPayload() + [$field => $value])->assertSessionHasErrors($field);
        }
        $url = route('portal.rating.store', ['invitation' => $invitation->invitation]);
        $this->asPortal($this->recipient);
        foreach ([[], [$this->criterion->id => 0], [$this->criterion->id => 6], [$this->criterion->id => 5, 999999 => 4]] as $criteria) {
            $this->post($url, ['criteria' => $criteria])->assertSessionHasErrors();
        }
        $this->post($url, $this->scores() + ['lab_id' => $this->peer->id])->assertSessionHasErrors('lab_id');
        $this->assertSame('pending', $invitation->fresh()->status);
        $this->assertFalse(Rating::query()->where('rating_request_id', $invitation->id)->exists());
    }

    public function test_identity_evidence_and_required_owner_are_enforced(): void
    {
        $invitation = $this->issue();
        $rating = app(SubmitRating::class)->portal($invitation->invitation, $this->recipient->id, $this->scores());
        foreach ([['lab_id' => $this->peer->id], ['review' => 'Changed evidence'], ['criteria' => []]] as $change) {
            try {
                $rating->update($change);
                $this->fail('Survey evidence must be immutable.');
            } catch (LogicException) {
                $rating->refresh();
                $this->assertSame($this->lab->id, $rating->lab_id);
            }
        }
        try {
            $invitation->update(['rater_id' => $this->portalAccount()->id]);
            $this->fail('Issued recipient must be immutable.');
        } catch (LogicException) {
            $this->assertSame($this->recipient->id, $invitation->fresh()->rater_id);
        }
        DB::beginTransaction();
        try {
            DB::table('ratings')->where('id', $rating->id)->update(['lab_id' => null]);
            $this->fail('Database owner must be required.');
        } catch (QueryException $exception) {
            $this->assertSame('23502', $exception->errorInfo[0]);
        } finally {
            DB::rollBack();
        }
        DB::beginTransaction();
        try {
            DB::table('ratings')->where('id', $rating->id)->update(['lab_id' => $this->peer->id]);
            $this->fail('Database response owner must match invitation.');
        } catch (QueryException $exception) {
            $this->assertSame('23503', $exception->errorInfo[0]);
        } finally {
            DB::rollBack();
        }
    }

    public function test_notifications_recheck_invitation_and_member_access_at_delivery(): void
    {
        $invitation = $this->issue();
        $notice = new OperationalNotification(['key' => 'quality.rating.requested', 'context' => ['lab_id' => $this->lab->id, 'invitation' => $invitation->invitation]]);
        $this->assertTrue($notice->shouldSend($this->recipient, 'mail'));
        $this->assertFalse($notice->shouldSend($this->portalAccount(), 'mail'));
        $invitation->update(['status' => 'revoked']);
        $this->assertFalse($notice->shouldSend($this->recipient, 'mail'));
        $received = new OperationalNotification(['key' => 'quality.rating.received', 'context' => ['lab_id' => $this->lab->id]]);
        $this->assertTrue($received->shouldSend($this->operator, 'database'));
        $this->assertFalse($received->shouldSend($this->peerOperator, 'database'));
        DB::table('lab_user')->where('user_id', $this->operator->id)->delete();
        $this->assertFalse($received->shouldSend($this->operator, 'database'));
        $this->recipient->delete();
        $this->assertFalse($notice->shouldSend($this->recipient, 'mail'));
    }

    public function test_notification_preparation_failure_rolls_back_response_and_completion(): void
    {
        $invitation = $this->issue();
        $this->mock(NotificationTemplateService::class)->shouldReceive('notify')->once()->andThrow(new \RuntimeException('Notification preparation failed'));
        try {
            app(SubmitRating::class)->portal($invitation->invitation, $this->recipient->id, $this->scores());
            $this->fail('Preparation failure must abort the transaction.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Notification preparation failed', $exception->getMessage());
        }
        $this->assertSame('pending', $invitation->fresh()->status);
        $this->assertFalse(Rating::query()->where('rating_request_id', $invitation->id)->exists());
    }

    public function test_peer_invitation_cannot_be_revoked_and_recorded_response_cannot_be_cancelled(): void
    {
        $peerInvitation = $this->issue($this->peer, $this->peerOperator);
        $this->post(route('ratings.invitations.revoke', ['invitation' => $peerInvitation->invitation]))->assertNotFound();
        $this->assertSame('pending', $peerInvitation->fresh()->status);
        $local = $this->issue();
        app(SubmitRating::class)->portal($local->invitation, $this->recipient->id, $this->scores());
        $this->post(route('ratings.invitations.revoke', ['invitation' => $local->invitation]))->assertStatus(409);
        $this->assertSame('completed', $local->fresh()->status);
    }

    public function test_average_and_histogram_include_more_than_the_latest_500_responses(): void
    {
        $raters = User::factory()->count(501)->create();
        $rows = $raters->map(fn (User $rater, int $index): array => [
            'lab_id' => $this->lab->id, 'user_id' => $rater->id, 'rateable_type' => 'service', 'rateable_id' => 0,
            'rater_type' => $rater->getMorphClass(), 'rater_id' => $rater->id, 'channel' => 'internal',
            'criteria' => json_encode([$this->criterion->id => $index === 0 ? 1 : 5]),
            'created_at' => now(), 'updated_at' => now(),
        ])->all();
        DB::table('ratings')->insert($rows);
        $page = $this->get(route('ratings.index'))->assertOk()->viewData('page');
        $this->assertSame(501, data_get($page, 'props.stats.total'));
        $this->assertSame(4.99, data_get($page, 'props.stats.average'));
        $this->assertSame([1, 0, 0, 0, 500], data_get($page, 'props.charts.score_distribution.series'));
    }

    public function test_survey_migration_replays_when_empty_and_refuses_to_discard_retained_ownership(): void
    {
        $migration = require database_path('migrations/2026_10_01_133455_add_laboratory_ownership_and_invitations_to_ratings.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('ratings', 'lab_id'));
        $migration->up();
        $this->assertTrue(Schema::hasColumn('ratings', 'lab_id'));
        $this->issue();
        foreach (['up', 'down'] as $method) {
            try {
                $migration->{$method}();
                $this->fail('Retained surveys must not be assigned or discarded implicitly.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('explicit laboratory', $exception->getMessage());
                $this->assertTrue(Schema::hasColumn('rating_requests', 'lab_id'));
            }
        }
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

    private function asPortal(Warehouse $recipient): static
    {
        inertia()->flushShared();

        return $this->actingAs($recipient, 'portal');
    }

    private function portalAccount(): Warehouse
    {
        $customer = Customer::query()->create(['name' => fake()->company(), 'code' => fake()->unique()->bothify('CU-######')]);

        return Warehouse::query()->create([
            'name' => fake()->company(), 'code' => fake()->unique()->bothify('WH-######'), 'email' => fake()->unique()->safeEmail(),
            'customer_id' => $customer->id, 'email_verified_at' => now(),
        ]);
    }

    private function invitationPayload(): array
    {
        return ['recipient_email' => $this->recipient->email, 'rateable_type' => 'service', 'rateable_id' => 0];
    }

    private function scores(): array
    {
        return ['criteria' => [$this->criterion->id => 5], 'review' => 'Recorded feedback'];
    }

    private function issue(?VAPLab $lab = null, ?User $operator = null): RatingRequest
    {
        return app(IssuePortalRatingInvitation::class)->execute(($lab ?? $this->lab)->id, ($operator ?? $this->operator)->id, $this->invitationPayload());
    }
}
