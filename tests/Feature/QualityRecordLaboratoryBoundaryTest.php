<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\EnvironmentalCondition;
use App\Models\ManagementReview;
use App\Models\Role;
use App\Models\UncertaintySource;
use App\Models\User;
use App\Models\VAPLab;
use App\Notifications\OperationalNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use LogicException;
use Tests\TestCase;

class QualityRecordLaboratoryBoundaryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_quality_pages_and_indicators_only_show_active_laboratory_records(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->member($lab);

        $localComplaint = $this->complaint($lab, 'Local complaint');
        $this->complaint($peer, 'Peer complaint');
        $localReview = $this->review($lab);
        $this->review($peer);
        $localSource = $this->source($lab, 'Local uncertainty');
        $this->source($peer, 'Peer uncertainty');
        $localCondition = $this->condition($lab, 'Local room');
        $this->condition($peer, 'Peer room');

        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        $complaints = $this->get(route('complaints.index'));
        $complaints->assertOk();
        $this->assertSame(1, data_get($complaints->viewData('page'), 'props.stats.total'));
        $this->assertSame($localComplaint->id, data_get($complaints->viewData('page'), 'props.complaints.data.0.id'));
        $this->assertCount(1, data_get($complaints->viewData('page'), 'props.complaints.data'));

        $reviews = $this->get(route('management-reviews.index'));
        $reviews->assertOk();
        $this->assertSame(1, data_get($reviews->viewData('page'), 'props.stats.planned'));
        $this->assertSame($localReview->id, data_get($reviews->viewData('page'), 'props.reviews.data.0.id'));
        $this->assertCount(1, data_get($reviews->viewData('page'), 'props.reviews.data'));

        $sources = $this->get(route('uncertainty-sources.index'));
        $sources->assertOk();
        $this->assertSame($localSource->id, data_get($sources->viewData('page'), 'props.sources.0.id'));
        $this->assertCount(1, data_get($sources->viewData('page'), 'props.sources'));

        $conditions = $this->get(route('environmental-conditions.index'));
        $conditions->assertOk();
        $this->assertSame(1, data_get($conditions->viewData('page'), 'props.stats.total'));
        $this->assertSame($localCondition->id, data_get($conditions->viewData('page'), 'props.conditions.data.0.id'));
        $this->assertCount(1, data_get($conditions->viewData('page'), 'props.conditions.data'));

        $qms = $this->get(route('qms.index'));
        $qms->assertOk();
        foreach (['open_complaints', 'scheduled_management_reviews', 'uncertainty_sources', 'environmental_entries_today'] as $metric) {
            $this->assertSame(1, data_get($qms->viewData('page'), "props.summary.{$metric}"));
        }
    }

    public function test_quality_creation_uses_active_laboratory_and_notifies_only_its_members(): void
    {
        Notification::fake();
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $sender = $this->member($lab);
        $localRecipient = $this->member($lab);
        $peerRecipient = $this->member($peer);
        $this->actingAs($sender)->withSession(['active_lab_id' => $lab->id]);

        $this->post(route('complaints.store'), [
            'lab_id' => $peer->id,
            'title' => 'Local concern',
            'description' => 'A customer concern for this laboratory.',
            'severity' => 'high',
            'confidentiality_level' => 'internal',
            'reported_by_name' => 'Quality lead',
            'assigned_to_id' => $localRecipient->id,
        ])->assertSessionHasNoErrors();

        $this->post(route('management-reviews.store'), [
            'lab_id' => $peer->id,
            'review_date' => now()->addWeek()->toDateString(),
            'scope' => 'Local review',
            'conducted_by_id' => $localRecipient->id,
        ])->assertSessionHasNoErrors();

        $this->post(route('uncertainty-sources.store'), [
            'lab_id' => $peer->id,
            'title' => 'Local source',
            'source_type' => 'method',
        ])->assertSessionHasNoErrors();

        $this->post(route('environmental-conditions.store'), [
            'lab_id' => $peer->id,
            'area' => 'Local room',
            'recorded_at' => now()->toDateTimeString(),
            'temperature_c' => 21,
        ])->assertSessionHasNoErrors();

        foreach (['complaints', 'management_reviews', 'uncertainty_sources', 'environmental_conditions'] as $table) {
            $this->assertSame(1, DB::table($table)->where('lab_id', $lab->id)->count());
            $this->assertSame(0, DB::table($table)->where('lab_id', $peer->id)->count());
        }

        Notification::assertSentTo($localRecipient, OperationalNotification::class);
        Notification::assertNotSentTo($peerRecipient, OperationalNotification::class);
    }

    public function test_foreign_quality_records_cannot_be_mutated_or_assigned_to_foreign_staff(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->member($lab);
        $peerUser = $this->member($peer);
        $complaint = $this->complaint($peer, 'Peer complaint');
        $review = $this->review($peer);
        $source = $this->source($peer, 'Peer uncertainty');
        $condition = $this->condition($peer, 'Peer room');
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        $this->put(route('complaints.update', $complaint), ['status' => 'closed'])->assertNotFound();
        $this->put(route('management-reviews.update', $review), ['status' => 'completed'])->assertNotFound();
        $this->put(route('uncertainty-sources.update', $source), ['title' => 'Changed', 'source_type' => 'method'])->assertNotFound();
        $this->delete(route('uncertainty-sources.destroy', $source))->assertNotFound();
        $this->put(route('environmental-conditions.update', $condition), ['area' => 'Changed', 'recorded_at' => now()->toDateTimeString()])->assertNotFound();
        $this->delete(route('environmental-conditions.destroy', $condition))->assertNotFound();

        $this->post(route('complaints.store'), [
            'title' => 'Invalid assignment',
            'description' => 'Must stay local.',
            'severity' => 'medium',
            'confidentiality_level' => 'internal',
            'reported_by_name' => 'Quality lead',
            'assigned_to_id' => $peerUser->id,
        ])->assertSessionHasErrors('assigned_to_id');

        $this->post(route('management-reviews.store'), [
            'review_date' => now()->toDateString(),
            'conducted_by_id' => $peerUser->id,
        ])->assertSessionHasErrors('conducted_by_id');

        $this->assertSame('open', $complaint->fresh()->status);
        $this->assertSame('planned', $review->fresh()->status);
        $this->assertNotSoftDeleted($source);
        $this->assertDatabaseHas('environmental_conditions', ['id' => $condition->id, 'area' => 'Peer room']);
    }

    public function test_quality_record_owner_is_immutable(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();

        foreach ([
            $this->complaint($lab, 'Local complaint'),
            $this->review($lab),
            $this->source($lab, 'Local source'),
            $this->condition($lab, 'Local room'),
        ] as $record) {
            try {
                $record->update(['lab_id' => $peer->id]);
                $this->fail('Changing a quality record laboratory must fail.');
            } catch (LogicException) {
                $this->assertSame($lab->id, $record->fresh()->lab_id);
            }
        }
    }

    public function test_local_uncertainty_and_environmental_records_remain_editable_and_archivable(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->member($lab);
        $source = $this->source($lab, 'Initial source');
        $condition = $this->condition($lab, 'Initial room');
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        $this->put(route('uncertainty-sources.update', $source), [
            'title' => 'Updated source',
            'source_type' => 'method',
        ])->assertSessionHasNoErrors();
        $this->put(route('environmental-conditions.update', $condition), [
            'area' => 'Updated room',
            'recorded_at' => now()->toDateTimeString(),
            'temperature_c' => 22,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Updated source', $source->fresh()->title);
        $this->assertSame('Updated room', $condition->fresh()->area);
        $this->assertSame($lab->id, $source->fresh()->lab_id);
        $this->assertSame($lab->id, $condition->fresh()->lab_id);

        $this->delete(route('uncertainty-sources.destroy', $source))->assertSessionHasNoErrors();
        $this->delete(route('environmental-conditions.destroy', $condition))->assertSessionHasNoErrors();
        $this->assertSoftDeleted($source);
        $this->assertDatabaseMissing('environmental_conditions', ['id' => $condition->id]);
    }

    private function member(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    private function complaint(VAPLab $lab, string $title): Complaint
    {
        return Complaint::query()->create([
            'lab_id' => $lab->id,
            'title' => $title,
            'description' => 'Quality concern.',
            'reported_by_name' => 'Quality lead',
        ]);
    }

    private function review(VAPLab $lab): ManagementReview
    {
        return ManagementReview::query()->create([
            'lab_id' => $lab->id,
            'review_date' => now()->addWeek()->toDateString(),
        ]);
    }

    private function source(VAPLab $lab, string $title): UncertaintySource
    {
        return UncertaintySource::query()->create([
            'lab_id' => $lab->id,
            'title' => $title,
            'source_type' => 'method',
        ]);
    }

    private function condition(VAPLab $lab, string $area): EnvironmentalCondition
    {
        return EnvironmentalCondition::query()->create([
            'lab_id' => $lab->id,
            'area' => $area,
            'recorded_at' => now(),
            'status' => 'within_limits',
            'temperature_c' => 21,
        ]);
    }
}
