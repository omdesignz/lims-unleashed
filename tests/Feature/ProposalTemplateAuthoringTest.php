<?php

namespace Tests\Feature;

use App\Actions\SaveProposalTemplate;
use App\Models\ISOActivityLog;
use App\Models\Permission;
use App\Models\ProposalTemplate;
use App\Models\User;
use App\Models\VAPProposalTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProposalTemplateAuthoringTest extends TestCase
{
    use DatabaseTransactions;

    public function test_creation_and_revision_retain_intent_creator_history_and_exact_audit(): void
    {
        [$actor, $template] = $this->fixture();
        $prior = $this->snapshot();
        $this->assertSame(0, DB::table('lab_user')->where('user_id', $actor->id)->count());
        $payload = ['name' => 'Canonical creation', 'content' => '<p>First</p>', 'is_active' => false,
            'layout_schema' => ['canvas_blocks' => [], 'styles_css' => 'h1{color:blue}'], 'export_settings' => ['paper_size' => 'A4']];
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $created = VAPProposalTemplate::where('name', $payload['name'])->sole();
        $this->assertSame($actor->id, $created->user_id);
        $this->assertSame('general', $created->category);
        foreach ($payload as $field => $value) {
            $this->assertSame($value, $created->{$field});
        }
        $audit = $this->audit($created, 'created');
        $this->assertSame($actor->id, (int) $audit->causer_id);
        $this->assertSame($created->getMorphClass(), $audit->subject_type);
        $this->assertSame($actor->getMorphClass(), $audit->causer_type);
        $this->assertSame('criou o modelo de proposta', $audit->description);
        $this->assertSame([], $audit->properties->get('old'));
        $this->assertSame($payload['content'], $audit->properties->get('attributes')['content']);
        $this->assertSame($actor->id, $audit->properties->get('attributes')['user_id']);
        $before = $template->getAttributes();
        $this->putJson(route('vap-proposals.templates.update', $template), ['name' => 'Canonical revision', 'content' => '<p>Second</p>'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $stored = $template->fresh();
        $this->assertSame('Canonical revision', $stored->name);
        $this->assertSame('<p>Second</p>', $stored->content);
        foreach (array_diff(array_keys($before), ['name', 'content', 'updated_at']) as $field) {
            $this->assertSame($before[$field], $stored->getRawOriginal($field), $field);
        }
        $revision = $this->audit($stored, 'updated');
        $this->assertSame(['name' => $template->name, 'content' => $template->content], $revision->properties->get('old'));
        $this->assertSame(['name' => 'Canonical revision', 'content' => '<p>Second</p>'], $revision->properties->get('attributes'));
        foreach ($prior['history'] as $retained) {
            $this->assertSame($retained, ISOActivityLog::withoutGlobalScopes()->findOrFail($retained['id'])->getAttributes());
        }
        $this->assertSame($prior['users'], $this->snapshot()['users']);
    }

    public function test_repeated_unchanged_revision_has_no_write_timestamp_or_duplicate_audit(): void
    {
        [$actor, $template] = $this->fixture();
        $before = $this->snapshot();
        VAPProposalTemplate::saving(function (): void {
            $this->fail('Unchanged intent must not fire saving hooks.');
        });
        $this->actingAs($actor)->putJson(route('vap-proposals.templates.update', $template), ['name' => $template->name, 'content' => $template->content])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('booleanInputs')]
    public function test_supported_boolean_inputs_keep_canonical_persistence_and_history(string $mode, bool|int|string $input): void
    {
        [$actor, $template] = $this->fixture();
        $active = (bool) $input;
        DB::table('proposal_templates')->where('id', $template->id)->update(['is_active' => ! $active]);
        $payload = ['name' => 'Boolean authoring', 'content' => '<p>Boolean authoring</p>', 'is_active' => $input];
        $this->actingAs($actor);
        $response = $mode === 'create' ? $this->postJson(route('vap-proposals.templates.store'), $payload)
            : $this->putJson(route('vap-proposals.templates.update', $template), $payload);
        $response->assertRedirect()->assertSessionHasNoErrors();
        $stored = VAPProposalTemplate::where('name', $payload['name'])->sole();
        $this->assertSame($active, $stored->is_active);
        $audit = $this->audit($stored, $mode === 'create' ? 'created' : 'updated');
        $this->assertSame($active, $audit->properties->get('attributes')['is_active']);
    }

    public static function booleanInputs(): array
    {
        $cases = [];
        foreach (['create', 'update'] as $mode) {
            foreach ([false, true, 0, 1, '0', '1'] as $input) {
                $cases[] = [$mode, $input];
            }
        }

        return $cases;
    }

    #[DataProvider('faults')]
    public function test_lifecycle_audit_and_authority_faults_roll_back_the_complete_write(string $mode, string $fault): void
    {
        [$actor, $template] = $this->fixture();
        $before = $this->snapshot();
        if ($fault === 'save_veto') {
            VAPProposalTemplate::saving(fn (): bool => false);
        } elseif ($fault === 'saving_content') {
            VAPProposalTemplate::saving(function (VAPProposalTemplate $record): void {
                $record->content = 'FORGED';
            });
        } elseif ($fault === 'saved_content') {
            VAPProposalTemplate::saved(function (VAPProposalTemplate $record): void {
                DB::table('proposal_templates')->where('id', $record->id)->update(['content' => 'FORGED']);
            });
        } else {
            ISOActivityLog::creating(function (ISOActivityLog $audit) use ($fault, $actor, $mode): ?bool {
                if ($audit->event !== ($mode === 'create' ? 'created' : 'updated') || $audit->subject_type !== (new VAPProposalTemplate)->getMorphClass()) {
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
                    'root_creator' => ['user_id' => $actor->id], 'root_status' => ['is_active' => false],
                    'root_layout' => ['layout_schema' => json_encode(['FORGED' => true])],
                    'root_export' => ['export_settings' => json_encode(['FORGED' => true])],
                    'root_created_at' => ['created_at' => '2001-01-01 00:00:00'],
                    'root_updated_at' => ['updated_at' => '2001-01-01 00:00:00'], 'root_archive' => ['deleted_at' => now()]];
                if (isset($auditFields[$fault])) {
                    $audit->forceFill($auditFields[$fault]);
                } elseif (isset($rootFields[$fault])) {
                    if ($fault === 'root_creator' && $mode === 'create') {
                        $rootFields[$fault]['user_id'] = User::where('id', '!=', $actor->id)->firstOrFail()->id;
                    }
                    DB::table('proposal_templates')->where('id', $audit->subject_id)->update($rootFields[$fault]);
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
                    activity()->performedOn($audit->subject)->causedBy($actor)->event('injected')->log('FORGED');
                } elseif ($fault === 'root_delete') {
                    DB::table('proposal_templates')->where('id', $audit->subject_id)->delete();
                }

                return null;
            });
        }
        $this->actingAs($actor);
        $payload = ['name' => 'Checked authoring', 'content' => '<p>Checked</p>'];
        $response = $mode === 'create' ? $this->postJson(route('vap-proposals.templates.store'), $payload)
            : $this->putJson(route('vap-proposals.templates.update', $template), $payload);
        $response->assertStatus(str_starts_with($fault, 'actor_') ? 403 : 409);
        $this->assertSame($before, $this->snapshot());
    }

    /** @return array<string, array{string,string}> */
    public static function faults(): array
    {
        $result = [];
        foreach (['create', 'update'] as $mode) {
            foreach (['save_veto', 'saving_content', 'saved_content', 'audit_veto', 'audit_description', 'audit_event', 'audit_log',
                'audit_subject', 'audit_subject_type', 'audit_actor', 'audit_actor_type', 'audit_properties',
                'root_content', 'root_name', 'root_creator', 'root_status', 'root_layout', 'root_export', 'root_created_at', 'root_updated_at',
                'root_archive', 'root_delete', 'actor_permission', 'actor_verified', 'actor_active', 'actor_archived',
                'history_delete', 'history_change', 'history_inject'] as $fault) {
                if ($mode === 'create' && str_starts_with($fault, 'history_') && $fault !== 'history_inject') {
                    continue;
                }
                $result[$mode.' '.$fault] = [$mode, $fault];
            }
        }

        return $result;
    }

    #[DataProvider('unavailableActors')]
    public function test_direct_action_requires_fresh_authority_even_for_noop(string $mode, string $state): void
    {
        [$actor, $template] = $this->fixture();
        if ($state === 'permission') {
            $actor->syncPermissions([]);
        } else {
            $actor->forceFill(match ($state) {
                'inactive' => ['is_active' => false], 'unverified' => ['email_verified_at' => null], default => ['deleted_at' => now()],
            })->save();
        }
        $before = $this->snapshot();
        try {
            app(SaveProposalTemplate::class)->execute($actor->id, ['name' => $template->name, 'content' => $template->content], $mode === 'update' ? $template->id : null);
            $this->fail('Expected current authoring authority.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public static function unavailableActors(): array
    {
        $cases = [];
        foreach (['create', 'update'] as $mode) {
            foreach (['permission', 'inactive', 'unverified', 'archived'] as $state) {
                $cases[] = [$mode, $state];
            }
        }

        return $cases;
    }

    public function test_internal_authoring_cannot_bypass_validation_or_revive_archived_templates(): void
    {
        [$actor, $template] = $this->fixture();
        $before = $this->snapshot();
        foreach ([['user_id' => $actor->id], ['layout_schema' => ['canvas_blocks' => ['invalid']]], ['name' => $template->name]] as $replacement) {
            try {
                app(SaveProposalTemplate::class)->execute($actor->id, array_replace(['name' => 'Internal authoring', 'content' => '<p>Internal</p>'], $replacement));
                $this->fail('Expected canonical validation.');
            } catch (ValidationException) {
                $this->assertSame($before, $this->snapshot());
            }
        }
        $template->delete();
        $before = $this->snapshot();
        $this->actingAs($actor)->putJson(route('vap-proposals.templates.update', $template), ['name' => $template->name, 'content' => $template->content])->assertNotFound();
        $this->assertSame($before, $this->snapshot());
    }

    /** @return array{User,VAPProposalTemplate} */
    private function fixture(): array
    {
        $actor = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        $actor->givePermissionTo(Permission::findOrCreate('add_proposal_templates', 'web'), Permission::findOrCreate('edit_proposal_templates', 'web'));
        $creator = User::factory()->create();
        $template = VAPProposalTemplate::create(['name' => 'Authoring fixture '.str()->uuid(), 'content' => '<p>Retained</p>',
            'user_id' => $creator->id, 'is_active' => true, 'category' => 'food', 'description' => 'Retained description', 'theme_preset' => 'ivory',
            'layout_schema' => ['canvas_blocks' => [], 'styles_css' => 'h1{color:blue}'], 'export_settings' => ['paper_size' => 'A4'],
            'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00']);
        activity()->performedOn($template)->causedBy($creator)->event('fixture_history')->log('Retained evidence');
        activity()->performedOn($template)->causedBy($creator)->event('fixture_history')->log('Retained alias evidence');
        ISOActivityLog::withoutGlobalScopes()->latest('id')->firstOrFail()->update(['subject_type' => ProposalTemplate::class]);

        return [$actor, $template];
    }

    private function audit(VAPProposalTemplate $template, string $event): ISOActivityLog
    {
        return ISOActivityLog::withoutGlobalScopes()->where('subject_id', $template->id)->where('subject_type', $template->getMorphClass())->where('event', $event)->sole();
    }

    /** @return array<string,list<array<string,mixed>>> */
    private function snapshot(): array
    {
        return ['templates' => VAPProposalTemplate::withTrashed()->orderBy('id')->get()->map->getAttributes()->all(),
            'users' => User::withTrashed()->orderBy('id')->get()->map->getAttributes()->all(),
            'history' => ISOActivityLog::withoutGlobalScopes()->orderBy('id')->get()->map->getAttributes()->all(),
            'permissions' => DB::table('model_has_permissions')->orderBy('model_id')->orderBy('permission_id')->get()->map(fn (object $row): array => (array) $row)->all()];
    }
}
