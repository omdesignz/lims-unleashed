<?php

namespace Tests\Feature;

use App\Actions\SetProposalArchived;
use App\Actions\SetProposalComplianceAgreementArchived;
use App\Models\Customer;
use App\Models\Department;
use App\Models\ProposalComplianceAgreement;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\Warehouse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProposalArchiveMutationTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lab = VAPLab::factory()->create();
        $this->operator = User::factory()->create(['is_active' => true]);
        foreach (['view_proposals', 'delete_proposals', 'restore_proposals'] as $permission) {
            $this->operator->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id]);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    private function proposal(string $status = 'PENDING', ?VAPLab $lab = null): VAPProposal
    {
        $customer = Customer::create(['name' => fake()->company()]);
        $site = Warehouse::create(['name' => fake()->unique()->company(), 'customer_id' => $customer->id]);
        $template = VAPProposalTemplate::create(['name' => 'Archive fixture', 'content' => '<p>Fixture</p>', 'user_id' => $this->operator->id, 'is_active' => true]);
        $proposal = new VAPProposal([
            'proposal_year' => now()->year, 'proposal_no' => 'ARCHIVE-'.Str::uuid(),
            'customer_id' => $customer->id, 'warehouse_id' => $site->id,
            'department_id' => Department::factory()->create()->id, 'template_id' => $template->id,
            'user_id' => $this->operator->id, 'status' => $status, 'details' => ['evidence' => 'retained'],
            'unique_hash' => (string) Str::uuid(),
        ]);
        $proposal->lab_id = ($lab ?? $this->lab)->id;
        $proposal->save();

        return $proposal;
    }

    private function agreement(VAPProposal $proposal): ProposalComplianceAgreement
    {
        return ProposalComplianceAgreement::create([
            'proposal_id' => $proposal->id, 'confidentiality' => true, 'impartiality' => true,
            'nondisclosure' => true, 'acknowledged_at' => now()->subDay(), 'client_ip' => '127.0.0.7',
        ]);
    }

    /** @return array<string, mixed> */
    private function evidence(VAPProposal|ProposalComplianceAgreement $record): array
    {
        return collect($record->fresh()->getRawOriginal())->except(['deleted_at', 'updated_at'])->all();
    }

    public function test_get_and_head_cannot_archive_restore_or_accept_proposals(): void
    {
        $proposal = $this->proposal();
        $agreement = $this->agreement($proposal);
        foreach (['proposals.destroy', 'proposals.restore', 'proposalcomplianceagreements.destroy', 'proposalcomplianceagreements.restore'] as $route) {
            $url = route($route, ['recordIds' => [$agreement->id, $proposal->id]]);
            $this->getJson($url)->assertStatus(405);
            $this->head($url)->assertStatus(405);
        }
        foreach (['accept', 'reject'] as $action) {
            $this->getJson('/proposals/'.$proposal->id.'/'.$action)->assertNotFound();
        }
        $this->assertSame('PENDING', $proposal->fresh()->status);
        $this->assertNull($proposal->fresh()->deleted_at);
        $this->assertNull($agreement->fresh()->deleted_at);
        $this->assertSame(0, $proposal->complianceAgreementLogs()->count());
    }

    public function test_archive_lists_are_usable_for_the_current_lab(): void
    {
        $proposal = $this->proposal();
        $agreement = $this->agreement($proposal);
        $this->agreement($this->proposal(lab: VAPLab::factory()->create()));
        $this->get(route('proposals.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->has('record.data', 1)->where('record.data.0.id', $proposal->id)
            ->where('record.data.0.details.evidence', 'retained')->where('record.data.0.can_archive', true));
        $this->get(route('proposalcomplianceagreements.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->has('record.data', 1)->where('record.data.0.id', $agreement->id)->where('record.data.0.deleted', false)
            ->where('model', 'proposals')->where('record.data.0.proposal.proposal_no', $proposal->proposal_no));
        $this->get(route('vap-proposals.index'))->assertOk();
        $agreement->delete();
        $proposal->delete();
        $this->get(route('proposals.index', ['filter' => ['trashed' => 'only']]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->has('record.data', 1)->where('record.data.0.deleted', true)->where('record.data.0.can_archive', false));
        $this->get(route('proposalcomplianceagreements.index', ['filter' => ['trashed' => 'only']]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->has('record.data', 1)->where('record.data.0.deleted', true)
            ->where('record.data.0.proposal.id', $proposal->id)
            ->where('record.data.0.proposal.proposal_no', $proposal->proposal_no));
    }

    public function test_manual_consent_creation_and_editing_are_retired_without_changing_evidence(): void
    {
        $proposal = $this->proposal();
        $agreement = $this->agreement($proposal);
        $before = $this->evidence($agreement);
        $this->getJson('/proposalcomplianceagreements/create')->assertNotFound();
        $this->getJson('/proposalcomplianceagreements/'.$agreement->id.'/edit')->assertNotFound();
        $this->postJson('/proposalcomplianceagreements', [
            'proposal_id' => $proposal->id, 'confidentiality' => false, 'impartiality' => false, 'nondisclosure' => false,
        ])->assertStatus(405);
        $this->putJson('/proposalcomplianceagreements/'.$agreement->id, [
            'confidentiality' => false, 'impartiality' => false, 'nondisclosure' => false,
        ])->assertNotFound();
        $this->assertSame($before, $this->evidence($agreement));
        $this->assertSame(1, $proposal->complianceAgreement()->count());
        $this->assertSame(0, $proposal->complianceAgreementLogs()->count());
    }

    public function test_bulk_and_canonical_archive_preserve_identity_and_shared_directory(): void
    {
        $proposal = $this->proposal();
        $agreement = $this->agreement($proposal);
        $identity = $this->evidence($proposal);
        $consent = $this->evidence($agreement);
        $customer = $proposal->customer->getRawOriginal();
        $site = $proposal->warehouse->getRawOriginal();

        $this->deleteJson(route('vap-proposals.destroy', $proposal->id))->assertRedirect();
        $this->assertSoftDeleted($proposal);
        $this->assertSame($identity, $this->evidence($proposal));
        $this->assertSame($consent, $this->evidence($agreement));
        $this->patchJson(route('proposals.restore'), ['recordIds' => [$proposal->id]])->assertRedirect();
        $this->assertNotSoftDeleted($proposal);
        $this->deleteJson(route('proposals.destroy'), ['recordIds' => [$proposal->id]])->assertRedirect();
        $this->assertSoftDeleted($proposal);
        $this->assertSame($identity, $this->evidence($proposal));
        $this->assertSame($customer, $proposal->customer->fresh()->getRawOriginal());
        $this->assertSame($site, $proposal->warehouse->fresh()->getRawOriginal());

        $audits = Activity::where('subject_type', 'proposal')->where('subject_id', $proposal->id)
            ->whereIn('description', ['arquivou a proposta', 'restaurou a proposta'])->get();
        $this->assertCount(3, $audits);
        foreach ($audits as $audit) {
            $this->assertSame($this->operator->id, $audit->causer_id);
            $this->assertSame($this->lab->id, $audit->properties['lab_id']);
        }
    }

    #[DataProvider('blockedStatuses')]
    public function test_every_archive_path_uses_current_canonical_status(string $status): void
    {
        $allowed = $this->proposal();
        $blocked = $this->proposal();
        DB::table('proposals')->where('id', $blocked->id)->update(['status' => $status]);
        $this->deleteJson(route('proposals.destroy'), ['recordIds' => [$allowed->id, $blocked->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('recordIds');
        $this->deleteJson(route('vap-proposals.destroy', $blocked->id))
            ->assertUnprocessable()->assertJsonValidationErrors('recordIds');
        $this->assertNull($allowed->fresh()->deleted_at);
        $this->assertNull($blocked->fresh()->deleted_at);
        $this->assertSame($status, $blocked->fresh()->status);
    }

    public static function blockedStatuses(): array
    {
        return array_map(fn (string $status): array => [$status], ['ACCEPTED', 'SENT', 'VIEWED', 'REVISED', 'EXPIRED']);
    }

    public function test_archive_and_restore_are_idempotent_and_keep_restore_status(): void
    {
        $proposal = $this->proposal('REJECTED');
        $action = app(SetProposalArchived::class);
        $this->assertSame(1, $action->execute($this->lab->id, $this->operator->id, [$proposal->id], true));
        $count = Activity::count();
        $this->assertSame(0, $action->execute($this->lab->id, $this->operator->id, [$proposal->id], true));
        $this->assertSame($count, Activity::count());
        DB::table('proposals')->where('id', $proposal->id)->update(['status' => 'ACCEPTED']);
        $this->assertSame(1, $action->execute($this->lab->id, $this->operator->id, [$proposal->id], false));
        $count = Activity::count();
        $this->assertSame(0, $action->execute($this->lab->id, $this->operator->id, [$proposal->id], false));
        $this->assertSame($count, Activity::count());
        $this->assertSame('ACCEPTED', $proposal->fresh()->status);
    }

    #[DataProvider('invalidIds')]
    public function test_bulk_endpoints_validate_ids_before_writing(mixed $ids): void
    {
        foreach (['proposals', 'proposalcomplianceagreements'] as $prefix) {
            $this->deleteJson(route($prefix.'.destroy'), ['recordIds' => $ids])->assertUnprocessable();
            $this->patchJson(route($prefix.'.restore'), ['recordIds' => $ids])->assertUnprocessable();
        }
    }

    public static function invalidIds(): array
    {
        return [[null], [[]], [['bad']], [[0]], [[-1]], [[1, 1]], [range(1, 101)], [[['nested' => 1]]], [['id' => 1]]];
    }

    public function test_mixed_lab_agreement_batches_fail_without_partial_changes(): void
    {
        $local = $this->agreement($this->proposal());
        $peer = $this->agreement($this->proposal(lab: VAPLab::factory()->create()));
        $this->deleteJson(route('proposalcomplianceagreements.destroy'), ['recordIds' => [$local->id, $peer->id]])->assertNotFound();
        $this->assertNull($local->fresh()->deleted_at);
        $this->assertNull($peer->fresh()->deleted_at);
        $local->delete();
        $peer->delete();
        $this->patchJson(route('proposalcomplianceagreements.restore'), ['recordIds' => [$local->id, $peer->id]])->assertNotFound();
        $this->assertSoftDeleted($local);
        $this->assertSoftDeleted($peer);
    }

    public function test_agreement_archive_restore_preserves_consent_logs_and_status_with_scoped_audit(): void
    {
        $proposal = $this->proposal('ACCEPTED');
        $agreement = $this->agreement($proposal);
        DB::table('proposal_compliance_agreements')->where('id', $agreement->id)->update([
            'rejected_at' => now()->subDays(2), 'rejection_reason' => 'Retained historical evidence',
        ]);
        $log = $proposal->complianceAgreementLogs()->create([
            'confidentiality' => true, 'impartiality' => true, 'nondisclosure' => true,
            'acknowledged_at' => now()->subDay(), 'client_ip' => '127.0.0.7',
        ]);
        $consent = $this->evidence($agreement);
        $logEvidence = $log->fresh()->getRawOriginal();
        $action = app(SetProposalComplianceAgreementArchived::class);
        foreach ([true, false] as $archived) {
            $this->assertSame(1, $action->execute($this->lab->id, $this->operator->id, [$agreement->id], $archived));
            $count = Activity::count();
            $this->assertSame(0, $action->execute($this->lab->id, $this->operator->id, [$agreement->id], $archived));
            $this->assertSame($count, Activity::count());
            $this->assertSame($consent, $this->evidence($agreement));
            $this->assertSame($logEvidence, $log->fresh()->getRawOriginal());
            $this->assertSame('ACCEPTED', $proposal->fresh()->status);
            $this->assertSame(1, $proposal->complianceAgreementLogs()->count());
        }
        $audit = Activity::where('description', 'restaurou o acordo da proposta')->latest('id')->firstOrFail();
        $this->assertSame('proposal', $audit->subject_type);
        $this->assertSame($proposal->id, $audit->subject_id);
        $this->assertSame($this->operator->id, $audit->causer_id);
        $this->assertSame($this->lab->id, $audit->properties['lab_id']);
        $this->assertSame($agreement->id, $audit->properties['agreement_id']);
    }

    public function test_agreement_restore_conflicts_are_atomic(): void
    {
        $parent = $this->proposal();
        $first = $this->agreement($parent);
        $first->delete();
        $second = $this->agreement($parent);
        $other = $this->agreement($this->proposal());
        $other->delete();
        $url = route('proposalcomplianceagreements.restore');
        $this->patchJson($url, ['recordIds' => [$other->id, $first->id]])->assertUnprocessable()->assertJsonValidationErrors('recordIds');
        $this->assertSoftDeleted($other);
        $second->delete();
        $this->patchJson($url, ['recordIds' => [$first->id, $second->id]])->assertUnprocessable();
        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
        $parent->delete();
        $this->patchJson($url, ['recordIds' => [$other->id, $first->id]])->assertUnprocessable();
        $this->assertSoftDeleted($other);
        $this->assertSoftDeleted($first);
        $parent->restore();
        $this->patchJson($url, ['recordIds' => [$first->id]])->assertRedirect();
        $this->assertNotSoftDeleted($first);
    }

    #[DataProvider('revocations')]
    public function test_actions_recheck_live_operator_access(string $revocation, bool $archived, bool $agreement): void
    {
        $proposal = $this->proposal();
        $record = $agreement ? $this->agreement($proposal) : $proposal;
        if (! $archived) {
            $record->delete();
        }
        match ($revocation) {
            'membership' => DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->operator->id)->delete(),
            'permission' => $this->operator->revokePermissionTo($archived ? 'delete_proposals' : 'restore_proposals'),
            'inactive' => $this->operator->update(['is_active' => false]),
            'unverified' => $this->operator->update(['email_verified_at' => null]),
            'lab' => $this->lab->delete(),
        };
        try {
            app($agreement ? SetProposalComplianceAgreementArchived::class : SetProposalArchived::class)
                ->execute($this->lab->id, $this->operator->id, [$record->id], $archived);
            $this->fail('Revoked access must reject mutation.');
        } catch (AuthorizationException) {
            $this->assertSame(! $archived, $record->fresh()->trashed());
        }
    }

    public static function revocations(): array
    {
        $cases = [];
        foreach (['membership', 'permission', 'inactive', 'unverified', 'lab'] as $revocation) {
            foreach ([true, false] as $archived) {
                foreach ([true, false] as $agreement) {
                    $cases[] = [$revocation, $archived, $agreement];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('cancelledWrites')]
    public function test_cancelled_lifecycle_writes_roll_back_the_whole_batch(bool $agreement, bool $archived): void
    {
        $firstProposal = $this->proposal();
        $lastProposal = $this->proposal();
        $first = $agreement ? $this->agreement($firstProposal) : $firstProposal;
        $last = $agreement ? $this->agreement($lastProposal) : $lastProposal;
        if (! $archived) {
            $first->delete();
            $last->delete();
        }
        $before = Activity::count();
        $event = 'eloquent.'.($archived ? 'deleting' : 'restoring').': '.get_class($last);
        Event::listen($event, fn ($record): ?bool => $record->id === $last->id ? false : null);
        try {
            app($agreement ? SetProposalComplianceAgreementArchived::class : SetProposalArchived::class)
                ->execute($this->lab->id, $this->operator->id, [$last->id, $first->id], $archived);
            $this->fail('Cancelled writes must fail.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
            $this->assertSame(! $archived, $first->fresh()->trashed());
            $this->assertSame(! $archived, $last->fresh()->trashed());
            $this->assertSame($before, Activity::count());
        } finally {
            Event::forget($event);
        }
    }

    public static function cancelledWrites(): array
    {
        return [[true, true], [true, false], [false, true], [false, false]];
    }

    public function test_consent_migration_preserves_unknown_history_and_guards_recorded_evidence_on_rollback(): void
    {
        $this->assertSame('lims_unleashed_test', DB::connection()->getDatabaseName());
        $schema = 'proposal_consent_'.bin2hex(random_bytes(8));
        $name = 'proposal_consent_migration';
        $writerName = 'proposal_consent_writer';
        $originalDefault = DB::getDefaultConnection();
        $originalConfig = config('database.connections.'.$name);
        $originalWriterConfig = config('database.connections.'.$writerName);
        config(['database.connections.'.$name => array_merge(config('database.connections.pgsql'), ['search_path' => $schema])]);
        config(['database.connections.'.$writerName => config('database.connections.'.$name)]);
        $connection = DB::connection($name);
        $writer = DB::connection($writerName);
        $probeLock = false;
        $connection->statement('CREATE SCHEMA "'.$schema.'"');

        try {
            DB::setDefaultConnection($name);
            Schema::create('proposal_compliance_agreement_logs', function (Blueprint $table): void {
                $table->id();
                $table->text('snapshot');
            });
            $id = DB::table('proposal_compliance_agreement_logs')->insertGetId(['snapshot' => 'original retained evidence']);
            $migration = require database_path('migrations/2026_10_01_155153_add_nondisclosure_to_proposal_compliance_agreement_logs.php');
            $migration->up();
            $row = DB::table('proposal_compliance_agreement_logs')->find($id);
            $this->assertNull($row->nondisclosure);
            $this->assertSame('original retained evidence', $row->snapshot);
            $writer->statement("SET lock_timeout = '100ms'");
            $blockedWrites = 0;
            DB::listen(function (QueryExecuted $query) use ($name, $writer, $id, &$probeLock, &$blockedWrites): void {
                if (! $probeLock || $query->connectionName !== $name || ! str_starts_with($query->sql, 'LOCK TABLE')) {
                    return;
                }

                $probeLock = false;
                $this->assertGreaterThan(0, $query->connection->transactionLevel());
                try {
                    $writer->table('proposal_compliance_agreement_logs')->where('id', $id)->update(['nondisclosure' => true]);
                    $this->fail('Rollback must exclude consent writers before checking evidence.');
                } catch (QueryException $exception) {
                    $this->assertSame('55P03', $exception->errorInfo[0]);
                    $blockedWrites++;
                }
            });
            $probeLock = true;
            $migration->down();
            $this->assertSame(1, $blockedWrites);
            $this->assertFalse(Schema::hasColumn('proposal_compliance_agreement_logs', 'nondisclosure'));
            $migration->up();
            DB::table('proposal_compliance_agreement_logs')->where('id', $id)->update(['nondisclosure' => true]);
            try {
                $migration->down();
                $this->fail('Recorded consent must block destructive rollback.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('consent evidence', $exception->getMessage());
                $this->assertTrue(DB::table('proposal_compliance_agreement_logs')->find($id)->nondisclosure);
            }
        } finally {
            $probeLock = false;
            DB::setDefaultConnection($originalDefault);
            $connection->statement('DROP SCHEMA "'.$schema.'" CASCADE');
            DB::purge($name);
            DB::purge($writerName);
            config(['database.connections.'.$name => $originalConfig]);
            config(['database.connections.'.$writerName => $originalWriterConfig]);
        }
    }
}
