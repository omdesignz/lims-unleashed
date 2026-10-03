<?php

namespace Tests\Feature;

use App\Actions\SetProposalTemplateActiveStatus;
use App\Models\ISOActivityLog;
use App\Models\Permission;
use App\Models\ProposalTemplate;
use App\Models\User;
use App\Models\VAPProposalTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProposalTemplateStatusTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('statuses')]
    public function test_explicit_status_preserves_template_and_prior_history_and_replays_without_writes(bool $active): void
    {
        [$actor, $template] = $this->fixture(! $active);
        $before = $template->getAttributes();
        $history = $this->history();
        $this->assertSame(0, DB::table('lab_user')->where('user_id', $actor->id)->count());
        $this->actingAs($actor)->putJson(route('vap-proposals.templates.toggle-status', $template), ['is_active' => $active])
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('is_active', $active);
        $stored = $template->fresh();
        $this->assertSame($active, $stored->is_active);
        foreach (array_diff(array_keys($before), ['is_active', 'updated_at']) as $field) {
            $this->assertSame($before[$field], $stored->getRawOriginal($field), $field);
        }
        $audit = ISOActivityLog::withoutGlobalScopes()->where('subject_id', $template->id)->where('event', 'activation_changed')->sole();
        $this->assertSame($actor->id, (int) $audit->causer_id);
        $this->assertSame($template->getMorphClass(), $audit->subject_type);
        $this->assertSame(['previous_is_active' => ! $active, 'is_active' => $active], $audit->properties->all());
        $this->assertSame($active ? 'proposal_template_activado' : 'proposal_template_desactivado', $audit->description);
        foreach ($history as $retained) {
            $this->assertSame($retained, ISOActivityLog::withoutGlobalScopes()->findOrFail($retained['id'])->getAttributes());
        }
        $after = $this->snapshot();
        $this->travel(10)->seconds(function () use ($template, $active, $after): void {
            $this->putJson(route('vap-proposals.templates.toggle-status', $template), ['is_active' => $active])
                ->assertOk()->assertJsonPath('is_active', $active);
            $this->assertSame($after, $this->snapshot());
        });
    }

    public static function statuses(): array
    {
        return [[true], [false]];
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_or_unexpected_fields_do_not_publish_anything(array $payload, string $field): void
    {
        [$actor, $template] = $this->fixture(true);
        $before = $this->snapshot();
        $this->actingAs($actor)->putJson(route('vap-proposals.templates.toggle-status', $template), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame($before, $this->snapshot());
    }

    public static function invalidPayloads(): array
    {
        return [
            'missing' => [[], 'is_active'], 'null' => [['is_active' => null], 'is_active'],
            'string' => [['is_active' => 'false'], 'is_active'], 'array' => [['is_active' => [false]], 'is_active'],
            'nonboolean number' => [['is_active' => 2], 'is_active'],
            'content' => [['is_active' => false, 'content' => 'FORGED'], 'content'],
            'creator' => [['is_active' => false, 'user_id' => 9], 'user_id'],
            'archive' => [['is_active' => false, 'deleted_at' => null], 'deleted_at'],
        ];
    }

    #[DataProvider('unavailableActors')]
    public function test_current_authority_is_required_even_for_noop_requests(string $state): void
    {
        [$actor, $template] = $this->fixture(true);
        if ($state === 'permission') {
            $actor->syncPermissions([]);
        } else {
            $actor->forceFill(match ($state) {
                'inactive' => ['is_active' => false], 'unverified' => ['email_verified_at' => null], default => ['deleted_at' => now()],
            })->save();
        }
        $before = $this->snapshot();
        try {
            app(SetProposalTemplateActiveStatus::class)->execute($actor->id, $template->id, true);
            $this->fail('Expected fresh status authority.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public static function unavailableActors(): array
    {
        return [['permission'], ['inactive'], ['unverified'], ['archived']];
    }

    public function test_missing_and_archived_templates_cannot_be_changed(): void
    {
        [$actor, $template] = $this->fixture(true);
        $template->delete();
        $before = $this->snapshot();
        $this->actingAs($actor)->putJson(route('vap-proposals.templates.toggle-status', $template), ['is_active' => false])->assertNotFound();
        $this->putJson(route('vap-proposals.templates.toggle-status', $template->id + 1000000), ['is_active' => false])->assertNotFound();
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('faults')]
    public function test_lifecycle_authority_or_audit_faults_rollback_the_whole_operation(string $fault, bool $active): void
    {
        [$actor, $template] = $this->fixture(! $active);
        $before = $this->snapshot();
        if ($fault === 'save_veto') {
            VAPProposalTemplate::saving(fn (): bool => false);
        } elseif ($fault === 'saving_status') {
            VAPProposalTemplate::saving(function (VAPProposalTemplate $record) use ($active): void {
                $record->is_active = ! $active;
            });
        } elseif ($fault === 'updated_root') {
            VAPProposalTemplate::updated(function (VAPProposalTemplate $record): void {
                DB::table('proposal_templates')->where('id', $record->id)->update(['content' => 'FORGED']);
            });
        } else {
            ISOActivityLog::creating(function (ISOActivityLog $audit) use ($fault, $actor, $template, $active): ?bool {
                if ($audit->event !== 'activation_changed') {
                    return null;
                }
                if ($fault === 'audit_veto') {
                    return false;
                }
                $auditFields = ['audit_description' => ['description' => 'FORGED'], 'audit_event' => ['event' => 'FORGED'],
                    'audit_log' => ['log_name' => 'FORGED'], 'audit_subject' => ['subject_id' => 0],
                    'audit_subject_type' => ['subject_type' => User::class], 'audit_actor' => ['causer_id' => 0],
                    'audit_actor_type' => ['causer_type' => VAPProposalTemplate::class], 'audit_properties' => ['properties' => collect(['FORGED' => true])]];
                $rootFields = ['root_content' => ['content' => 'FORGED'], 'root_name' => ['name' => 'FORGED'],
                    'root_creator' => ['user_id' => $actor->id], 'root_status' => ['is_active' => ! $active],
                    'root_layout' => ['layout_schema' => json_encode(['FORGED' => true])],
                    'root_export' => ['export_settings' => json_encode(['FORGED' => true])],
                    'root_created_at' => ['created_at' => '2001-01-01 00:00:00'],
                    'root_updated_at' => ['updated_at' => '2001-01-01 00:00:00'], 'root_archive' => ['deleted_at' => now()]];
                if (isset($auditFields[$fault])) {
                    $audit->forceFill($auditFields[$fault]);
                } elseif (isset($rootFields[$fault])) {
                    DB::table('proposal_templates')->where('id', $template->id)->update($rootFields[$fault]);
                } elseif ($fault === 'actor_permission') {
                    $actor->syncPermissions([]);
                } elseif ($fault === 'actor_verified') {
                    User::whereKey($actor->id)->update(['email_verified_at' => null]);
                } elseif ($fault === 'actor_active') {
                    User::whereKey($actor->id)->update(['is_active' => false]);
                } elseif ($fault === 'actor_archived') {
                    User::whereKey($actor->id)->update(['deleted_at' => now()]);
                } elseif ($fault === 'history_delete') {
                    ISOActivityLog::withoutGlobalScopes()->where('event', 'fixture_history')->delete();
                } elseif ($fault === 'history_change') {
                    ISOActivityLog::withoutGlobalScopes()->where('event', 'fixture_history')->update(['description' => 'FORGED']);
                } elseif ($fault === 'history_inject') {
                    activity()->performedOn($template)->causedBy($actor)->event('injected')->log('FORGED');
                } elseif ($fault === 'root_delete') {
                    DB::table('proposal_templates')->where('id', $template->id)->delete();
                }

                return null;
            });
        }
        $this->actingAs($actor)->putJson(route('vap-proposals.templates.toggle-status', $template), ['is_active' => $active])
            ->assertStatus(str_starts_with($fault, 'actor_') ? 403 : 409);
        $this->assertSame($before, $this->snapshot());
    }

    /** @return array<string, array{string, bool}> */
    public static function faults(): array
    {
        $cases = [];
        foreach (['save_veto', 'saving_status', 'updated_root', 'audit_veto', 'audit_description', 'audit_event', 'audit_log',
            'audit_subject', 'audit_subject_type', 'audit_actor', 'audit_actor_type', 'audit_properties',
            'root_content', 'root_name', 'root_creator', 'root_status', 'root_layout', 'root_export', 'root_created_at', 'root_updated_at',
            'root_archive', 'root_delete', 'actor_permission', 'actor_verified', 'actor_active', 'actor_archived',
            'history_delete', 'history_change', 'history_inject'] as $fault) {
            foreach ([true, false] as $active) {
                $cases[$fault.($active ? '_activate' : '_deactivate')] = [$fault, $active];
            }
        }

        return $cases;
    }

    /** @return array{User, VAPProposalTemplate} */
    private function fixture(bool $active): array
    {
        $actor = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        $actor->givePermissionTo(Permission::findOrCreate('edit_proposal_templates', 'web'));
        $creator = User::factory()->create();
        $template = VAPProposalTemplate::create(['name' => 'Status fixture '.str()->uuid(), 'content' => '<p>Retained</p>',
            'user_id' => $creator->id, 'is_active' => $active, 'category' => 'food', 'description' => 'Keep description', 'theme_preset' => 'ivory',
            'layout_schema' => ['canvas_blocks' => [['id' => 'retained', 'surface' => 'content']], 'styles_css' => 'h1{color:blue}'],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P', 'margin_top' => 15],
            'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00']);
        activity()->performedOn($template)->causedBy($creator)->event('fixture_history')->log('Retained evidence');
        activity()->performedOn($template)->causedBy($creator)->event('fixture_history')->log('Retained alias evidence');
        ISOActivityLog::withoutGlobalScopes()->latest('id')->firstOrFail()->update(['subject_type' => ProposalTemplate::class]);

        return [$actor, $template];
    }

    /** @return list<array<string, mixed>> */
    private function history(): array
    {
        return ISOActivityLog::withoutGlobalScopes()->orderBy('id')->get()->map->getAttributes()->all();
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function snapshot(): array
    {
        return ['templates' => VAPProposalTemplate::withTrashed()->orderBy('id')->get()->map->getAttributes()->all(),
            'users' => User::withTrashed()->orderBy('id')->get()->map->getAttributes()->all(), 'history' => $this->history(),
            'permissions' => DB::table('model_has_permissions')->orderBy('model_id')->orderBy('permission_id')->get()->map(fn (object $row): array => (array) $row)->all()];
    }
}
