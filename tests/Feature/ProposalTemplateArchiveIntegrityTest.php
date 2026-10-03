<?php

namespace Tests\Feature;

use App\Actions\SetProposalTemplatesArchived;
use App\Models\ISOActivityLog;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPProposalTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProposalTemplateArchiveIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_full_template_content_creator_presentation_and_prior_history_survive_archive_restore_and_replay(): void
    {
        [$actor, $first, $last] = $this->fixture();
        $before = $this->snapshot();
        foreach ([true, true, false, false] as $index => $archived) {
            $count = app(SetProposalTemplatesArchived::class)->execute($actor->id, [$last->id, $first->id], $archived);
            $this->assertSame($index % 2 === 0 ? 2 : 0, $count);
            foreach ([$first, $last] as $template) {
                $stored = $template->fresh();
                $this->assertSame($archived, $stored->trashed());
                foreach (['name', 'content', 'user_id', 'created_at', 'category', 'description', 'is_active', 'theme_preset', 'layout_schema', 'export_settings'] as $field) {
                    $this->assertSame($template->getRawOriginal($field), $stored->getRawOriginal($field), $field);
                }
            }
        }
        $this->assertSame(6, ISOActivityLog::withoutGlobalScopes()->count());
        foreach ($before['history'] as $old) {
            $this->assertSame($old, ISOActivityLog::withoutGlobalScopes()->findOrFail($old['id'])->getAttributes());
        }
    }

    #[DataProvider('archiveIntent')]
    public function test_noop_batch_members_remain_guarded_after_a_later_member_audit(bool $archived): void
    {
        [$actor, $first, $last] = $this->fixture();
        if ($archived) {
            $first->delete();
        } else {
            $last->delete();
        }
        $before = $this->snapshot();
        ISOActivityLog::creating(function (ISOActivityLog $audit) use ($first, $last): void {
            if ($audit->subject_type === $last->getMorphClass() && (int) $audit->subject_id === $last->id) {
                DB::table('proposal_templates')->where('id', $first->id)->update(['content' => 'FORGED-NOOP']);
            }
        });
        try {
            app(SetProposalTemplatesArchived::class)->execute($actor->id, [$last->id, $first->id], $archived);
            $this->fail('Expected a skipped batch member to retain its evidence.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public static function archiveIntent(): array
    {
        return [[true], [false]];
    }

    #[DataProvider('faults')]
    public function test_late_lifecycle_and_audit_faults_rollback_the_entire_batch(string $fault, bool $archived): void
    {
        [$actor, $first, $last] = $this->fixture();
        if (! $archived) {
            $first->delete();
            $last->delete();
        }
        $before = $this->snapshot();
        ISOActivityLog::creating(function (ISOActivityLog $audit) use ($fault, $actor, $first, $last): ?bool {
            if ($audit->subject_type !== $first->getMorphClass() || (int) $audit->subject_id !== $last->id
                || ! in_array($audit->event, ['archived', 'restored'], true)) {
                return null;
            }
            $actorFields = ['actor_active' => ['is_active' => false], 'actor_verified' => ['email_verified_at' => null], 'actor_archived' => ['deleted_at' => now()]];
            if (isset($actorFields[$fault])) {
                User::whereKey($actor->id)->update($actorFields[$fault]);
            } elseif ($fault === 'actor_permission') {
                $actor->syncPermissions([]);
            } elseif ($fault === 'audit_veto') {
                return false;
            } elseif ($fault === 'audit_description') {
                $audit->description = 'FORGED';
            } elseif ($fault === 'audit_event') {
                $audit->event = 'FORGED';
            } elseif ($fault === 'audit_log') {
                $audit->log_name = 'FORGED';
            } elseif ($fault === 'audit_subject') {
                $audit->subject_id = $first->id;
            } elseif ($fault === 'audit_actor') {
                $audit->causer_id = 0;
            } elseif ($fault === 'audit_properties') {
                $audit->properties = collect(['FORGED' => true]);
            } elseif ($fault === 'history_deleted') {
                ISOActivityLog::withoutGlobalScopes()->where('event', 'fixture_history')->delete();
            } elseif ($fault === 'history_altered') {
                ISOActivityLog::withoutGlobalScopes()->where('event', 'fixture_history')->update(['description' => 'FORGED']);
            } elseif ($fault === 'history_injected') {
                activity()->performedOn($first)->causedBy($actor)->event('injected')->log('FORGED');
            } elseif ($fault === 'first_audit') {
                ISOActivityLog::withoutGlobalScopes()->where('subject_id', $first->id)->whereIn('event', ['archived', 'restored'])->update(['description' => 'FORGED']);
            } else {
                $fields = ['root' => ['content' => 'FORGED'], 'creator' => ['user_id' => $actor->id],
                    'active' => ['is_active' => false], 'layout' => ['layout_schema' => json_encode(['FORGED' => true])],
                    'export' => ['export_settings' => json_encode(['FORGED' => true])], 'created_at' => ['created_at' => '2001-01-01 00:00:00'],
                    'updated_at' => ['updated_at' => '2001-01-01 00:00:00'],
                    'archive_state' => ['deleted_at' => $first->fresh()->trashed() ? null : now()], 'archive_date' => ['deleted_at' => '2001-01-01 00:00:00']];
                if ($fault === 'root_deleted') {
                    DB::table('proposal_templates')->where('id', $first->id)->delete();
                } else {
                    DB::table('proposal_templates')->where('id', $first->id)->update($fields[$fault]);
                }
            }

            return null;
        });
        try {
            app(SetProposalTemplatesArchived::class)->execute($actor->id, [$last->id, $first->id], $archived);
            $this->fail('Expected template archive evidence to fail closed.');
        } catch (HttpException $exception) {
            $this->assertContains($exception->getStatusCode(), [403, 409]);
        }
        $this->assertSame($before, $this->snapshot());
    }

    /** @return array<string,array{string,bool}> */
    public static function faults(): array
    {
        $cases = [];
        foreach (['actor_active', 'actor_verified', 'actor_archived', 'actor_permission', 'audit_veto', 'audit_description', 'audit_event', 'audit_log',
            'audit_subject', 'audit_actor', 'audit_properties', 'history_deleted', 'history_altered', 'history_injected', 'first_audit',
            'root', 'creator', 'active', 'layout', 'export', 'created_at', 'updated_at', 'archive_state', 'archive_date', 'root_deleted'] as $fault) {
            foreach ([true, false] as $archived) {
                $cases[$fault.($archived ? '_archive' : '_restore')] = [$fault, $archived];
            }
        }

        return $cases;
    }

    /** @return array{User,VAPProposalTemplate,VAPProposalTemplate} */
    private function fixture(): array
    {
        $actor = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach (['delete_proposal_templates', 'restore_proposal_templates'] as $permission) {
            $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $creator = User::factory()->create();
        $templates = [];
        foreach (range(1, 2) as $index) {
            $template = VAPProposalTemplate::create(['name' => 'Archive integrity '.$index.' '.str()->uuid(), 'content' => '<p>Retained '.$index.'</p>',
                'user_id' => $creator->id, 'is_active' => true, 'category' => 'food', 'description' => 'Description', 'theme_preset' => 'ivory',
                'layout_schema' => ['styles_css' => 'h1 { color: blue; }', 'canvas_blocks' => [['id' => 'block-'.$index, 'surface' => 'content']]],
                'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P', 'margin_top' => 15]]);
            activity()->performedOn($template)->causedBy($actor)->event('fixture_history')->log('Retained template evidence '.$index);
            $templates[] = $template;
        }

        return [$actor, ...$templates];
    }

    /** @return array<string,list<array<string,mixed>>> */
    private function snapshot(): array
    {
        return ['templates' => VAPProposalTemplate::withTrashed()->orderBy('id')->get()->map->getAttributes()->all(),
            'users' => User::withTrashed()->orderBy('id')->get()->map->getAttributes()->all(),
            'permissions' => DB::table('model_has_permissions')->orderBy('model_id')->orderBy('permission_id')->get()->map(fn (object $row): array => (array) $row)->all(),
            'history' => ISOActivityLog::withoutGlobalScopes()->orderBy('id')->get()->map->getAttributes()->all()];
    }
}
