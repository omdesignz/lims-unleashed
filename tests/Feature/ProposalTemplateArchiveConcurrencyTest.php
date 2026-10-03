<?php

namespace Tests\Feature;

use App\Actions\CreateProposal;
use App\Actions\ImportProposalTemplates;
use App\Actions\SaveProposalTemplate;
use App\Actions\SetProposalTemplateActiveStatus;
use App\Actions\SetProposalTemplatesArchived;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposalTemplate;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\IsolatedPostgresTestCase;

class ProposalTemplateArchiveConcurrencyTest extends IsolatedPostgresTestCase
{
    public function test_reversed_existing_import_batches_replay_without_duplicate_history(): void
    {
        [$template, $create, $archive] = $this->fixture();
        $other = VAPProposalTemplate::create(['name' => 'Other native import', 'content' => '<p>Retained</p>', 'user_id' => $create['actor']]);
        $rows = [['name' => $template->name, 'content' => '<p>First revision</p>'], ['name' => $other->name, 'content' => '<p>Second revision</p>']];
        $this->assertSame([200, 200], $this->compete('proposal_templates', [$template->id, $other->id], [
            ['op' => 'import_templates', 'actor' => $create['actor'], 'rows' => $rows],
            ['op' => 'import_templates', 'actor' => $archive['actor'], 'rows' => array_reverse($rows)],
        ]));
        $this->assertSame($rows[0]['content'], $template->fresh()->content);
        $this->assertSame($rows[1]['content'], $other->fresh()->content);
        $this->assertSame($create['actor'], $template->fresh()->user_id);
        $this->assertSame($create['actor'], $other->fresh()->user_id);
        $this->assertSame(2, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->where('event', 'updated')->count());
    }

    #[DataProvider('statusRevocations')]
    public function test_waiting_import_batch_rechecks_fresh_authority(string $revocation): void
    {
        [$template, $create] = $this->fixture();
        $before = $template->fresh()->getAttributes();
        $operation = ['op' => 'import_templates', 'actor' => $create['actor'], 'rows' => [
            ['name' => $template->name, 'content' => '<p>First revision</p>'], ['name' => 'New native import', 'content' => '<p>New</p>'],
        ]];
        $this->assertSame([403], $this->compete('users', [$create['actor']], [$operation], beforeRelease: function () use ($revocation, $create): void {
            $actor = User::findOrFail($create['actor']);
            if ($revocation === 'permission') {
                $actor->syncPermissions([]);
            } else {
                $actor->forceFill(match ($revocation) {
                    'verification' => ['email_verified_at' => null], 'active' => ['is_active' => false], default => ['deleted_at' => now()],
                })->save();
            }
        }));
        $this->assertSame($before, $template->fresh()->getAttributes());
        $this->assertSame(1, DB::table('proposal_templates')->count());
        $this->assertSame(0, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->count());
    }

    public function test_whole_import_batch_and_history_publish_only_at_real_outer_commit(): void
    {
        [$template, $create] = $this->fixture();
        config(['database.connections.template_import_observer' => config('database.connections.pgsql')]);
        $observer = DB::connection('template_import_observer');
        $rows = [['name' => $template->name, 'content' => '<p>Committed revision</p>'], ['name' => 'New committed import', 'content' => '<p>New committed</p>']];
        try {
            foreach ([false, true] as $commit) {
                DB::beginTransaction();
                $this->assertSame(['created' => 1, 'updated' => 1, 'unchanged' => 0], app(ImportProposalTemplates::class)->execute($create['actor'], $rows));
                $this->assertSame($template->content, $observer->table('proposal_templates')->where('id', $template->id)->value('content'));
                $this->assertSame(1, $observer->table('proposal_templates')->count());
                $this->assertSame(0, $observer->table('activity_log')->where('subject_type', $template->getMorphClass())->count());
                if ($commit) {
                    DB::commit();
                } else {
                    DB::rollBack();
                    $this->assertSame($template->content, $template->fresh()->content);
                    $this->assertSame(1, DB::table('proposal_templates')->count());
                    $this->assertSame(0, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->count());
                }
            }
            $this->assertSame($rows[0]['content'], $observer->table('proposal_templates')->where('id', $template->id)->value('content'));
            $this->assertSame($rows[1]['content'], $observer->table('proposal_templates')->where('name', $rows[1]['name'])->value('content'));
            $this->assertSame(2, $observer->table('activity_log')->where('subject_type', $template->getMorphClass())->count());
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('template_import_observer');
        }
    }

    public function test_replayed_authoring_from_two_actors_publishes_one_revision(): void
    {
        [$template, $create, $archive] = $this->fixture();
        $data = ['name' => $template->name, 'content' => '<p>Concurrent revision</p>'];
        $this->assertSame([200, 200], $this->compete('proposal_templates', [$template->id], [
            ['op' => 'save_template', 'actor' => $create['actor'], 'target' => $template->id, 'data' => $data],
            ['op' => 'save_template', 'actor' => $archive['actor'], 'target' => $template->id, 'data' => $data],
        ]));
        $this->assertSame($data['content'], $template->fresh()->content);
        $this->assertSame($template->user_id, $template->fresh()->user_id);
        $this->assertSame(1, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->where('event', 'updated')->count());
    }

    #[DataProvider('orders')]
    public function test_authoring_and_archive_serialize_on_the_live_template(string $order): void
    {
        [$template, $create, $archive] = $this->fixture();
        $save = ['op' => 'save_template', 'actor' => $create['actor'], 'target' => $template->id,
            'data' => ['name' => $template->name, 'content' => '<p>Concurrent revision</p>']];
        $beforeStart = match ($order) {
            'create_first' => fn () => app(SaveProposalTemplate::class)->execute($save['actor'], $save['data'], $template->id),
            'archive_first' => fn () => app(SetProposalTemplatesArchived::class)->execute($archive['actor'], [$template->id], true),
            default => null,
        };
        $operations = match ($order) {
            'create_first' => [$archive], 'archive_first' => [$save], default => [$save, $archive],
        };
        $results = $this->compete('proposal_templates', [$template->id], $operations, $beforeStart);
        if ($order === 'compete') {
            $this->assertContains($results, [[200, 200], [404, 200]]);
            $changed = $results[0] === 200;
        } else {
            $this->assertSame($order === 'create_first' ? [200] : [404], $results);
            $changed = $order === 'create_first';
        }
        $stored = $template->fresh();
        $this->assertTrue($stored->trashed());
        $this->assertSame($changed ? $save['data']['content'] : $template->content, $stored->content);
        $this->assertSame($changed ? 1 : 0, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->where('event', 'updated')->count());
        $this->assertSame(1, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->where('event', 'archived')->count());
    }

    public function test_authoring_and_desired_status_preserve_each_others_persisted_fields(): void
    {
        [$template, $create, $archive] = $this->fixture();
        $this->assertSame([200, 200], $this->compete('proposal_templates', [$template->id], [
            ['op' => 'save_template', 'actor' => $create['actor'], 'target' => $template->id,
                'data' => ['name' => $template->name, 'content' => '<p>Concurrent revision</p>']],
            ['op' => 'status', 'actor' => $archive['actor'], 'target' => $template->id, 'active' => true],
        ]));
        $stored = $template->fresh();
        $this->assertTrue($stored->is_active);
        $this->assertSame('<p>Concurrent revision</p>', $stored->content);
        $this->assertSame(1, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->where('event', 'updated')->count());
        $this->assertSame(1, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->where('event', 'activation_changed')->count());
    }

    #[DataProvider('authoringRevocations')]
    public function test_waiting_authoring_rechecks_fresh_authority(string $mode, string $revocation): void
    {
        [$template, $create] = $this->fixture();
        $before = $template->fresh()->getAttributes();
        $save = ['op' => 'save_template', 'actor' => $create['actor'], 'target' => $mode === 'create' ? null : $template->id,
            'data' => ['name' => 'Waiting authoring', 'content' => '<p>Waiting revision</p>']];
        $this->assertSame([403], $this->compete('users', [$create['actor']], [$save], beforeRelease: function () use ($revocation, $create): void {
            $actor = User::findOrFail($create['actor']);
            if ($revocation === 'permission') {
                $actor->syncPermissions([]);
            } else {
                $actor->forceFill(match ($revocation) {
                    'verification' => ['email_verified_at' => null], 'active' => ['is_active' => false], default => ['deleted_at' => now()],
                })->save();
            }
        }));
        $this->assertSame($before, $template->fresh()->getAttributes());
        $this->assertSame(1, DB::table('proposal_templates')->count());
        $this->assertSame(0, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->count());
    }

    public static function authoringRevocations(): array
    {
        $cases = [];
        foreach (['create', 'update'] as $mode) {
            foreach (['permission', 'verification', 'active', 'archive'] as $revocation) {
                $cases[] = [$mode, $revocation];
            }
        }

        return $cases;
    }

    #[DataProvider('authoringModes')]
    public function test_authoring_and_history_publish_only_at_real_outer_commit(string $mode): void
    {
        [$template, $create] = $this->fixture();
        config(['database.connections.template_authoring_observer' => config('database.connections.pgsql')]);
        $observer = DB::connection('template_authoring_observer');
        $data = ['name' => 'Outer commit authoring', 'content' => '<p>Outer commit revision</p>'];
        try {
            foreach ([false, true] as $commit) {
                DB::beginTransaction();
                $stored = app(SaveProposalTemplate::class)->execute($create['actor'], $data, $mode === 'create' ? null : $template->id);
                $this->assertSame(0, $observer->table('proposal_templates')->where('name', $data['name'])->count());
                $this->assertSame(0, $observer->table('activity_log')->where('subject_type', $template->getMorphClass())->count());
                if ($commit) {
                    DB::commit();
                } else {
                    DB::rollBack();
                    $this->assertSame(1, DB::table('proposal_templates')->count());
                    $this->assertSame($template->content, $template->fresh()->content);
                    $this->assertSame(0, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->count());
                }
            }
            $this->assertSame($data['content'], $observer->table('proposal_templates')->where('id', $stored->id)->value('content'));
            $this->assertSame(1, $observer->table('activity_log')->where('subject_type', $template->getMorphClass())->where('event', $mode === 'create' ? 'created' : 'updated')->count());
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('template_authoring_observer');
        }
    }

    public static function authoringModes(): array
    {
        return [['create'], ['update']];
    }

    public function test_replayed_desired_status_from_two_actors_publishes_one_audit(): void
    {
        [$template, $create, $archive] = $this->fixture();
        $this->assertSame([200, 200], $this->compete('proposal_templates', [$template->id], [
            ['op' => 'status', 'actor' => $create['actor'], 'target' => $template->id, 'active' => true],
            ['op' => 'status', 'actor' => $archive['actor'], 'target' => $template->id, 'active' => true],
        ]));
        $this->assertTrue($template->fresh()->is_active);
        $this->assertSame(1, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->where('event', 'activation_changed')->count());
        $this->assertSame(0, DB::table('proposals')->count());
    }

    #[DataProvider('orders')]
    public function test_desired_status_and_archive_share_the_same_live_template_lock(string $order): void
    {
        [$template, $create, $archive] = $this->fixture();
        $status = ['op' => 'status', 'actor' => $create['actor'], 'target' => $template->id, 'active' => true];
        $beforeStart = match ($order) {
            'create_first' => fn () => app(SetProposalTemplateActiveStatus::class)->execute($status['actor'], $template->id, true),
            'archive_first' => fn () => app(SetProposalTemplatesArchived::class)->execute($archive['actor'], [$template->id], true),
            default => null,
        };
        $operations = match ($order) {
            'create_first' => [$archive], 'archive_first' => [$status], default => [$status, $archive],
        };
        $results = $this->compete('proposal_templates', [$template->id], $operations, $beforeStart);
        if ($order === 'compete') {
            $this->assertContains($results, [[200, 200], [404, 200]]);
            $changed = $results[0] === 200;
        } else {
            $this->assertSame($order === 'create_first' ? [200] : [404], $results);
            $changed = $order === 'create_first';
        }
        $this->assertTrue($template->fresh()->trashed());
        $this->assertSame($changed, $template->fresh()->is_active);
        $this->assertSame($changed ? 1 : 0, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->where('event', 'activation_changed')->count());
        $this->assertSame(1, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->where('event', 'archived')->count());
    }

    #[DataProvider('statusRevocations')]
    public function test_waiting_status_request_uses_fresh_authority(string $revocation): void
    {
        [$template, $create] = $this->fixture();
        $status = ['op' => 'status', 'actor' => $create['actor'], 'target' => $template->id, 'active' => true];
        $this->assertSame([403], $this->compete('users', [$create['actor']], [$status], beforeRelease: function () use ($revocation, $create): void {
            $actor = User::findOrFail($create['actor']);
            if ($revocation === 'permission') {
                $actor->syncPermissions([]);
            } else {
                $actor->forceFill(match ($revocation) {
                    'verification' => ['email_verified_at' => null], 'active' => ['is_active' => false], default => ['deleted_at' => now()],
                })->save();
            }
        }));
        $this->assertFalse($template->fresh()->is_active);
        $this->assertSame(0, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->count());
    }

    public static function statusRevocations(): array
    {
        return [['permission'], ['verification'], ['active'], ['archive']];
    }

    public function test_status_and_its_history_publish_only_at_real_outer_commit(): void
    {
        [$template, $create] = $this->fixture();
        config(['database.connections.template_status_observer' => config('database.connections.pgsql')]);
        $observer = DB::connection('template_status_observer');
        try {
            foreach ([false, true] as $commit) {
                DB::beginTransaction();
                app(SetProposalTemplateActiveStatus::class)->execute($create['actor'], $template->id, true);
                $this->assertFalse((bool) $observer->table('proposal_templates')->where('id', $template->id)->value('is_active'));
                $this->assertSame(0, $observer->table('activity_log')->where('subject_type', $template->getMorphClass())->count());
                if ($commit) {
                    DB::commit();
                } else {
                    DB::rollBack();
                    $this->assertFalse($template->fresh()->is_active);
                    $this->assertSame(0, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->count());
                }
            }
            $this->assertTrue((bool) $observer->table('proposal_templates')->where('id', $template->id)->value('is_active'));
            $this->assertSame(1, $observer->table('activity_log')->where('subject_type', $template->getMorphClass())->where('event', 'activation_changed')->count());
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('template_status_observer');
        }
    }

    #[DataProvider('orders')]
    public function test_creation_and_archive_serialize_at_the_shared_template_boundary(string $order): void
    {
        [$template, $create, $archive] = $this->fixture();
        $beforeStart = match ($order) {
            'create_first' => fn () => app(CreateProposal::class)->execute($create['lab'], $create['actor'], $create['data']),
            'archive_first' => fn () => app(SetProposalTemplatesArchived::class)->execute($archive['actor'], [$template->id], true),
            default => null,
        };
        $operations = match ($order) {
            'create_first' => [$archive], 'archive_first' => [$create], default => [$create, $archive],
        };
        $results = $this->compete('proposal_templates', [$template->id], $operations, $beforeStart);
        if ($order === 'compete') {
            $this->assertContains($results, [[200, 422], [422, 200]]);
            $created = $results[0] === 200;
        } else {
            $this->assertSame([422], $results);
            $created = $order === 'create_first';
        }
        $this->assertSame(! $created, $template->fresh()->trashed());
        foreach (['proposals', 'proposal_items', 'proposal_compliance_agreements'] as $table) {
            $this->assertSame($created ? 1 : 0, DB::table($table)->count());
        }
        $this->assertSame($created ? 0 : 1, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->where('event', 'archived')->count());
        if ($created) {
            $this->assertSame($template->id, DB::table('proposals')->sole()->template_id);
            $this->assertFalse($template->fresh()->is_active);
        }
    }

    public static function orders(): array
    {
        return [['compete'], ['create_first'], ['archive_first']];
    }

    public function test_reversed_archive_batches_replay_without_duplicate_history(): void
    {
        [$template, $create, $archive] = $this->fixture();
        $other = VAPProposalTemplate::create(['name' => 'Second concurrent template', 'content' => '<p>Keep</p>', 'user_id' => $create['actor']]);
        $this->assertSame([200, 200], $this->compete('proposal_templates', [$template->id, $other->id], [
            [...$archive, 'targets' => [$template->id, $other->id]],
            ['op' => 'archive', 'actor' => $create['actor'], 'targets' => [$other->id, $template->id]],
        ]));
        $this->assertTrue($template->fresh()->trashed());
        $this->assertTrue($other->fresh()->trashed());
        $this->assertSame(2, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->where('event', 'archived')->count());
    }

    #[DataProvider('revocations')]
    public function test_waiting_archive_rechecks_current_authority(string $revocation): void
    {
        [$template, , $archive] = $this->fixture();
        $this->assertSame([403], $this->compete('users', [$archive['actor']], [$archive], beforeRelease: function () use ($revocation, $archive): void {
            $actor = User::findOrFail($archive['actor']);
            if ($revocation === 'permission') {
                $actor->syncPermissions([]);
            } else {
                $actor->forceFill(['email_verified_at' => null])->save();
            }
        }));
        $this->assertFalse($template->fresh()->trashed());
        $this->assertSame(0, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->count());
    }

    public static function revocations(): array
    {
        return [['permission'], ['verification']];
    }

    public function test_archive_and_history_publish_only_at_real_outer_commit(): void
    {
        [$template, , $archive] = $this->fixture();
        config(['database.connections.template_archive_observer' => config('database.connections.pgsql')]);
        $observer = DB::connection('template_archive_observer');
        try {
            foreach ([false, true] as $commit) {
                DB::beginTransaction();
                app(SetProposalTemplatesArchived::class)->execute($archive['actor'], [$template->id], true);
                $this->assertNull($observer->table('proposal_templates')->where('id', $template->id)->value('deleted_at'));
                $this->assertSame(0, $observer->table('activity_log')->where('subject_type', $template->getMorphClass())->count());
                if ($commit) {
                    DB::commit();
                } else {
                    DB::rollBack();
                    $this->assertFalse($template->fresh()->trashed());
                    $this->assertSame(0, DB::table('activity_log')->where('subject_type', $template->getMorphClass())->count());
                }
            }
            $this->assertNotNull($observer->table('proposal_templates')->where('id', $template->id)->value('deleted_at'));
            $this->assertSame(1, $observer->table('activity_log')->where('subject_type', $template->getMorphClass())->where('event', 'archived')->count());
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('template_archive_observer');
        }
    }

    /** @return array{VAPProposalTemplate,array<string,mixed>,array<string,mixed>} */
    private function fixture(): array
    {
        $creator = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $archiver = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $creator->givePermissionTo(Permission::findOrCreate('add_proposals', 'web'));
        foreach ([$creator, $archiver] as $actor) {
            $actor->givePermissionTo(Permission::findOrCreate('delete_proposal_templates', 'web'));
            $actor->givePermissionTo(Permission::findOrCreate('edit_proposal_templates', 'web'));
            $actor->givePermissionTo(Permission::findOrCreate('add_proposal_templates', 'web'));
            $actor->givePermissionTo(Permission::findOrCreate('import_proposal_templates', 'web'));
        }
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $creator->id]);
        $customer = Customer::create(['name' => 'Native customer']);
        $site = Warehouse::create(['name' => 'Native site', 'customer_id' => $customer->id]);
        $department = Department::factory()->create();
        $unit = Unit::create(['code' => 'NATIVE', 'description' => 'Native unit']);
        $template = VAPProposalTemplate::create(['name' => 'Retained native template', 'content' => '<p>Keep</p>', 'user_id' => $creator->id, 'is_active' => false]);
        request()->setLaravelSession(app('session')->driver());
        $create = ['op' => 'create', 'actor' => $creator->id, 'lab' => $lab->id, 'data' => ['template_id' => $template->id, 'customer_id' => $customer->id,
            'warehouse_id' => $site->id, 'department_id' => $department->id, 'service_location' => 'Native site', 'tolerance_days' => 30,
            'items' => [['item_description' => 'Native analysis', 'unit_id' => $unit->id, 'qty' => 1, 'unit_price' => 20]]]];

        return [$template, $create, ['op' => 'archive', 'actor' => $archiver->id, 'targets' => [$template->id]]];
    }

    /** @param list<int> $ids
     * @param  list<array<string,mixed>>  $operations
     * @return list<int>
     */
    private function compete(string $table, array $ids, array $operations, ?\Closure $beforeStart = null, ?\Closure $beforeRelease = null): array
    {
        $connection = DB::connection();
        $barrier = sys_get_temp_dir().'/template-archive-race-'.bin2hex(random_bytes(8));
        mkdir($barrier, 0700);
        $processes = [];
        $holding = false;
        $worker = <<<'PHP'
        require getcwd().'/vendor/autoload.php';
        $app = require getcwd().'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $connection = Illuminate\Support\Facades\DB::connection();
        if (! $app->environment('testing') || $connection->getDatabaseName() !== 'lims_unleashed_test' || $connection->getConfig('search_path') !== $argv[1]) {
            throw new RuntimeException('Template archive concurrency requires its dedicated test schema.');
        }
        Illuminate\Support\Facades\Notification::fake();
        Illuminate\Support\Facades\Queue::fake();
        request()->setLaravelSession($app->make('session')->driver());
        $connection->beforeExecuting(function (string $query) use ($argv): void {
            if (str_starts_with($query, 'select * from "'.$argv[5].'"') && str_contains($query, 'for update')) {
                touch($argv[3].'/boundary-'.$argv[4]);
            }
        });
        $operation = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
        try {
            if ($operation['op'] === 'create') {
                $app->make(App\Actions\CreateProposal::class)->execute($operation['lab'], $operation['actor'], $operation['data']);
            } elseif ($operation['op'] === 'save_template') {
                $app->make(App\Actions\SaveProposalTemplate::class)->execute($operation['actor'], $operation['data'], $operation['target']);
            } elseif ($operation['op'] === 'import_templates') {
                $app->make(App\Actions\ImportProposalTemplates::class)->execute($operation['actor'], $operation['rows']);
            } elseif ($operation['op'] === 'status') {
                $app->make(App\Actions\SetProposalTemplateActiveStatus::class)->execute($operation['actor'], $operation['target'], $operation['active']);
            } else {
                $app->make(App\Actions\SetProposalTemplatesArchived::class)->execute($operation['actor'], $operation['targets'], true);
            }
            echo json_encode(['status' => 200]);
        } catch (Illuminate\Validation\ValidationException $exception) {
            echo json_encode(['status' => 422]);
        } catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            echo json_encode(['status' => $exception->getStatusCode()]);
        } catch (Illuminate\Auth\Access\AuthorizationException $exception) {
            echo json_encode(['status' => 403]);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            echo json_encode(['status' => 404]);
        }
        PHP;
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '', 'DB_SCHEMA' => $this->schema,
            'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'), 'DB_DATABASE' => $connection->getDatabaseName(),
            'DB_USERNAME' => $connection->getConfig('username'), 'DB_PASSWORD' => $connection->getConfig('password') ?? '',
            'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'database', 'MAIL_MAILER' => 'array', 'BROADCAST_CONNECTION' => 'null'];
        try {
            DB::beginTransaction();
            $holding = true;
            DB::table($table)->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            $beforeStart?->__invoke();
            foreach ($operations as $index => $operation) {
                $process = new Process([PHP_BINARY, '-r', $worker, $this->schema, json_encode($operation, JSON_THROW_ON_ERROR), $barrier, (string) $index, $table], base_path(), $environment, timeout: 30);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (count(glob($barrier.'/boundary-*') ?: []) < count($operations) && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertCount(count($operations), glob($barrier.'/boundary-*') ?: [], collect($processes)->map(fn (Process $process): string => $process->getErrorOutput().$process->getOutput())->implode(' | '));
            foreach ($processes as $process) {
                $this->assertTrue($process->isRunning(), $process->getErrorOutput().$process->getOutput());
                $this->assertSame('', trim($process->getOutput()));
            }
            $beforeRelease?->__invoke();
            DB::commit();
            $holding = false;
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $results[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR)['status'];
            }

            return $results;
        } finally {
            if ($holding) {
                DB::rollBack();
            }
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            foreach (glob($barrier.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($barrier);
        }
    }
}
