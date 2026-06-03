<?php

namespace Tests\Feature;

use App\Enums\Orders\InventoryOrderTrackingStatus;
use App\Models\CriteriaRating;
use App\Models\Customer;
use App\Models\Department;
use App\Models\InventoryOrder;
use App\Models\Rating;
use App\Models\RatingRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPNonConformity;
use App\Models\VAPNonConformityAction;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Notifications\NonConformityWorkflowNotification;
use App\Notifications\RatingSubmittedNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RatingAndNonConformityModuleTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        return Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
    }

    public function test_order_rating_form_opens_and_completes_pending_request(): void
    {
        $user = $this->verifiedAdmin();
        CriteriaRating::withTrashed()->where('type', 'order')->forceDelete();

        $order = InventoryOrder::query()->create([
            'date' => now()->toDateString(),
            'order_year' => (string) now()->year,
            'user_id' => $user->id,
            'status' => InventoryOrderTrackingStatus::PLACED,
        ]);

        $communication = CriteriaRating::query()->create([
            'name' => 'Comunicação',
            'description' => 'Clareza e acompanhamento do pedido.',
            'type' => 'order',
        ]);

        $delivery = CriteriaRating::query()->create([
            'name' => 'Entrega',
            'description' => 'Cumprimento dos prazos acordados.',
            'type' => 'order',
        ]);

        RatingRequest::query()->create([
            'user_id' => $user->id,
            'rateable_type' => 'order',
            'rateable_id' => $order->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('rating.create', ['rateableType' => 'order', 'rateableId' => $order->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RateForm')
                ->has('criteria', 2)
                ->where('storeRoute', 'rating.store')
                ->where('returnRoute', 'dashboard')
                ->where('ratingRequest.status', 'pending'));

        $this->actingAs($user)
            ->post(route('rating.store', ['rateableType' => 'order', 'rateableId' => $order->id]), [
                'criteria' => [
                    $communication->id => 5,
                    $delivery->id => 4,
                ],
                'review' => 'Fluxo de avaliação validado.',
            ])
            ->assertRedirect(route('dashboard'));

        $rating = Rating::query()->where('user_id', $user->id)->where('rateable_id', $order->id)->firstOrFail();

        $this->assertSame(5, $rating->criteria['Comunicação']);
        $this->assertSame(4, $rating->criteria['Entrega']);

        $this->assertDatabaseHas('rating_requests', [
            'user_id' => $user->id,
            'rateable_type' => 'order',
            'rateable_id' => $order->id,
            'status' => 'completed',
        ]);
    }

    public function test_rating_requires_the_exact_configured_criteria_set(): void
    {
        $user = $this->verifiedAdmin();
        CriteriaRating::withTrashed()->where('type', 'order')->forceDelete();

        $order = InventoryOrder::query()->create([
            'date' => now()->toDateString(),
            'order_year' => (string) now()->year,
            'user_id' => $user->id,
            'status' => InventoryOrderTrackingStatus::PLACED,
        ]);

        CriteriaRating::query()->create([
            'name' => 'Critério obrigatório',
            'type' => 'order',
        ]);

        $this->actingAs($user)
            ->from(route('rating.create', ['rateableType' => 'order', 'rateableId' => $order->id]))
            ->post(route('rating.store', ['rateableType' => 'order', 'rateableId' => $order->id]), [
                'criteria' => [],
            ])
            ->assertSessionHasErrors('criteria');
    }

    public function test_internal_user_can_rate_a_general_service_process(): void
    {
        $user = $this->verifiedAdmin();
        CriteriaRating::withTrashed()->where('type', 'service')->forceDelete();
        Notification::fake();

        $criterion = CriteriaRating::query()->create([
            'name' => 'Experiência geral',
            'type' => 'service',
        ]);

        $this->actingAs($user)
            ->get(route('rating.create', ['rateableType' => 'service']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RateForm')
                ->where('rateableId', 0)
                ->where('rateableLabel', trans('gestlab.rating.subjects.service')));

        $this->actingAs($user)
            ->post(route('rating.store', ['rateableType' => 'service']), [
                'criteria' => [
                    $criterion->id => 5,
                ],
                'review' => 'Processo interno claro e rastreável.',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('ratings', [
            'rateable_type' => 'service',
            'rateable_id' => 0,
            'rater_type' => $user->getMorphClass(),
            'rater_id' => $user->id,
            'channel' => 'internal',
        ]);

        Notification::assertSentTo($user, RatingSubmittedNotification::class);
    }

    public function test_rating_index_exposes_apex_chart_payloads(): void
    {
        $user = $this->verifiedAdmin();

        Rating::query()->create([
            'user_id' => $user->id,
            'rateable_type' => 'service',
            'rateable_id' => 0,
            'rater_type' => $user->getMorphClass(),
            'rater_id' => $user->id,
            'channel' => 'internal',
            'criteria' => ['Experiência geral' => 5],
            'review' => 'Muito bom.',
        ]);

        $this->actingAs($user)
            ->get(route('ratings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ratings/Index')
                ->has('charts.by_type.series')
                ->has('charts.by_channel.series')
                ->has('charts.monthly.series')
                ->has('charts.score_distribution.series'));
    }

    public function test_portal_customer_can_rate_a_sample_entry_process(): void
    {
        CriteriaRating::withTrashed()->where('type', 'sample_entry')->forceDelete();

        $criterion = CriteriaRating::query()->create([
            'name' => 'Comunicação',
            'type' => 'sample_entry',
        ]);

        $customer = Customer::query()->create([
            'name' => 'Cliente Rating ISO',
            'code' => 'CLI-RATE-ISO',
        ]);
        $department = Department::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();

        $warehouse = Warehouse::query()->create([
            'name' => 'Portal Rating ISO',
            'code' => 'WH-RATE-ISO',
            'email' => 'portal-rating-iso@example.test',
            'customer_id' => $customer->id,
            'email_verified_at' => now(),
        ]);

        $sampleEntry = VAPSampleEntry::query()->create([
            'name' => 'Amostra de água',
            'code' => 'SAM-RATE-ISO',
            'status' => 'POR_INICIAR',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'department_id' => $department->id,
            'lab_id' => $lab->id,
            'sample_year' => (string) now()->year,
        ]);

        RatingRequest::query()->create([
            'rateable_type' => 'sample_entry',
            'rateable_id' => $sampleEntry->id,
            'rater_type' => $warehouse->getMorphClass(),
            'rater_id' => $warehouse->id,
            'channel' => 'portal',
            'status' => 'pending',
        ]);

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.rating.create', ['rateableType' => 'sample_entry', 'rateableId' => $sampleEntry->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RateForm')
                ->where('storeRoute', 'portal.rating.store')
                ->where('returnRoute', 'portal.home')
                ->where('ratingRequest.status', 'pending'));

        $this->actingAs($warehouse, 'portal')
            ->post(route('portal.rating.store', ['rateableType' => 'sample_entry', 'rateableId' => $sampleEntry->id]), [
                'criteria' => [
                    $criterion->id => 4,
                ],
                'review' => 'Boa comunicação sobre a amostra.',
            ])
            ->assertRedirect(route('portal.home'));

        $this->assertDatabaseHas('ratings', [
            'user_id' => null,
            'rateable_type' => 'sample_entry',
            'rateable_id' => $sampleEntry->id,
            'rater_type' => $warehouse->getMorphClass(),
            'rater_id' => $warehouse->id,
            'channel' => 'portal',
        ]);

        $this->assertDatabaseHas('rating_requests', [
            'rateable_type' => 'sample_entry',
            'rateable_id' => $sampleEntry->id,
            'rater_type' => $warehouse->getMorphClass(),
            'rater_id' => $warehouse->id,
            'status' => 'completed',
        ]);
    }

    public function test_non_conformity_update_cannot_mutate_actions_from_another_record(): void
    {
        $user = $this->verifiedAdmin();

        $first = $this->makeNonConformity($user, 'NC-SCOPE-001');
        $second = $this->makeNonConformity($user, 'NC-SCOPE-002');

        VAPNonConformityAction::query()->create([
            'nc_id' => $first->id,
            'correction' => 'Ação original',
        ]);

        $otherAction = VAPNonConformityAction::query()->create([
            'nc_id' => $second->id,
            'correction' => 'Não deve mudar',
        ]);

        $this->actingAs($user)
            ->put(route('vap_non_conformities.update', $first), array_merge($this->nonConformityPayload($first), [
                'actions' => [
                    [
                        'id' => $otherAction->id,
                        'correction' => 'Tentativa indevida',
                    ],
                ],
            ]))
            ->assertNotFound();

        $this->assertSame('Não deve mudar', $otherAction->fresh()->correction);
    }

    public function test_non_conformity_update_can_remove_all_actions(): void
    {
        $user = $this->verifiedAdmin();
        $nonConformity = $this->makeNonConformity($user, 'NC-SCOPE-003');

        VAPNonConformityAction::query()->create([
            'nc_id' => $nonConformity->id,
            'correction' => 'A remover',
        ]);

        $this->actingAs($user)
            ->put(route('vap_non_conformities.update', $nonConformity), array_merge($this->nonConformityPayload($nonConformity), [
                'actions' => [],
            ]))
            ->assertRedirect(route('vap_non_conformities.show', $nonConformity));

        $this->assertSame(0, $nonConformity->actions()->count());
    }

    public function test_non_conformity_index_exposes_apex_chart_payloads(): void
    {
        $user = $this->verifiedAdmin();
        $this->makeNonConformity($user, 'NC-CHART-001');

        $this->actingAs($user)
            ->get(route('vap_non_conformities.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPNonConformities/Index')
                ->has('charts.status.series')
                ->has('charts.severity.series')
                ->has('charts.category.series')
                ->has('charts.trend.series'));
    }

    public function test_non_conformity_breadcrumbs_use_module_labels(): void
    {
        $user = $this->verifiedAdmin();
        $nonConformity = $this->makeNonConformity($user, 'NC-BREADCRUMB-001');

        $this->actingAs($user)
            ->get(route('vap_non_conformities.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('breadcrumbs.0.title', trans('gestlab.general.labels.vap_non_conformities.title'))
                ->where('breadcrumbs.0.current', true));

        $this->actingAs($user)
            ->get(route('vap_non_conformities.show', $nonConformity))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('breadcrumbs.0.title', trans('gestlab.general.labels.vap_non_conformities.title'))
                ->where('breadcrumbs.0.current', false)
                ->where('breadcrumbs.1.title', trans('gestlab.general.labels.vap_non_conformities.details_title'))
                ->where('breadcrumbs.1.current', true));
    }

    public function test_non_conformity_creation_sends_workflow_notification(): void
    {
        $user = $this->verifiedAdmin();
        Notification::fake();

        $this->actingAs($user)
            ->post(route('vap_non_conformities.store'), [
                'nc_number' => 'NC-NOTIFY-001',
                'title' => 'Falha crítica em ensaio',
                'description' => 'Resultado fora dos critérios de aceitação.',
                'status' => 'opened',
                'severity' => 'critical',
                'category' => 'quality',
                'reported_by' => $user->name,
                'reported_by_id' => $user->id,
                'assigned_to_id' => $user->id,
                'reported_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect(route('vap_non_conformities.index'));

        Notification::assertSentTo($user, NonConformityWorkflowNotification::class);
    }

    public function test_non_conformity_can_store_and_show_media_attachments(): void
    {
        Storage::fake('public');
        Notification::fake();

        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->post(route('vap_non_conformities.store'), [
                'nc_number' => 'NC-ATTACH-001',
                'title' => 'Evidência com anexo',
                'description' => 'Registo com evidência anexada.',
                'status' => 'opened',
                'severity' => 'medium',
                'category' => 'quality',
                'reported_by' => $user->name,
                'reported_by_id' => $user->id,
                'reported_at' => now()->toDateTimeString(),
                'attachment_files' => [
                    UploadedFile::fake()->create('evidencia.pdf', 128, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('vap_non_conformities.index'));

        $nonConformity = VAPNonConformity::query()
            ->where('nc_number', 'NC-ATTACH-001')
            ->firstOrFail();

        $this->assertSame(1, $nonConformity->getMedia('attachments')->count());

        $this->actingAs($user)
            ->get(route('vap_non_conformities.show', $nonConformity))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('nonConformity.media_attachments.0.file_name', 'evidencia.pdf'));
    }

    private function makeNonConformity(User $user, string $number): VAPNonConformity
    {
        return VAPNonConformity::query()->create([
            'nc_number' => $number,
            'title' => 'Desvio de ensaio',
            'description' => 'Resultado fora do procedimento aprovado.',
            'status' => 'opened',
            'severity' => 'high',
            'category' => 'quality',
            'reported_by' => $user->name,
            'reported_by_id' => $user->id,
            'reported_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function nonConformityPayload(VAPNonConformity $nonConformity): array
    {
        return [
            'lab_id' => $nonConformity->lab_id,
            'department_id' => $nonConformity->department_id,
            'nc_number' => $nonConformity->nc_number,
            'title' => $nonConformity->title,
            'description' => $nonConformity->description,
            'status' => $nonConformity->status,
            'severity' => $nonConformity->severity,
            'category' => $nonConformity->category,
            'reported_by' => $nonConformity->reported_by,
            'reported_by_id' => $nonConformity->reported_by_id,
            'reported_at' => $nonConformity->reported_at->toDateTimeString(),
        ];
    }
}
