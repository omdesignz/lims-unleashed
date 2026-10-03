<?php

namespace Tests\Feature;

use App\Actions\SetProposalTemplatesArchived;
use App\Models\Customer;
use App\Models\Department;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LegacyProposalTemplateWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        config(['broadcasting.default' => 'null']);
        $this->operator = User::factory()->create(['is_active' => true]);
        foreach (['view_proposal_templates', 'add_proposal_templates', 'edit_proposal_templates', 'delete_proposal_templates', 'restore_proposal_templates'] as $permission) {
            $this->operator->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($this->operator);
    }

    private function template(): VAPProposalTemplate
    {
        return VAPProposalTemplate::create([
            'name' => 'Template workflow '.str()->uuid(), 'content' => '<p>Retained commercial evidence</p>',
            'user_id' => $this->operator->id, 'is_active' => true,
        ]);
    }

    /** @return list<array{string, string}> */
    public static function endpoints(): array
    {
        return [['get', 'index'], ['get', 'create'], ['get', 'edit'], ['get', 'getProposalTemplate'], ['delete', 'destroy'], ['patch', 'restore']];
    }

    #[DataProvider('endpoints')]
    public function test_retained_routes_require_the_matching_template_permission(string $method, string $action): void
    {
        $template = $this->template();
        $this->operator->syncPermissions([]);
        $parameters = $action === 'edit' ? ['template' => $template->id] : [];
        $this->{$method}(route('proposaltemplates.'.$action, $parameters), ['recordIds' => [$template->id]])->assertForbidden();
        $this->assertFalse($template->fresh()->trashed());
    }

    public function test_duplicate_authoring_redirects_and_writes_are_retired(): void
    {
        $template = $this->template();
        $this->get(route('proposaltemplates.create'))->assertRedirect(route('vap-proposals.templates.create'));
        $this->get(route('proposaltemplates.edit', $template->id))->assertRedirect(route('vap-proposals.templates.edit', $template));
        $this->assertFalse(Route::has('proposaltemplates.store'));
        $this->assertFalse(Route::has('proposaltemplates.update'));
        $before = $template->fresh()->getRawOriginal();
        $this->postJson('/proposaltemplates', ['name' => 'Forged'])->assertStatus(405);
        $this->putJson('/proposaltemplates/'.$template->id, ['content' => 'Forged'])->assertNotFound();
        $this->assertSame($before, $template->fresh()->getRawOriginal());
        $this->get(route('vap-proposals.templates.create'))->assertOk();
        $this->get(route('vap-proposals.templates.edit', $template))->assertOk();
    }

    public function test_get_and_head_cannot_archive_or_restore_templates(): void
    {
        $template = $this->template();
        foreach (['destroy', 'restore'] as $action) {
            $url = route('proposaltemplates.'.$action, ['recordIds' => [$template->id]]);
            $this->get($url)->assertStatus(405);
            $this->head($url)->assertStatus(405);
        }
        $this->assertFalse($template->fresh()->trashed());
    }

    public function test_archive_restore_is_idempotent_and_retains_content_and_audit(): void
    {
        $template = $this->template();
        $before = $template->fresh()->only(['name', 'content', 'user_id', 'is_active']);
        $start = Activity::count();
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->delete(route('proposaltemplates.destroy'), ['recordIds' => [$template->id]])->assertRedirect();
        }
        $this->assertTrue($template->fresh()->trashed());
        $this->assertSame($start + 1, Activity::count());
        $audit = Activity::where('subject_type', 'proposal_template')->where('subject_id', $template->id)->firstOrFail();
        $this->assertSame($template->id, $audit->subject()->withTrashed()->firstOrFail()->id);
        $list = $this->get(route('proposaltemplates.index', ['filter' => ['trashed' => 'only']]))->assertOk();
        $row = collect($list->inertiaProps('record.data'))->firstWhere('id', $template->id);
        $this->assertTrue($row['deleted']);
        $this->assertFalse($row['can_archive']);
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->patch(route('proposaltemplates.restore'), ['recordIds' => [$template->id]])->assertRedirect();
        }
        $this->assertFalse($template->fresh()->trashed());
        $this->assertSame($before, $template->fresh()->only(['name', 'content', 'user_id', 'is_active']));
        $this->assertSame($start + 2, Activity::count());
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function invalidBatches(): array
    {
        return ['missing' => [[]], 'empty' => [['recordIds' => []]], 'scalar' => [['recordIds' => 1]],
            'negative' => [['recordIds' => [-1]]], 'duplicate' => [['recordIds' => [1, 1]]],
            'associative' => [['recordIds' => ['first' => 1]]], 'too_many' => [['recordIds' => range(1, 101)]]];
    }

    #[DataProvider('invalidBatches')]
    public function test_invalid_archive_batches_fail_before_writes(array $payload): void
    {
        $template = $this->template();
        $this->deleteJson(route('proposaltemplates.destroy'), $payload)->assertUnprocessable();
        $this->assertFalse($template->fresh()->trashed());
    }

    public function test_missing_batch_member_rolls_back_without_archiving_valid_templates(): void
    {
        $template = $this->template();
        $this->deleteJson(route('proposaltemplates.destroy'), ['recordIds' => [$template->id, 2147483647]])->assertNotFound();
        $this->assertFalse($template->fresh()->trashed());
    }

    /** @return list<array{bool}> */
    public static function booleans(): array
    {
        return [[true], [false]];
    }

    #[DataProvider('booleans')]
    public function test_templates_used_by_any_lab_or_archived_proposal_cannot_be_archived(bool $archivedProposal): void
    {
        $template = $this->template();
        $unused = $this->template();
        $proposal = $this->proposalForTemplate($template);
        if ($archivedProposal) {
            $proposal->delete();
        }
        $before = Activity::count();
        $this->deleteJson(route('proposaltemplates.destroy'), ['recordIds' => [$unused->id, $template->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('recordIds');
        $this->deleteJson(route('vap-proposals.templates.destroy', $template))->assertUnprocessable();
        $this->assertFalse($template->fresh()->trashed());
        $this->assertFalse($unused->fresh()->trashed());
        $this->assertSame($before, Activity::count());
        $list = $this->get(route('proposaltemplates.index'))->assertOk();
        $row = collect($list->inertiaProps('record.data'))->firstWhere('id', $template->id);
        $this->assertFalse($row['can_archive']);
        $this->assertArrayNotHasKey('proposals_exists', $row);
        $this->assertArrayNotHasKey('proposals_count', $row);
    }

    private function withListener(string $event, callable $listener, callable $check): void
    {
        $existing = Event::getRawListeners()[$event] ?? [];
        Event::listen($event, $listener);
        try {
            $check();
        } finally {
            Event::forget($event);
            foreach ($existing as $original) {
                Event::listen($event, $original);
            }
        }
    }

    #[DataProvider('booleans')]
    public function test_lifecycle_veto_rolls_back_the_entire_archive_or_restore_batch(bool $archived): void
    {
        $first = $this->template();
        $last = $this->template();
        if (! $archived) {
            $first->delete();
            $last->delete();
        }
        $before = Activity::count();
        $this->withListener('eloquent.'.($archived ? 'deleting' : 'restoring').': '.VAPProposalTemplate::class,
            fn (VAPProposalTemplate $record): ?bool => $record->id === $last->id ? false : null,
            function () use ($first, $last, $archived, $before): void {
                try {
                    app(SetProposalTemplatesArchived::class)->execute($this->operator->id, [$last->id, $first->id], $archived);
                    $this->fail('Lifecycle veto must fail closed.');
                } catch (HttpException $exception) {
                    $this->assertSame(409, $exception->getStatusCode());
                }
                $this->assertSame(! $archived, $first->fresh()->trashed());
                $this->assertSame(! $archived, $last->fresh()->trashed());
                $this->assertSame($before, Activity::count());
            });
    }

    public function test_audit_veto_rolls_back_the_template_archive(): void
    {
        $template = $this->template();
        $this->withListener('eloquent.creating: '.config('activitylog.activity_model'), fn (): bool => false,
            function () use ($template): void {
                $this->deleteJson(route('proposaltemplates.destroy'), ['recordIds' => [$template->id]])->assertStatus(409);
                $this->assertFalse($template->fresh()->trashed());
            });
    }

    public function test_action_rechecks_fresh_permissions(): void
    {
        $template = $this->template();
        $this->operator->can('delete_proposal_templates');
        User::findOrFail($this->operator->id)->syncPermissions([]);
        try {
            app(SetProposalTemplatesArchived::class)->execute($this->operator->id, [$template->id], true);
            $this->fail('Cached permissions must not authorize a fresh action.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertFalse($template->fresh()->trashed());
    }

    public function test_action_rechecks_active_account_state_after_template_write_hooks(): void
    {
        $template = $this->template();
        $before = Activity::count();
        $this->withListener('eloquent.deleted: '.VAPProposalTemplate::class,
            function (): void {
                User::findOrFail($this->operator->id)->update(['is_active' => false]);
            },
            function () use ($template, $before): void {
                $this->deleteJson(route('proposaltemplates.destroy'), ['recordIds' => [$template->id]])->assertForbidden();
                $this->assertFalse($template->fresh()->trashed());
                $this->assertTrue($this->operator->fresh()->is_active);
                $this->assertSame($before, Activity::count());
            });
    }

    public function test_late_proposal_reference_rejects_archive_and_rolls_back_its_write_hook(): void
    {
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $this->operator->id]);
        $this->withSession(['active_lab_id' => $lab->id]);
        $template = $this->template();
        $before = VAPProposal::withoutGlobalScope('proposal_laboratory')->withTrashed()->count();
        $this->withListener('eloquent.deleted: '.VAPProposalTemplate::class,
            function () use ($template): void {
                $this->proposalForTemplate($template);
            },
            function () use ($template, $before): void {
                $this->deleteJson(route('proposaltemplates.destroy'), ['recordIds' => [$template->id]])->assertUnprocessable();
                $this->assertFalse($template->fresh()->trashed());
                $this->assertSame($before, VAPProposal::withoutGlobalScope('proposal_laboratory')->withTrashed()->count());
            });
    }

    private function proposalForTemplate(VAPProposalTemplate $template): VAPProposal
    {
        $customer = Customer::create(['name' => fake()->company()]);
        $site = Warehouse::create(['name' => 'Template site '.str()->uuid(), 'customer_id' => $customer->id]);
        $proposal = new VAPProposal([
            'proposal_year' => now()->year, 'proposal_no' => 'T-'.str()->uuid(),
            'customer_id' => $customer->id, 'warehouse_id' => $site->id,
            'department_id' => Department::factory()->create()->id, 'template_id' => $template->id,
            'user_id' => $this->operator->id, 'status' => 'PENDING', 'details' => [],
            'unique_hash' => (string) str()->uuid(),
        ]);
        $proposal->lab_id = VAPLab::factory()->create()->id;
        $proposal->save();

        return $proposal;
    }

    public function test_lookup_returns_only_live_template_identity(): void
    {
        $template = $this->template();
        $archived = $this->template();
        $archived->delete();
        $response = $this->getJson(route('proposaltemplates.getProposalTemplate', ['q' => $template->name]))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $template->id);
        $this->assertSame(['id', 'name'], array_keys($response->json('0')));
        $this->getJson(route('proposaltemplates.getProposalTemplate', ['q' => $archived->name]))->assertOk()->assertExactJson([]);
    }
}
