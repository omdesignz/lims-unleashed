<?php

namespace Tests\Feature;

use App\Actions\PrepareSampleEntryPayload;
use App\Actions\SaveWorksheet;
use App\Models;
use App\Support\SampleEntryCollectionFlowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LaboratoryWorksheetAccessTest extends TestCase
{
    use DatabaseTransactions;

    private Models\User $operator;

    private Models\VAPLab $lab;

    private Models\VAPLab $peerLab;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $network = Models\LabNetwork::query()->create(['name' => 'Worksheet network '.Str::uuid()]);
        $this->lab = Models\VAPLab::factory()->create(['network_id' => $network->id]);
        $this->peerLab = Models\VAPLab::factory()->create(['network_id' => $network->id]);
        $network->update(['main_lab_id' => $this->lab->id]);
        $this->operator = Models\User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach (['view', 'add', 'edit', 'delete', 'restore'] as $ability) {
            $this->operator->givePermissionTo(Models\Permission::findOrCreate($ability.'_worksheets', 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id, 'can_view_network' => true]);
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
        Notification::fake();
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    public function test_network_visibility_cannot_generate_a_peer_analysis_worksheet(): void
    {
        $peer = $this->fixture($this->peerLab);
        $before = Models\Worksheet::withTrashed()->count();

        $this->post(route('analysis.worksheet-draft', $peer['analysis']->id))->assertNotFound();
        $this->assertSame($before, Models\Worksheet::withTrashed()->count());
    }

    public function test_canonical_draft_replay_preserves_cells_author_scope_and_issued_identifiers(): void
    {
        $local = $this->fixture($this->lab);
        $identity = collect(['entry', 'accession', 'code', 'sample', 'analysis'])
            ->mapWithKeys(fn (string $key): array => [$key => $local[$key]->fresh()->getAttributes()])->all();
        $this->post(route('analysis.worksheet-draft', $local['analysis']->id))->assertRedirect();
        $worksheet = Models\Worksheet::query()->where('analysis_id', $local['analysis']->id)->sole();
        $this->assertSame($this->lab->id, $worksheet->lab_id);
        $this->assertSame($this->operator->id, $worksheet->user_id);
        $this->assertSame($local['accession']->id, $worksheet->worksheets['collection_product_id']);
        $this->assertSame($local['sample']->id, $worksheet->worksheets['sample_id']);
        $this->assertSame($local['profile']->id, $worksheet->worksheets['profile_id']);
        $this->assertSame(1, $worksheet->worksheets['scope_control']['expected_count']);

        $payload = $this->payload();
        $this->put(route('worksheets.update', $worksheet), $payload)->assertRedirect(route('worksheets.show', $worksheet));
        $before = $worksheet->fresh()->getAttributes();
        $activities = $this->activities($worksheet);
        $this->post(route('analysis.worksheet-draft', $local['analysis']->id))->assertRedirect(route('worksheets.show', $worksheet));
        $this->assertSame($before, $worksheet->fresh()->getAttributes());
        $this->assertSame($activities, $this->activities($worksheet));
        foreach ($identity as $key => $attributes) {
            $this->assertSame($attributes, $local[$key]->fresh()->getAttributes());
        }
        $this->get(route('worksheets.show', $worksheet))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->component('Worksheets/Edit')->where('worksheet.id', $worksheet->id)->where('can_edit', true)
            ->where('worksheet.worksheets.sheets.0.data.0.0', 'Valor de bancada')
            ->where('worksheet.worksheets.sheets.0.data.0.1', 0)->missing('worksheet.user_id'));
    }

    public function test_manual_workbooks_are_server_owned_and_do_not_create_analytical_records(): void
    {
        $before = Models\Analysis::query()->count();
        $this->post(route('worksheets.store'), $this->payload())->assertRedirect();
        $worksheet = Models\Worksheet::query()->where('lab_id', $this->lab->id)->sole();
        $this->assertSame($this->operator->id, $worksheet->user_id);
        $this->assertNull($worksheet->analysis_id);
        $this->assertSame($this->payload()['worksheets'], $worksheet->worksheets);
        $this->assertSame($before, Models\Analysis::query()->count());
        $before = $worksheet->getAttributes();
        $activities = $this->activities($worksheet);
        $this->put(route('worksheets.update', $worksheet), $this->payload())->assertRedirect();
        $this->assertSame($before, $worksheet->fresh()->getAttributes());
        $this->assertSame($activities, $this->activities($worksheet));
    }

    public function test_draft_values_exclude_results_with_foreign_analytical_lineage_and_preserve_zero(): void
    {
        $local = $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        $result = [
            'sample_id' => $local['sample']->id, 'parameter_id' => $local['parameter']->id,
            'profile_id' => $local['profile']->id, 'code_id' => $local['code']->id,
            'collection_id' => $local['accession']->id, 'product_id' => $local['product']->id,
            'resultable_id' => $local['analysis']->id, 'resultable_type' => $local['analysis']->getMorphClass(),
            'inserted_date' => now()->subDay(), 'inserted_value' => '0', 'insertion_notes' => 'Local bench note',
        ];
        Models\Result::query()->create($result);
        Models\Result::query()->create(array_replace($result, [
            'code_id' => $peer['code']->id, 'collection_id' => $peer['accession']->id,
            'resultable_id' => $peer['analysis']->id, 'approved_date' => now(),
            'approved_value' => 'Foreign secret', 'approval_notes' => 'Peer scientific evidence',
        ]));
        $worksheet = $this->draft($local);
        $rows = $worksheet->worksheets['sheets'][0]['data'];
        $parameter = $rows[array_key_last($rows)];
        $this->assertSame('0', $parameter[7]);
        $this->assertSame('Local bench note', $parameter[9]);
        $this->assertSame(1, $worksheet->worksheets['scope_control']['completed_count']);
        $this->assertStringNotContainsString('Foreign secret', json_encode($worksheet->worksheets, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('Peer scientific evidence', json_encode($worksheet->worksheets, JSON_THROW_ON_ERROR));
    }

    public function test_unique_analysis_constraint_covers_archived_drafts_without_losing_the_original(): void
    {
        $local = $this->fixture($this->lab);
        $worksheet = $this->draft($local);
        foreach ([false, true] as $archived) {
            if ($archived) {
                $worksheet->delete();
            }
            $before = $worksheet->fresh()->getAttributes();
            try {
                DB::transaction(fn () => Models\Worksheet::query()->create([
                    'name' => 'Duplicate draft', 'lab_id' => $this->lab->id, 'user_id' => $this->operator->id,
                    'analysis_id' => $local['analysis']->id, 'worksheets' => $worksheet->worksheets,
                ]));
                $this->fail('The database accepted a duplicate analytical worksheet.');
            } catch (QueryException $exception) {
                $this->assertSame('23505', (string) $exception->getCode());
            }
            $this->assertSame($before, $worksheet->fresh()->getAttributes());
            $this->assertSame(1, Models\Worksheet::withTrashed()->where('analysis_id', $local['analysis']->id)->count());
        }
    }

    public function test_draft_preserves_issued_parameter_identity_after_catalogue_changes(): void
    {
        $local = $this->fixture($this->lab);
        $issuedCode = (string) $local['parameter']->code;
        $issuedUnit = $local['unit']->code;
        $local['profile']->parameters()->detach();
        $replacement = Models\Parameter::query()->create(['name' => 'Replacement catalogue parameter', 'code' => Str::uuid(), 'active' => true]);
        $local['profile']->parameters()->attach($replacement);
        $local['parameter']->update(['name' => 'Changed catalogue label', 'code' => Str::uuid(), 'requires_calculation' => true]);
        $local['unit']->update(['code' => 'CHANGED']);
        $worksheet = $this->draft($local);
        $this->assertSame(1, $worksheet->worksheets['scope_control']['expected_count']);
        $this->assertSame($local['parameter']->id, $worksheet->worksheets['scope_control']['missing_parameters'][0]['id']);
        $this->assertSame('Worksheet parameter', $worksheet->worksheets['scope_control']['missing_parameters'][0]['name']);
        $rows = $worksheet->worksheets['sheets'][0]['data'];
        $parameter = $rows[array_key_last($rows)];
        $this->assertSame($issuedCode, $parameter[1]);
        $this->assertSame('Worksheet parameter', $parameter[2]);
        $this->assertSame($issuedUnit, $parameter[3]);
        $this->assertSame('Manual', $parameter[4]);
        $this->assertSame('1.5', $parameter[5]);
        $this->assertSame('9.5', $parameter[6]);
        $this->assertStringNotContainsString('Replacement catalogue parameter', json_encode($worksheet->worksheets, JSON_THROW_ON_ERROR));
    }

    public function test_outside_scope_results_do_not_count_as_completed_parameters(): void
    {
        $local = $this->fixture($this->lab);
        $outside = Models\Parameter::query()->create(['name' => 'Outside issued scope', 'code' => Str::uuid(), 'active' => true]);
        Models\Result::query()->create([
            'sample_id' => $local['sample']->id, 'parameter_id' => $outside->id,
            'profile_id' => $local['profile']->id, 'code_id' => $local['code']->id,
            'collection_id' => $local['accession']->id, 'product_id' => $local['product']->id,
            'resultable_id' => $local['analysis']->id, 'resultable_type' => $local['analysis']->getMorphClass(),
            'inserted_date' => now(), 'inserted_value' => 'Unexpected value',
        ]);
        $worksheet = $this->draft($local);
        $this->assertSame(1, $worksheet->worksheets['scope_control']['expected_count']);
        $this->assertSame(0, $worksheet->worksheets['scope_control']['completed_count']);
        $this->assertSame(1, $worksheet->worksheets['scope_control']['missing_count']);
        $this->assertStringNotContainsString('Unexpected value', json_encode($worksheet->worksheets, JSON_THROW_ON_ERROR));
    }

    #[DataProvider('invalidIssuedScopes')]
    public function test_missing_or_invalid_issued_scope_cannot_be_replaced_with_current_catalogue_data(string $variant): void
    {
        $local = $this->fixture($this->lab);
        $info = $local['entry']->client_submitted_info;
        if ($variant === 'missing') {
            unset($info['required_parameters']);
        } elseif ($variant === 'empty') {
            $info['required_parameters'] = [];
        } elseif ($variant === 'malformed') {
            $info['required_parameters'][0]['id'] = 'broken';
        } elseif ($variant === 'foreign-profile') {
            $info['required_parameters'][0]['profile_ids'] = [$local['profile']->id + 999999];
        } else {
            $info['required_parameters'][0]['profile_definitions'] = [['profile_id' => 'invalid']];
        }
        DB::table('sample_entries')->where('id', $local['entry']->id)->update(['client_submitted_info' => json_encode($info, JSON_THROW_ON_ERROR)]);
        $before = Models\Worksheet::withTrashed()->count();
        $this->postJson(route('analysis.worksheet-draft', $local['analysis']->id))->assertUnprocessable()->assertJsonValidationErrors('worksheet');
        $this->assertSame($before, Models\Worksheet::withTrashed()->count());
    }

    /** @return array<string, array{string}> */
    public static function invalidIssuedScopes(): array
    {
        return collect(['missing', 'empty', 'malformed', 'foreign-profile', 'malformed-definition'])
            ->mapWithKeys(fn (string $variant): array => [$variant => [$variant]])->all();
    }

    public function test_older_scope_snapshots_do_not_fabricate_unrecorded_calculation_or_reference_settings(): void
    {
        $local = $this->fixture($this->lab);
        $info = $local['entry']->client_submitted_info;
        unset($info['required_parameters'][0]['requires_calculation'], $info['required_parameters'][0]['profile_definitions']);
        DB::table('sample_entries')->where('id', $local['entry']->id)->update(['client_submitted_info' => json_encode($info, JSON_THROW_ON_ERROR)]);
        $worksheet = $this->draft($local);
        $rows = $worksheet->worksheets['sheets'][0]['data'];
        $parameter = $rows[array_key_last($rows)];
        $this->assertNull($parameter[3]);
        $this->assertSame('Não registado', $parameter[4]);
        $this->assertNull($parameter[5]);
        $this->assertNull($parameter[6]);
    }

    #[DataProvider('immutableWorkbookFields')]
    public function test_model_updates_cannot_reassign_workbook_identity_or_scope(string $path): void
    {
        $local = $this->fixture($this->lab);
        $worksheet = $this->draft($local);
        $before = $worksheet->getAttributes();
        $changes = [];
        if (str_starts_with($path, 'worksheets.')) {
            $changes['worksheets'] = $worksheet->worksheets;
        }
        data_set($changes, $path, 999999);
        try {
            $worksheet->update($changes);
            $this->fail('The model allowed issued worksheet identity to change.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('cannot be changed', $exception->getMessage());
        }
        $this->assertSame($before, $worksheet->fresh()->getAttributes());
    }

    /** @return array<string, array{string}> */
    public static function immutableWorkbookFields(): array
    {
        return collect(['lab_id', 'analysis_id', 'user_id', 'worksheets.analysis_id', 'worksheets.collection_product_id',
            'worksheets.sample_id', 'worksheets.profile_id', 'worksheets.generated_from', 'worksheets.scope_control.expected_count'])
            ->mapWithKeys(fn (string $path): array => [$path => [$path]])->all();
    }

    public function test_lists_lookup_direct_access_and_archived_filters_are_lab_private(): void
    {
        $local = $this->manual($this->lab, 'Local worksheet needle');
        $peer = $this->manual($this->peerLab, 'Foreign worksheet secret');
        $this->get(route('worksheets.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->component('Worksheets/Index')->has('worksheets', 1)->where('worksheets.0.id', $local->id));
        $this->getJson(route('worksheets.getWorksheet', ['q' => 'local worksheet NEEDLE']))->assertOk()
            ->assertJsonCount(1)->assertJsonPath('0.id', $local->id);
        $this->getJson(route('worksheets.getWorksheet', ['q' => 'Foreign worksheet secret']))->assertOk()->assertExactJson([]);
        $this->get(route('worksheets.show', $peer))->assertNotFound();
        $this->putJson(route('worksheets.update', $peer), $this->payload())->assertNotFound();
        $local->delete();
        $peer->delete();
        foreach (['only', 'with'] as $filter) {
            $this->get(route('worksheets.index', ['trashed' => $filter]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
                ->has('worksheets', 1)->where('worksheets.0.id', $local->id)
                ->where('trashed', $filter)->where('can_restore', true));
        }
        $this->getJson(route('worksheets.getWorksheet'))->assertOk()->assertExactJson([]);
        $this->get(route('worksheets.show', $local))->assertNotFound();
        $this->putJson(route('worksheets.update', $local), $this->payload())->assertNotFound();
    }

    public function test_lab_switching_requires_direct_membership_and_never_merges_lists(): void
    {
        $local = $this->manual($this->lab);
        $peer = $this->manual($this->peerLab);
        $this->withSession(['active_lab_id' => $this->peerLab->id])->get(route('worksheets.index'))
            ->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('worksheets', 1)->where('worksheets.0.id', $local->id));
        DB::table('lab_user')->insert(['lab_id' => $this->peerLab->id, 'user_id' => $this->operator->id]);
        $this->withSession(['active_lab_id' => $this->peerLab->id])->get(route('worksheets.index'))
            ->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('worksheets', 1)->where('worksheets.0.id', $peer->id));
        $this->get(route('worksheets.show', $local))->assertNotFound();
    }

    public function test_view_permission_does_not_imply_editor_or_archive_permission(): void
    {
        $worksheet = $this->manual($this->lab);
        foreach (['edit', 'delete', 'restore'] as $ability) {
            $this->operator->revokePermissionTo($ability.'_worksheets');
        }
        $this->get(route('worksheets.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->where('can_restore', false)->where('trashed', ''));
        $this->get(route('worksheets.show', $worksheet))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->where('can_edit', false));
        $this->putJson(route('worksheets.update', $worksheet), $this->payload())->assertForbidden();
        $this->postJson(route('worksheets.destroy'), ['recordIds' => [$worksheet->id]])->assertForbidden();
        $this->postJson(route('worksheets.restore'), ['recordIds' => [$worksheet->id]])->assertForbidden();
    }

    #[DataProvider('accessRevocations')]
    public function test_all_entrypoints_recheck_current_membership_user_and_lab(string $change): void
    {
        $local = $this->fixture($this->lab);
        $worksheet = $this->manual($this->lab);
        $before = $worksheet->getAttributes();
        match ($change) {
            'membership' => DB::table('lab_user')->where('user_id', $this->operator->id)->delete(),
            'inactive' => DB::table('users')->where('id', $this->operator->id)->update(['is_active' => false]),
            'unverified' => DB::table('users')->where('id', $this->operator->id)->update(['email_verified_at' => null]),
            'laboratory' => DB::table('labs')->where('id', $this->lab->id)->update(['deleted_at' => now()]),
        };
        $this->getJson(route('worksheets.index'))->assertForbidden();
        $this->getJson(route('worksheets.getWorksheet'))->assertForbidden();
        $this->getJson(route('worksheets.show', $worksheet))->assertForbidden();
        $this->postJson(route('worksheets.store'), $this->payload())->assertForbidden();
        $this->putJson(route('worksheets.update', $worksheet), $this->payload())->assertForbidden();
        $this->postJson(route('worksheets.destroy'), ['recordIds' => [$worksheet->id]])->assertForbidden();
        $this->postJson(route('worksheets.restore'), ['recordIds' => [$worksheet->id]])->assertForbidden();
        $this->postJson(route('analysis.worksheet-draft', $local['analysis']->id))->assertForbidden();
        $this->assertSame($before, $worksheet->fresh()->getAttributes());
    }

    public static function accessRevocations(): array
    {
        return collect(['membership', 'inactive', 'unverified', 'laboratory'])->mapWithKeys(fn (string $value): array => [$value => [$value]])->all();
    }

    public function test_revoked_permissions_fail_before_reads_or_writes(): void
    {
        $local = $this->fixture($this->lab);
        $worksheet = $this->manual($this->lab);
        $this->operator->revokePermissionTo('view_worksheets');
        $this->getJson(route('worksheets.index'))->assertForbidden();
        $this->getJson(route('worksheets.getWorksheet'))->assertForbidden();
        $this->getJson(route('worksheets.show', $worksheet))->assertForbidden();
        $this->postJson(route('analysis.worksheet-draft', $local['analysis']->id))->assertForbidden();
        $this->operator->revokePermissionTo('add_worksheets');
        $this->postJson(route('worksheets.store'), $this->payload())->assertForbidden();
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_and_forged_payloads_fail_before_creation_or_update(string $path, mixed $value): void
    {
        $worksheet = $this->manual($this->lab);
        $before = $worksheet->getAttributes();
        $payload = $this->payload();
        data_set($payload, $path, $value);
        $this->postJson(route('worksheets.store'), $payload)->assertUnprocessable();
        $this->putJson(route('worksheets.update', $worksheet), $payload)->assertUnprocessable();
        $this->assertSame($before, $worksheet->fresh()->getAttributes());
        $this->assertSame(1, Models\Worksheet::query()->where('lab_id', $this->lab->id)->count());
    }

    public static function invalidPayloads(): array
    {
        return [
            'forged lab' => ['lab_id', 999], 'forged author' => ['user_id', 999], 'forged analysis column' => ['analysis_id', 999],
            'forged analysis JSON' => ['worksheets.analysis_id', 999], 'forged accession' => ['worksheets.collection_product_id', 999],
            'forged scope' => ['worksheets.scope_control', ['status' => 'complete']],
            'unknown envelope key' => ['worksheets.secret', 'value'], 'unknown sheet key' => ['worksheets.sheets.0.secret', 'value'],
            'empty name' => ['name', ''], 'long name' => ['name', str_repeat('a', 256)],
            'empty sheets' => ['worksheets.sheets', []], 'empty grid' => ['worksheets.sheets.0.data', []],
            'empty row' => ['worksheets.sheets.0.data.0', []], 'non scalar cell' => ['worksheets.sheets.0.data.0.0', ['hidden' => 1]],
            'long cell' => ['worksheets.sheets.0.data.0.0', str_repeat('é', 2001)],
            'duplicate sheet identifiers' => ['worksheets.sheets', [
                ['id' => 'same', 'name' => 'A', 'data' => [['']]], ['id' => 'same', 'name' => 'B', 'data' => [['']]],
            ]],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_internal_save_action_reuses_the_full_editable_workbook_validation(string $path, mixed $value): void
    {
        $worksheet = $this->manual($this->lab);
        $before = $worksheet->getAttributes();
        $payload = $this->payload();
        data_set($payload, $path, $value);
        foreach ([null, $worksheet->id] as $id) {
            try {
                app(SaveWorksheet::class)->execute($this->lab->id, $this->operator->id, $payload, $id);
                $this->fail('The internal action accepted an invalid workbook.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
        $this->assertSame($before, $worksheet->fresh()->getAttributes());
        $this->assertSame(1, Models\Worksheet::withTrashed()->where('lab_id', $this->lab->id)->count());
        $this->assertSame(0, $this->activities($worksheet));
    }

    #[DataProvider('oversizedGrids')]
    public function test_oversized_grids_fail_at_the_shared_boundary_without_writes(array $sheets, string $field): void
    {
        $worksheet = $this->manual($this->lab);
        $before = $worksheet->getAttributes();
        $payload = $this->payload();
        $payload['worksheets']['sheets'] = $sheets;
        $this->postJson(route('worksheets.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->putJson(route('worksheets.update', $worksheet), $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
        try {
            app(SaveWorksheet::class)->execute($this->lab->id, $this->operator->id, $payload, $worksheet->id);
            $this->fail('The internal action accepted an oversized workbook.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
        $this->assertSame($before, $worksheet->fresh()->getAttributes());
        $this->assertSame(1, Models\Worksheet::withTrashed()->where('lab_id', $this->lab->id)->count());
        $this->assertSame(0, $this->activities($worksheet));
    }

    /** @return array<string, array{array, string}> */
    public static function oversizedGrids(): array
    {
        $sheet = ['id' => 's1', 'name' => 'Bancada', 'data' => [['']]];

        return [
            'sheet limit' => [array_fill(0, 21, $sheet), 'worksheets.sheets'],
            'row limit' => [[array_replace($sheet, ['data' => array_fill(0, 2001, [''])])], 'worksheets.sheets.0.data'],
            'column limit' => [[array_replace($sheet, ['data' => [array_fill(0, 101, '')]])], 'worksheets.sheets.0.data.0'],
            'total cell limit' => [[array_replace($sheet, ['data' => array_fill(0, 501, array_fill(0, 100, ''))])], 'worksheets'],
        ];
    }

    #[DataProvider('archivedLineage')]
    public function test_archived_lineage_blocks_draft_reads_updates_and_archive_replay(string $subject): void
    {
        $local = $this->fixture($this->lab);
        $worksheet = $this->draft($local);
        $model = $local[$subject];
        DB::table($model->getTable())->where('id', $model->id)->update(['deleted_at' => now()]);
        $this->get(route('worksheets.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('worksheets', 0));
        $this->getJson(route('worksheets.getWorksheet'))->assertOk()->assertExactJson([]);
        $this->get(route('worksheets.show', $worksheet))->assertNotFound();
        $this->post(route('analysis.worksheet-draft', $local['analysis']->id))->assertNotFound();
        $this->putJson(route('worksheets.update', $worksheet), $this->payload())->assertNotFound();
        $this->postJson(route('worksheets.destroy'), ['recordIds' => [$worksheet->id]])->assertNotFound();
        $this->postJson(route('worksheets.restore'), ['recordIds' => [$worksheet->id]])->assertNotFound();
    }

    public static function archivedLineage(): array
    {
        return collect(['entry', 'accession', 'code', 'sample', 'analysis'])->mapWithKeys(fn (string $value): array => [$value => [$value]])->all();
    }

    #[DataProvider('forgedLinks')]
    public function test_conflicting_stored_lineage_fails_closed(string $field): void
    {
        $local = $this->fixture($this->lab);
        $worksheet = $this->draft($local);
        $payload = $worksheet->worksheets;
        $payload[$field] = 999999999;
        DB::table('worksheets')->where('id', $worksheet->id)->update(['worksheets' => json_encode($payload, JSON_THROW_ON_ERROR)]);
        $this->get(route('worksheets.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('worksheets', 0));
        $this->get(route('worksheets.show', $worksheet))->assertNotFound();
        $this->putJson(route('worksheets.update', $worksheet), $this->payload())->assertNotFound();
        $this->post(route('analysis.worksheet-draft', $local['analysis']->id))->assertNotFound();
    }

    public static function forgedLinks(): array
    {
        return collect(['analysis_id', 'collection_product_id', 'sample_id', 'profile_id'])->mapWithKeys(fn (string $value): array => [$value => [$value]])->all();
    }

    public function test_json_claims_cannot_turn_a_manual_workbook_into_an_analysis_workbook(): void
    {
        $local = $this->fixture($this->lab);
        $worksheet = $this->manual($this->lab);
        $data = $worksheet->worksheets + ['analysis_id' => $local['analysis']->id, 'generated_from' => 'analysis_scope'];
        DB::table('worksheets')->where('id', $worksheet->id)->update(['worksheets' => json_encode($data, JSON_THROW_ON_ERROR)]);
        $this->get(route('worksheets.show', $worksheet))->assertNotFound();
        $this->post(route('analysis.worksheet-draft', $local['analysis']->id))->assertRedirect();
        $this->assertSame(2, Models\Worksheet::query()->where('lab_id', $this->lab->id)->count());
    }

    public function test_archive_restore_are_atomic_idempotent_post_operations(): void
    {
        $local = $this->manual($this->lab);
        $peer = $this->manual($this->peerLab);
        $before = $local->getAttributes();
        $this->get(route('worksheets.destroy', ['recordIds' => [$local->id]]))->assertMethodNotAllowed();
        $this->get(route('worksheets.restore', ['recordIds' => [$local->id]]))->assertMethodNotAllowed();
        $this->postJson(route('worksheets.destroy'), ['recordIds' => [$local->id, $peer->id]])->assertNotFound();
        $this->assertSame($before, $local->fresh()->getAttributes());
        $this->post(route('worksheets.destroy'), ['recordIds' => [$local->id]])->assertRedirect();
        $archived = Models\Worksheet::withTrashed()->findOrFail($local->id)->getAttributes();
        $count = $this->activities($local);
        $this->post(route('worksheets.destroy'), ['recordIds' => [$local->id]])->assertRedirect();
        $this->assertSame($archived, Models\Worksheet::withTrashed()->findOrFail($local->id)->getAttributes());
        $this->assertSame($count, $this->activities($local));
        $this->postJson(route('worksheets.restore'), ['recordIds' => [$local->id, $peer->id]])->assertNotFound();
        $this->assertSame($archived, Models\Worksheet::withTrashed()->findOrFail($local->id)->getAttributes());
        $this->post(route('worksheets.restore'), ['recordIds' => [$local->id]])->assertRedirect();
        $restored = $local->fresh()->getAttributes();
        $count = $this->activities($local);
        $this->post(route('worksheets.restore'), ['recordIds' => [$local->id]])->assertRedirect();
        $this->assertSame($restored, $local->fresh()->getAttributes());
        $this->assertSame($count, $this->activities($local));
        $this->assertSame($before['worksheets'], $restored['worksheets']);
    }

    public function test_archived_analysis_workbook_is_not_silently_recreated(): void
    {
        $local = $this->fixture($this->lab);
        $worksheet = $this->draft($local);
        $this->post(route('worksheets.destroy'), ['recordIds' => [$worksheet->id]])->assertRedirect();
        $this->postJson(route('analysis.worksheet-draft', $local['analysis']->id))->assertUnprocessable()->assertJsonValidationErrors('worksheet');
        $this->assertSame(1, Models\Worksheet::withTrashed()->where('analysis_id', $local['analysis']->id)->count());
        $this->post(route('worksheets.restore'), ['recordIds' => [$worksheet->id]])->assertRedirect();
        $this->post(route('analysis.worksheet-draft', $local['analysis']->id))->assertRedirect(route('worksheets.show', $worksheet));
    }

    #[DataProvider('invalidArchiveIds')]
    public function test_archive_validation_rejects_invalid_identifiers(mixed $ids): void
    {
        $worksheet = $this->manual($this->lab);
        foreach (['worksheets.destroy', 'worksheets.restore'] as $route) {
            $this->postJson(route($route), ['recordIds' => $ids])->assertUnprocessable();
        }
        $this->assertFalse($worksheet->fresh()->trashed());
    }

    public static function invalidArchiveIds(): array
    {
        return ['empty' => [[]], 'scalar' => ['1'], 'zero' => [[0]], 'negative' => [[-1]],
            'nested' => [[[1]]], 'text' => [['abc']], 'duplicate' => [[1, '1']],
            'overflow' => [['99999999999999999999999']], 'large batch' => [range(1, 501)]];
    }

    public function test_malformed_route_ids_and_lookup_shapes_fail_without_postgresql_errors(): void
    {
        foreach (['0', '-1', 'invalid', '9999999999999999999', str_repeat('9', 100)] as $id) {
            $this->getJson(route('worksheets.show', $id))->assertNotFound();
            $this->putJson(route('worksheets.update', $id), $this->payload())->assertNotFound();
            $this->postJson(route('analysis.worksheet-draft', $id))->assertNotFound();
        }
        foreach ([['bad'], str_repeat('a', 256)] as $query) {
            $this->getJson(route('worksheets.getWorksheet', ['q' => $query]))->assertUnprocessable()->assertJsonValidationErrors('q');
        }
    }

    public function test_workbook_activity_is_private_in_index_direct_access_and_download(): void
    {
        foreach (['view_activity_log', 'export_activity_log'] as $permission) {
            $this->operator->givePermissionTo(Models\Permission::findOrCreate($permission, 'web'));
        }
        $local = $this->manual($this->lab);
        $peer = $this->manual($this->peerLab);
        $needle = 'WorksheetAudit'.Str::uuid();
        activity()->causedBy($this->operator)->performedOn($local)->log($needle.' local');
        activity()->causedBy($this->operator)->performedOn($peer)->log($needle.' peer secret');
        $localId = DB::table('activity_log')->where('subject_type', 'worksheet')->where('subject_id', $local->id)->value('id');
        $peerId = DB::table('activity_log')->where('subject_type', 'worksheet')->where('subject_id', $peer->id)->value('id');
        $this->get(route('systemactivity.index', ['search' => $needle]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->has('record.data', 1)->where('record.data.0.id', $localId));
        $this->getJson(route('systemactivity.show', $localId))->assertOk()->assertJsonPath('activity.subject.id', $local->id);
        $this->getJson(route('systemactivity.show', $peerId))->assertNotFound();
        $response = $this->get(route('exports.download', ['dataset' => 'activity_log', 'search' => $needle]))->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $sheet = IOFactory::load($path)->getActiveSheet()->toArray();
            $this->assertCount(2, $sheet);
            $this->assertSame((string) $localId, (string) $sheet[1][0]);
            $this->assertStringNotContainsString('peer secret', json_encode($sheet, JSON_THROW_ON_ERROR));
        } finally {
            @unlink($path);
        }
        $local->delete();
        $this->getJson(route('systemactivity.show', $localId))->assertOk();
        $this->operator->revokePermissionTo('view_worksheets');
        $this->getJson(route('systemactivity.show', $localId))->assertNotFound();
    }

    #[DataProvider('cancelledWrites')]
    public function test_model_event_cancellation_rolls_back_entire_batch_and_audit(bool $restore): void
    {
        $first = $this->manual($this->lab);
        $last = $this->manual($this->lab);
        if ($restore) {
            $first->delete();
            $last->delete();
        }
        $before = [$first->fresh()->getAttributes(), $last->fresh()->getAttributes()];
        $count = $this->activities($first) + $this->activities($last);
        $event = 'eloquent.'.($restore ? 'restoring' : 'deleting').': '.Models\Worksheet::class;
        Event::listen($event, fn (Models\Worksheet $worksheet): ?bool => $worksheet->id === $last->id ? false : null);
        try {
            $this->postJson(route($restore ? 'worksheets.restore' : 'worksheets.destroy'), ['recordIds' => [$first->id, $last->id]])->assertStatus(409);
            $this->assertSame($before, [$first->fresh()->getAttributes(), $last->fresh()->getAttributes()]);
            $this->assertSame($count, $this->activities($first) + $this->activities($last));
        } finally {
            Event::forget($event);
        }
    }

    public static function cancelledWrites(): array
    {
        return ['archive cancelled' => [false], 'restore cancelled' => [true]];
    }

    public function test_cancelled_creation_and_edit_leave_no_false_success_or_audit(): void
    {
        $worksheet = $this->manual($this->lab);
        $before = $worksheet->getAttributes();
        $event = 'eloquent.updating: '.Models\Worksheet::class;
        Event::listen($event, fn (): bool => false);
        try {
            $this->putJson(route('worksheets.update', $worksheet), $this->payload())->assertStatus(409);
            $this->assertSame($before, $worksheet->fresh()->getAttributes());
            $this->assertSame(0, $this->activities($worksheet));
        } finally {
            Event::forget($event);
        }
        $local = $this->fixture($this->lab);
        $event = 'eloquent.creating: '.Models\Worksheet::class;
        Event::listen($event, fn (): bool => false);
        try {
            $this->postJson(route('worksheets.store'), $this->payload())->assertStatus(409);
            $this->postJson(route('analysis.worksheet-draft', $local['analysis']->id))->assertStatus(409);
            $this->assertSame(1, Models\Worksheet::withTrashed()->where('lab_id', $this->lab->id)->count());
        } finally {
            Event::forget($event);
        }
    }

    /** @return array{name: string, worksheets: array{sheets: array}} */
    private function payload(): array
    {
        return ['name' => 'Folha técnica de bancada', 'worksheets' => ['sheets' => [
            ['id' => 'sheet-1', 'name' => 'Bancada', 'data' => [['Valor de bancada', 0, null, 'áéíóú']]],
        ]]];
    }

    private function manual(Models\VAPLab $lab, string $name = 'Manual worksheet'): Models\Worksheet
    {
        return Models\Worksheet::query()->create(['name' => $name] + $this->payload() + ['lab_id' => $lab->id, 'user_id' => $this->operator->id])->fresh();
    }

    /** @param array<string, Model> $fixture */
    private function draft(array $fixture): Models\Worksheet
    {
        $this->post(route('analysis.worksheet-draft', $fixture['analysis']->id))->assertRedirect();

        return Models\Worksheet::query()->where('analysis_id', $fixture['analysis']->id)->sole();
    }

    private function activities(Models\Worksheet $worksheet): int
    {
        return DB::table('activity_log')->where('subject_type', $worksheet->getMorphClass())->where('subject_id', $worksheet->id)->count();
    }

    /** @return array<string, Model> */
    private function fixture(Models\VAPLab $lab): array
    {
        $customer = Models\Customer::query()->create(['name' => 'Worksheet customer '.Str::uuid()]);
        $warehouse = Models\Warehouse::query()->create(['name' => 'Worksheet site '.Str::uuid(), 'customer_id' => $customer->id]);
        $department = Models\Department::factory()->create();
        $category = Models\AnalysisCategory::query()->create(['name' => 'Worksheet category '.Str::uuid(), 'code' => Str::uuid(), 'department_id' => $department->id]);
        $matrix = Models\Matrix::query()->create(['code' => Str::uuid(), 'description' => 'Worksheet matrix']);
        $profile = Models\Profile::query()->create(['name' => 'Worksheet profile', 'code' => Str::uuid(), 'category_id' => $category->id]);
        $matrix->profiles()->attach($profile);
        $parameter = Models\Parameter::query()->create(['name' => 'Worksheet parameter', 'code' => Str::uuid(), 'active' => true]);
        $unit = Models\Unit::query()->create(['code' => 'mg/L '.Str::uuid()]);
        $profile->parameters()->attach($parameter, ['unit_id' => $unit->id, 'min_ref_value' => '1.5', 'max_ref_value' => '9.5']);
        $product = Models\Product::query()->create(['name' => 'Worksheet product', 'matrix_id' => $matrix->id]);
        $payload = app(PrepareSampleEntryPayload::class)->execute([
            'code' => null, 'lab_id' => $lab->id, 'department_id' => $department->id,
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'client_submitted_info' => ['collection_type' => 'direct', 'product_id' => $product->id, 'requested_profile_ids' => [$profile->id]],
        ], null);
        $entry = Models\VAPSampleEntry::factory()->create($payload);
        $accession = app(SampleEntryCollectionFlowService::class)->sync($entry);
        $code = $accession->code;
        $sample = $code->samples()->firstOrFail();
        $analysis = Models\Analysis::query()->where('sample_id', $sample->id)->firstOrFail();

        return compact('customer', 'warehouse', 'department', 'category', 'matrix', 'profile', 'parameter', 'unit', 'product', 'entry', 'accession', 'code', 'sample', 'analysis');
    }
}
