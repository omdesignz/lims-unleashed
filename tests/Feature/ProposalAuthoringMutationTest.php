<?php

namespace Tests\Feature;

use App\Actions\CreateProposal;
use App\Actions\DownloadStaffProposalPdf;
use App\Actions\ReviseProposal;
use App\Actions\SendProposal;
use App\Models\Customer;
use App\Models\Department;
use App\Models\ISOActivityLog;
use App\Models\Matrix;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Models\VAPProposalComplianceAgreement;
use App\Models\VAPProposalItem;
use App\Models\VAPProposalTemplate;
use App\Models\Warehouse;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProposalAuthoringMutationTest extends TestCase
{
    use DatabaseTransactions;

    private User $operator;

    private VAPProposal $proposal;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        config(['broadcasting.default' => 'null', 'mail.default' => 'array']);
        Storage::fake(config('filesystems.default', 'local'));
        $this->operator = User::factory()->create(['is_active' => true]);
        foreach (['add_proposals', 'edit_proposals', 'view_proposals'] as $permission) {
            $this->operator->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $this->operator->id]);
        $customer = Customer::create(['name' => 'Authoring customer']);
        $site = Warehouse::create(['name' => 'Authoring site', 'customer_id' => $customer->id]);
        $template = VAPProposalTemplate::create(['name' => 'Authoring template', 'content' => '<p>Authoring</p>', 'user_id' => $this->operator->id]);
        $this->unit = Unit::create(['code' => 'U-'.str()->uuid(), 'description' => 'Authoring unit']);
        $this->proposal = new VAPProposal([
            'proposal_year' => now()->year, 'status' => 'PENDING', 'unique_hash' => (string) str()->uuid(),
            'customer_id' => $customer->id, 'warehouse_id' => $site->id, 'template_id' => $template->id,
            'department_id' => Department::factory()->create()->id, 'user_id' => $this->operator->id,
            'service_location' => 'Original site', 'obs' => 'Original observation', 'details' => ['private' => true],
        ]);
        $this->proposal->lab_id = $lab->id;
        $this->proposal->save();
        $this->proposal->items()->create(['item_description' => 'Original item', 'unit_id' => $this->unit->id, 'qty' => 1, 'unit_price' => 20, 'total' => 20]);
        $this->proposal->complianceAgreement()->create(['confidentiality' => true, 'impartiality' => false, 'nondisclosure' => true, 'client_ip' => '192.0.2.9']);
        $this->proposal->refresh();
        Notification::fake();
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'customer_id' => $this->proposal->customer_id, 'warehouse_id' => $this->proposal->warehouse_id,
            'department_id' => $this->proposal->department_id, 'template_id' => $this->proposal->template_id,
            'service_location' => 'Revised site', 'obs' => 'Revised observation', 'tolerance_days' => 45,
            'revision_reason' => 'A documented commercial correction.',
            'items' => [['item_description' => 'Revised item', 'qty' => 2, 'unit_price' => 50,
                'unit_id' => $this->unit->id, 'discount_percentage' => 10, 'tax_percentage' => 14, 'charge_tax' => true]],
        ];
    }

    /** @return array<string, mixed> */
    private function evidence(): array
    {
        return [
            'proposal' => $this->proposal->fresh()->getRawOriginal(),
            'items' => $this->proposal->items()->withTrashed()->orderBy('id')->get()->map->getRawOriginal()->all(),
            'agreement' => $this->proposal->complianceAgreement()->firstOrFail()->getRawOriginal(),
            'audit' => DB::table('activity_log')->count(),
        ];
    }

    private function execute(string $operation, ?array $data = null): VAPProposal
    {
        $labId = (int) $this->proposal->lab_id;
        $userId = $this->operator->id;

        return match ($operation) {
            'create' => app(CreateProposal::class)->execute($labId, $userId, $data ?? $this->payload()),
            'revise' => app(ReviseProposal::class)->execute($labId, $userId, $this->proposal, $data ?? $this->payload()),
            'send' => app(SendProposal::class)->execute($labId, $userId, $this->proposal),
        };
    }

    private function renderer(?callable $duringRender = null): void
    {
        $this->mock(ReportStudioPdfRenderer::class, function (MockInterface $mock) use ($duringRender): void {
            $mock->shouldReceive('renderDocument')->once()->andReturnUsing(function () use ($duringRender): array {
                if ($duringRender) {
                    $duringRender();
                }

                return ['content' => '%PDF-authoring', 'renderer' => 'test'];
            });
        });
    }

    public function test_creation_owns_identity_uses_server_totals_and_records_no_client_consent(): void
    {
        $record = $this->execute('create', [...$this->payload(), 'status' => 'ACCEPTED', 'unique_hash' => 'forged',
            'lab_id' => 0, 'user_id' => 0, 'total' => 999, 'sub_total' => 999,
            'confidentiality' => true, 'impartiality' => true, 'nondisclosure' => true, 'file_path' => 'forged.pdf']);
        $this->assertSame($this->proposal->lab_id, $record->lab_id);
        $this->assertSame($this->operator->id, $record->user_id);
        $this->assertSame('PENDING', $record->status);
        $this->assertNotSame('forged', $record->unique_hash);
        $this->assertNull($record->file_path);
        $this->assertSame('90.00', $record->sub_total);
        $this->assertSame('102.60', $record->total);
        $this->assertFalse($record->complianceAgreement->confidentiality);
        $this->assertFalse($record->complianceAgreement->impartiality);
        $this->assertFalse($record->complianceAgreement->nondisclosure);
        $this->assertNull($record->complianceAgreement->acknowledged_at);
        Notification::assertNothingSent();
    }

    #[DataProvider('templateWriteBoundaries')]
    public function test_creation_rejects_changed_template_identity_at_lifecycle_and_audit_boundaries(string $boundary, bool $archived): void
    {
        $other = VAPProposalTemplate::create(['name' => 'Substituted template', 'content' => '<p>Not selected</p>', 'user_id' => $this->operator->id]);
        if ($archived) {
            $other->delete();
        }
        $counts = [];
        foreach (['proposals', 'proposal_items', 'proposal_compliance_agreements', 'activity_log'] as $table) {
            $counts[$table] = DB::table($table)->count();
        }
        $before = $this->evidence();
        if ($boundary === 'creating') {
            VAPProposal::creating(function (VAPProposal $record) use ($other): void {
                $record->template_id = $other->id;
            });
        } elseif ($boundary === 'created') {
            VAPProposal::created(function (VAPProposal $record) use ($other): void {
                DB::table('proposals')->where('id', $record->id)->update(['template_id' => $other->id]);
            });
        } else {
            ISOActivityLog::creating(function (ISOActivityLog $audit) use ($other): void {
                if ($audit->subject_type === $this->proposal->getMorphClass()) {
                    DB::table('proposals')->where('id', $audit->subject_id)->update(['template_id' => $other->id]);
                }
            });
        }
        try {
            $this->execute('create');
            $this->fail('Expected the persisted template identity guard to reject substitution.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
        $this->assertSame($before, $this->evidence());
        foreach ($counts as $table => $count) {
            $this->assertSame($count, DB::table($table)->count(), $table);
        }
    }

    /** @return list<array{string,bool}> */
    public static function templateWriteBoundaries(): array
    {
        return [['creating', false], ['created', false], ['audit', false], ['creating', true], ['created', true], ['audit', true]];
    }

    #[DataProvider('editableStates')]
    public function test_revision_preserves_source_consent_and_archived_items(string $status): void
    {
        DB::table('proposals')->where('id', $this->proposal->id)->update(['status' => $status]);
        $before = $this->evidence();
        $oldItemId = $this->proposal->items()->sole()->id;
        $record = $this->execute('revise', [...$this->payload(), 'customer_id' => 0, 'user_id' => 0,
            'unique_hash' => 'forged', 'status' => 'ACCEPTED', 'file_path' => 'forged.pdf']);
        $this->assertSame('REVISED', $record->status);
        $current = $record->fresh();
        foreach (['lab_id', 'customer_id', 'warehouse_id', 'department_id', 'user_id', 'unique_hash', 'details'] as $field) {
            $this->assertSame($before['proposal'][$field], $current->getRawOriginal($field));
        }
        $this->assertSame($before['agreement'], $record->complianceAgreement()->sole()->getRawOriginal());
        $this->assertSame('Original item', VAPProposalItem::withTrashed()->findOrFail($oldItemId)->item_description);
        $this->assertTrue(VAPProposalItem::withTrashed()->findOrFail($oldItemId)->trashed());
        $this->assertSame(1, $record->items()->count());
        $this->assertSame('Revised item', $record->items->sole()->item_description);
        $this->assertSame(12.6, $record->tax);
        $this->assertSame('102.60', $current->total);
        $this->assertSame($current->created_at->copy()->addDays(45)->toDateString(), $current->expiry_date->toDateString());
        $audit = ISOActivityLog::where('description', 'revised')->where('subject_id', $record->id)->sole();
        $this->assertSame([$oldItemId], $audit->properties['item_changes']['removed']);
        $this->assertNull($audit->properties['old_items'][0]['deleted_at']);
    }

    public static function editableStates(): array
    {
        return ['pending' => ['PENDING'], 'sent' => ['SENT'], 'viewed' => ['VIEWED'], 'rejected' => ['REJECTED']];
    }

    #[DataProvider('writeVetoes')]
    public function test_lifecycle_or_audit_veto_rolls_back_the_whole_write(string $operation, string $model, string $event): void
    {
        $before = $this->evidence();
        $count = VAPProposal::count();
        $dispatcher = Event::getFacadeRoot();
        $modelDispatcher = Model::getEventDispatcher();
        $isolatedDispatcher = clone $dispatcher;
        Event::swap($isolatedDispatcher);
        Model::setEventDispatcher($isolatedDispatcher);
        try {
            if ($operation === 'send') {
                $this->renderer();
            }
            Event::listen("eloquent.{$event}: {$model}", fn (): bool => false);
            try {
                $this->execute($operation);
                $this->fail('A cancelled lifecycle write must fail.');
            } catch (HttpException $exception) {
                $this->assertSame(409, $exception->getStatusCode());
            }
            $this->assertSame($before, $this->evidence());
            $this->assertSame($count, VAPProposal::count());
            $this->assertSame([], Storage::allFiles());
            Notification::assertNothingSent();
        } finally {
            Event::swap($dispatcher);
            Model::setEventDispatcher($modelDispatcher);
        }
    }

    public static function writeVetoes(): array
    {
        return [
            'create proposal' => ['create', VAPProposal::class, 'saving'],
            'create item' => ['create', VAPProposalItem::class, 'saving'],
            'create agreement' => ['create', VAPProposalComplianceAgreement::class, 'saving'],
            'create audit' => ['create', ISOActivityLog::class, 'saving'],
            'revise proposal' => ['revise', VAPProposal::class, 'saving'],
            'revise old item' => ['revise', VAPProposalItem::class, 'deleting'],
            'revise new item' => ['revise', VAPProposalItem::class, 'saving'],
            'revise audit' => ['revise', ISOActivityLog::class, 'saving'],
            'send proposal' => ['send', VAPProposal::class, 'saving'],
            'send audit' => ['send', ISOActivityLog::class, 'saving'],
        ];
    }

    #[DataProvider('operations')]
    public function test_removed_membership_is_rechecked_inside_each_action(string $operation): void
    {
        $before = $this->evidence();
        DB::table('lab_user')->where('lab_id', $this->proposal->lab_id)->where('user_id', $this->operator->id)->delete();
        try {
            $this->execute($operation);
            $this->fail('Removed members must not write.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $this->evidence());
            $this->assertSame([], Storage::allFiles());
        }
    }

    public static function operations(): array
    {
        return ['create' => ['create'], 'revise' => ['revise'], 'send' => ['send']];
    }

    #[DataProvider('operations')]
    public function test_revoked_permission_is_rechecked_inside_each_action(string $operation): void
    {
        $this->operator->revokePermissionTo($operation === 'create' ? 'add_proposals' : 'edit_proposals');
        $before = $this->evidence();
        try {
            $this->execute($operation);
            $this->fail('Revoked permission must block a write.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $this->evidence());
        }
    }

    #[DataProvider('staleStates')]
    public function test_stale_bound_status_cannot_replace_a_terminal_decision(string $operation, string $status): void
    {
        DB::table('proposals')->where('id', $this->proposal->id)->update(['status' => $status]);
        $before = $this->evidence();
        try {
            $this->execute($operation);
            $this->fail('The locked current state must decide eligibility.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
            $this->assertSame($before, $this->evidence());
            $this->assertSame([], Storage::allFiles());
        }
    }

    public static function staleStates(): array
    {
        return ['accepted revision' => ['revise', 'ACCEPTED'], 'expired revision' => ['revise', 'EXPIRED'],
            'repeated revision' => ['revise', 'REVISED'], 'accepted send' => ['send', 'ACCEPTED'], 'repeated send' => ['send', 'SENT']];
    }

    #[DataProvider('renderChanges')]
    public function test_send_rechecks_access_and_content_after_rendering(string $change, int $status): void
    {
        $path = "vap-proposals/{$this->proposal->id}/old.pdf";
        Storage::put($path, 'retained PDF');
        $this->proposal->update(['file_path' => $path]);
        $this->proposal->refresh();
        $this->renderer(function () use ($change): void {
            match ($change) {
                'membership' => DB::table('lab_user')->where('user_id', $this->operator->id)->delete(),
                'permission' => $this->operator->revokePermissionTo('edit_proposals'),
                'token' => DB::table('proposals')->where('id', $this->proposal->id)->update(['unique_hash' => (string) str()->uuid()]),
                'accepted' => DB::table('proposals')->where('id', $this->proposal->id)->update(['status' => 'ACCEPTED']),
                'item' => DB::table('proposal_items')->where('proposal_id', $this->proposal->id)->update(['total' => 30]),
                'source' => DB::table('warehouses')->where('id', $this->proposal->warehouse_id)->update(['deleted_at' => now()]),
            };
        });
        try {
            $this->execute('send');
            $this->fail('Changed render context must not be published.');
        } catch (AuthorizationException) {
            $this->assertSame(403, $status);
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
        $this->assertNotSame('SENT', $this->proposal->fresh()->status);
        $this->assertSame($path, $this->proposal->fresh()->file_path);
        $this->assertSame([$path], Storage::allFiles());
        $this->assertSame('retained PDF', Storage::get($path));
        Notification::assertNothingSent();
    }

    public static function renderChanges(): array
    {
        return ['membership' => ['membership', 403], 'permission' => ['permission', 403], 'token' => ['token', 404],
            'accepted' => ['accepted', 409], 'item' => ['item', 409], 'source' => ['source', 404]];
    }

    #[DataProvider('readyStates')]
    public function test_send_publishes_a_unique_checked_artifact_and_cannot_be_replayed(string $status): void
    {
        DB::table('proposals')->where('id', $this->proposal->id)->update(['status' => $status]);
        $this->renderer();
        $record = $this->execute('send');
        $this->assertSame('SENT', $record->status);
        $this->assertStringStartsWith("vap-proposals/{$record->id}/", $record->file_path);
        $this->assertSame('%PDF-authoring', Storage::get($record->file_path));
        $before = $this->evidence();
        try {
            $this->execute('send');
            $this->fail('Replays must not repeat a send.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
            $this->assertSame($before, $this->evidence());
            $this->assertSame([$record->file_path], Storage::allFiles());
        }
    }

    public static function readyStates(): array
    {
        return ['pending' => ['PENDING'], 'revised' => ['REVISED']];
    }

    #[DataProvider('malformedItems')]
    public function test_invalid_source_payload_fails_validation_without_creating_any_records(array $item): void
    {
        $before = VAPProposal::count();
        $data = $this->payload();
        $data['items'][0] = [...$data['items'][0], ...$item];
        $this->actingAs($this->operator)->postJson(route('vap-proposals.store'), $data)->assertUnprocessable();
        $this->assertSame($before, VAPProposal::count());
    }

    public static function malformedItems(): array
    {
        return ['arbitrary class' => [['itemable_type' => User::class, 'itemable_id' => 1]],
            'malformed id' => [['itemable_type' => Matrix::class, 'itemable_id' => 'not-an-id']],
            'id array' => [['itemable_type' => Matrix::class, 'itemable_id' => ['wrong']]],
            'missing id' => [['itemable_type' => Matrix::class]],
            'missing type' => [['itemable_id' => 1]],
            'class array' => [['itemable_type' => [User::class], 'itemable_id' => 1]],
            'item id array' => [['item_id' => ['wrong']]]];
    }

    public function test_staff_pdf_read_checks_permissions_after_rendering_without_persisting_files(): void
    {
        $before = $this->evidence();
        $this->renderer(fn () => $this->operator->revokePermissionTo('view_proposals'));
        try {
            app(DownloadStaffProposalPdf::class)->execute($this->proposal->lab_id, $this->operator->id, $this->proposal);
            $this->fail('Download access must be current after rendering.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $this->evidence());
            $this->assertSame([], Storage::allFiles());
        }
    }

    #[DataProvider('storageFailures')]
    public function test_failed_or_partial_pdf_write_retains_the_previous_artifact(bool $throws): void
    {
        $path = "vap-proposals/{$this->proposal->id}/retained.pdf";
        Storage::put($path, 'Retained');
        $this->proposal->update(['file_path' => $path]);
        $this->proposal->refresh();
        $before = $this->evidence();
        $manager = Storage::getFacadeRoot();
        $disk = Storage::disk();
        $proxy = \Mockery::mock($manager);
        $proxy->shouldReceive('put')->once()->andReturnUsing(function (string $newPath, string $content) use ($disk, $throws): bool {
            $disk->put($newPath, $content);
            if ($throws) {
                throw new \RuntimeException('Partial storage failure');
            }

            return false;
        });
        Storage::swap($proxy);
        $this->renderer();
        try {
            try {
                $this->execute('send');
                $this->fail('Failed storage must not publish a sent proposal.');
            } catch (HttpException $exception) {
                $this->assertFalse($throws);
                $this->assertSame(409, $exception->getStatusCode());
            } catch (\RuntimeException $exception) {
                $this->assertTrue($throws);
                $this->assertSame('Partial storage failure', $exception->getMessage());
            }
        } finally {
            Storage::swap($manager);
        }
        $this->assertSame($before, $this->evidence());
        $this->assertSame([$path], $disk->allFiles());
        $this->assertSame('Retained', $disk->get($path));
    }

    public static function storageFailures(): array
    {
        return ['false after partial write' => [false], 'exception after partial write' => [true]];
    }

    #[DataProvider('operations')]
    public function test_source_changed_by_a_write_hook_is_not_adopted_by_the_final_guard(string $operation): void
    {
        $before = $this->evidence();
        $count = VAPProposal::count();
        $dispatcher = Model::getEventDispatcher();
        $isolated = clone $dispatcher;
        Model::setEventDispatcher($isolated);
        $isolated->listen('eloquent.saving: '.VAPProposal::class, function (VAPProposal $proposal): void {
            $proposal->unique_hash = (string) str()->uuid();
        });
        if ($operation === 'send') {
            $this->renderer();
        }
        try {
            try {
                $this->execute($operation);
                $this->fail('A changed captured source must fail closed.');
            } catch (HttpException $exception) {
                $this->assertSame(404, $exception->getStatusCode());
            }
        } finally {
            Model::setEventDispatcher($dispatcher);
        }
        $this->assertSame($before, $this->evidence());
        $this->assertSame($count, VAPProposal::count());
        $this->assertSame([], Storage::allFiles());
    }

    #[DataProvider('itemWrites')]
    public function test_item_reference_archived_during_write_rolls_back_the_mutation(string $operation): void
    {
        $before = $this->evidence();
        $dispatcher = Model::getEventDispatcher();
        $isolated = clone $dispatcher;
        Model::setEventDispatcher($isolated);
        $isolated->listen('eloquent.saved: '.VAPProposalItem::class, function (): void {
            DB::table('units')->where('id', $this->unit->id)->update(['deleted_at' => now()]);
        });
        try {
            try {
                $this->execute($operation);
                $this->fail('Archived references must not be adopted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('items.0.unit_id', $exception->errors());
            }
        } finally {
            Model::setEventDispatcher($dispatcher);
        }
        $this->assertSame($before, $this->evidence());
        $this->assertNull($this->unit->fresh()->deleted_at);
    }

    public static function itemWrites(): array
    {
        return ['create' => ['create'], 'revise' => ['revise']];
    }

    #[DataProvider('visibleSendStates')]
    public function test_show_exposes_send_only_for_the_existing_sendable_states(string $status, bool $canSend): void
    {
        DB::table('proposals')->where('id', $this->proposal->id)->update(['status' => $status]);
        $this->actingAs($this->operator)->get(route('vap-proposals.show', $this->proposal->id))
            ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('VAPProposals/Show')->where('canSend', $canSend));
    }

    public static function visibleSendStates(): array
    {
        return ['pending' => ['PENDING', true], 'revised' => ['REVISED', true], 'sent' => ['SENT', false],
            'viewed' => ['VIEWED', false], 'accepted' => ['ACCEPTED', false], 'rejected' => ['REJECTED', false], 'expired' => ['EXPIRED', false]];
    }
}
