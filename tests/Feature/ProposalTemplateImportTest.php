<?php

namespace Tests\Feature;

use App\Actions\ImportProposalTemplates;
use App\Exports\ProposalTemplatesExport;
use App\Models\ISOActivityLog;
use App\Models\Permission;
use App\Models\ProposalTemplate;
use App\Models\User;
use App\Models\VAPProposalTemplate;
use App\Support\ProposalTemplateImportFileReader;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use ZipArchive;

class ProposalTemplateImportTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('formats')]
    public function test_supported_formats_import_atomically_preserve_creator_and_replay_without_writes(string $format): void
    {
        [$actor, $template] = $this->fixture();
        $createdAt = $template->created_at->toDateTimeString();
        $rows = [['name' => $template->name, 'content' => '<p>Imported revision</p>', 'is_active' => false,
            'created_by' => 'FORGED', 'created_at' => '1900-01-01', 'updated_at' => '1900-01-01',
            'layout_schema' => ['canvas_blocks' => [], 'variable_catalog' => [], 'styles_css' => 'h1{color:blue}']],
            ['name' => 'New import', 'content' => '<p>New</p>', 'is_active' => true]];
        $this->assertSame(0, DB::table('lab_user')->where('user_id', $actor->id)->count());
        $this->assertFalse($actor->can('add_proposal_templates'));
        $this->assertFalse($actor->can('edit_proposal_templates'));
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => $this->file($rows, $format)])
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('counts.created', 1)->assertJsonPath('counts.updated', 1);
        $stored = $template->fresh();
        $this->assertSame($template->user_id, $stored->user_id);
        $this->assertSame($createdAt, $stored->created_at->toDateTimeString());
        $this->assertSame($rows[0]['content'], $stored->content);
        $this->assertFalse($stored->is_active);
        $new = VAPProposalTemplate::where('name', 'New import')->sole();
        $this->assertSame($actor->id, $new->user_id);
        $audits = ISOActivityLog::withoutGlobalScopes()->where('causer_id', $actor->id)->whereIn('event', ['created', 'updated'])->get();
        $this->assertCount(2, $audits);
        foreach ($audits as $audit) {
            $this->assertSame('import', $audit->properties->get('origin'));
            $this->assertSame($template->getMorphClass(), $audit->subject_type);
        }
        $before = $this->snapshot();
        VAPProposalTemplate::saving(function (): void {
            $this->fail('Import replay must not save unchanged rows.');
        });
        $this->postJson(route('vap-proposals.templates.import'), ['template_file' => $this->file($rows, $format)])
            ->assertOk()->assertJsonPath('counts.unchanged', 2);
        $this->assertSame($before, $this->snapshot());
    }

    public static function formats(): array
    {
        return [['json'], ['csv'], ['xlsx']];
    }

    #[DataProvider('formats')]
    public function test_import_preserves_authored_strings_byte_for_byte(string $format): void
    {
        [$actor, $template] = $this->fixture();
        $row = ['name' => $template->name, 'content' => " \n<p>Authored body</p>\n ",
            'description' => '  Authored description  ',
            'layout_schema' => ['styles_css' => " \nh1 { color: blue; }\n ",
                'canvas_blocks' => [['id' => 'body', 'content_html' => '  <p>Nested body</p>  ']],
                'variable_catalog' => [['value' => 'customer', 'label' => '  Authored label  ']]]];
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => $this->file([$row], $format)])
            ->assertOk();
        $stored = $template->fresh();
        $this->assertSame($row['content'], $stored->content);
        $this->assertSame($row['description'], $stored->description);
        $this->assertSame($row['layout_schema'], $stored->layout_schema);
        $before = $this->snapshot();
        $this->postJson(route('vap-proposals.templates.import'), ['template_file' => $this->file([$row], $format)])
            ->assertOk()->assertJsonPath('counts.unchanged', 1);
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('objectInsteadOfList')]
    public function test_json_objects_cannot_masquerade_as_ordered_layout_lists(string $format, array $layout): void
    {
        [$actor] = $this->fixture();
        $before = $this->snapshot();
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => $this->file([
            ['name' => 'Invalid object list', 'content' => 'Body', 'layout_schema' => $layout],
        ], $format)])->assertUnprocessable();
        $this->assertSame($before, $this->snapshot());
    }

    public static function objectInsteadOfList(): array
    {
        $layouts = [
            ['canvas_blocks' => (object) ['0' => ['id' => 'body']]],
            ['variable_catalog' => (object) ['0' => ['value' => 'customer']]],
        ];
        foreach (['chart_labels' => 'Label', 'chart_values' => 0, 'chart_colors' => '#ffffff'] as $field => $value) {
            $layouts[] = ['canvas_blocks' => [['id' => 'chart', 'block_kind' => 'chart_snapshot', $field => (object) ['0' => $value]]]];
        }
        $cases = [];
        foreach (['json', 'csv', 'xlsx'] as $format) {
            foreach ($layouts as $layout) {
                $cases[] = [$format, $layout];
            }
        }

        return $cases;
    }

    #[DataProvider('invalidDocuments')]
    public function test_invalid_documents_fail_before_any_model_write(string $document): void
    {
        [$actor] = $this->fixture();
        $before = $this->snapshot();
        VAPProposalTemplate::saving(function (): void {
            $this->fail('All rows must validate before the first write.');
        });
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => UploadedFile::fake()->createWithContent('models.json', $document)])
            ->assertUnprocessable()->assertJsonPath('success', false);
        $this->assertSame($before, $this->snapshot());
    }

    public static function invalidDocuments(): array
    {
        $valid = ['name' => 'Valid import', 'content' => '<p>Valid</p>'];
        $cases = ['empty' => '[]', 'object' => '{"name":"One","content":"Body"}', 'numeric object' => '{"0":{"name":"One","content":"Body"}}', 'scalar' => '42', 'null' => 'null',
            'broken' => '[', 'scalar row' => '[42]', 'list row' => '[["One","Body"]]', 'empty row' => '[{}]'];
        foreach (['missing content' => ['name' => 'Incomplete'], 'creator' => [...$valid, 'user_id' => 1],
            'identity' => [...$valid, 'id' => 1], 'archive' => [...$valid, 'deleted_at' => null],
            'lab' => [...$valid, 'lab_id' => 1], 'invalid blocks' => [...$valid, 'layout_schema' => ['canvas_blocks' => ['bad']]],
            'unknown layout' => [...$valid, 'layout_schema' => ['internal' => true]], 'invalid boolean' => [...$valid, 'is_active' => 'maybe']] as $name => $invalid) {
            $cases[$name] = json_encode([$valid, $invalid], JSON_THROW_ON_ERROR);
        }
        $cases['duplicate name'] = json_encode([$valid, $valid], JSON_THROW_ON_ERROR);
        $cases['too many'] = json_encode(array_map(fn (int $index): array => ['name' => 'Row '.$index, 'content' => '<p>Body</p>'], range(1, 101)), JSON_THROW_ON_ERROR);

        return array_map(fn (string $document): array => [$document], $cases);
    }

    #[DataProvider('formats')]
    public function test_partially_filled_spreadsheet_or_json_rows_are_not_silently_skipped(string $format): void
    {
        [$actor] = $this->fixture();
        $before = $this->snapshot();
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => $this->file([
            ['name' => 'Valid import', 'content' => '<p>Valid</p>'], ['name' => 'Missing body'],
        ], $format)])->assertUnprocessable()->assertJsonPath('success', false);
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('badJsonColumns')]
    public function test_invalid_spreadsheet_json_columns_are_not_silently_erased(string $value): void
    {
        [$actor] = $this->fixture();
        $before = $this->snapshot();
        $csv = "name,content,layout_schema_json\nImport,Body,".str_replace('"', '""', $value)."\n";
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => UploadedFile::fake()->createWithContent('models.csv', $csv)])
            ->assertUnprocessable()->assertJsonPath('success', false);
        $this->assertSame($before, $this->snapshot());
    }

    public static function badJsonColumns(): array
    {
        return [['{'], ['42'], ['"bad"']];
    }

    #[DataProvider('spreadsheetFormats')]
    public function test_spreadsheet_row_budget_rejects_the_whole_file(string $format): void
    {
        [$actor] = $this->fixture();
        $before = $this->snapshot();
        $rows = array_map(fn (int $index): array => ['name' => 'Row '.$index, 'content' => '<p>Body</p>'], range(1, 101));
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => $this->file($rows, $format)])
            ->assertUnprocessable()->assertJsonValidationErrors('template_file');
        $this->assertSame($before, $this->snapshot());
    }

    public static function spreadsheetFormats(): array
    {
        return [['csv'], ['xlsx']];
    }

    #[DataProvider('spreadsheetBooleans')]
    public function test_textual_spreadsheet_booleans_have_explicit_meaning(string $input, ?bool $active): void
    {
        [$actor] = $this->fixture();
        $csv = "name,content,is_active\nBoolean import,Body,".$input."\n";
        $before = $this->snapshot();
        $this->actingAs($actor);
        $response = $this->postJson(route('vap-proposals.templates.import'), ['template_file' => UploadedFile::fake()->createWithContent('models.csv', $csv)]);
        if ($active === null) {
            $response->assertUnprocessable()->assertJsonValidationErrors('rows.0.is_active');
            $this->assertSame($before, $this->snapshot());
        } else {
            $response->assertOk();
            $this->assertSame($active, VAPProposalTemplate::where('name', 'Boolean import')->sole()->is_active);
        }
    }

    public static function spreadsheetBooleans(): array
    {
        return [['true', true], ['FALSE', false], ['yes', true], ['no', false], ['on', true], ['off', false], ['', true], ['maybe', null]];
    }

    public function test_whitespace_only_spreadsheet_cells_use_canonical_defaults(): void
    {
        [$actor] = $this->fixture();
        $csv = "name,content,is_active,layout_schema_json,export_settings_json\nWhitespace import,Body,   ,   ,   \n";
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => UploadedFile::fake()->createWithContent('models.csv', $csv)])
            ->assertOk();
        $stored = VAPProposalTemplate::where('name', 'Whitespace import')->sole();
        $this->assertTrue($stored->is_active);
        $this->assertSame([], $stored->layout_schema);
        $this->assertSame([], $stored->export_settings);
    }

    public function test_unexpected_reader_failures_are_not_reported_as_invalid_user_files(): void
    {
        [$actor] = $this->fixture();
        $before = $this->snapshot();
        Excel::shouldReceive('toCollection')->once()->andThrow(new \RuntimeException('Unexpected reader failure'));
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => UploadedFile::fake()->createWithContent('models.csv', "name,content\nImport,Body\n")])
            ->assertStatus(500);
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('zipBudgets')]
    public function test_xlsx_archive_limits_are_checked_before_spreadsheet_loading(string $budget): void
    {
        $file = UploadedFile::fake()->createWithContent('models.xlsx', '');
        $archive = new ZipArchive;
        $this->assertTrue($archive->open($file->path(), ZipArchive::CREATE | ZipArchive::OVERWRITE));
        if ($budget === 'expanded') {
            $archive->addFromString('large.xml', str_repeat('x', 20 * 1024 * 1024 + 1));
        } else {
            for ($index = 0; $index < 513; $index++) {
                $archive->addFromString('part-'.$index.'.xml', 'x');
            }
        }
        $archive->close();
        try {
            app(ProposalTemplateImportFileReader::class)->read($file);
            $this->fail('Expected archive budget rejection before parsing.');
        } catch (ValidationException $exception) {
            $this->assertSame(['template_file'], array_keys($exception->errors()));
            $this->assertStringContainsString($budget === 'expanded' ? 'descomprimido' : 'estrutura', $exception->errors()['template_file'][0]);
        }
    }

    public static function zipBudgets(): array
    {
        return [['expanded'], ['entries']];
    }

    public function test_upload_authority_fields_and_unsupported_extensions_are_rejected(): void
    {
        [$actor] = $this->fixture();
        $before = $this->snapshot();
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => $this->file([['name' => 'Upload', 'content' => '<p>Body</p>']], 'json'), 'user_id' => $actor->id])
            ->assertUnprocessable()->assertJsonValidationErrors('user_id');
        $this->postJson(route('vap-proposals.templates.import'), ['template_file' => UploadedFile::fake()->createWithContent('models.exe', '[{"name":"Upload","content":"Body"}]')])
            ->assertUnprocessable()->assertJsonValidationErrors('template_file');
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('batchFaults')]
    public function test_later_row_faults_roll_back_earlier_roots_and_prior_or_new_history(string $fault): void
    {
        [$actor, $first] = $this->fixture();
        $second = VAPProposalTemplate::create(['name' => 'Second import', 'content' => '<p>Retained second</p>', 'user_id' => $first->user_id]);
        $before = $this->snapshot();
        if ($fault === 'second_save_veto') {
            VAPProposalTemplate::saving(fn (VAPProposalTemplate $record): ?bool => $record->id === $second->id ? false : null);
        } else {
            ISOActivityLog::creating(function (ISOActivityLog $audit) use ($fault, $actor, $first, $second): void {
                if ($audit->properties->get('origin') !== 'import') {
                    return;
                }
                if ($fault === 'pending_root' && (int) $audit->subject_id === $first->id) {
                    DB::table('proposal_templates')->where('id', $second->id)->update(['user_id' => $actor->id]);

                    return;
                }
                if ((int) $audit->subject_id !== $second->id) {
                    return;
                }
                if ($fault === 'earlier_root') {
                    DB::table('proposal_templates')->where('id', $first->id)->update(['content' => 'FORGED']);
                } elseif ($fault === 'earlier_delete') {
                    DB::table('proposal_templates')->where('id', $first->id)->delete();
                } elseif ($fault === 'earlier_created_at') {
                    DB::table('proposal_templates')->where('id', $first->id)->update(['created_at' => '2001-01-01']);
                } elseif ($fault === 'prior_history_delete' || $fault === 'prior_history_change') {
                    $query = ISOActivityLog::withoutGlobalScopes()->where('subject_id', $first->id)->where('event', 'fixture_history');
                    $fault === 'prior_history_delete' ? $query->delete() : $query->update(['description' => 'FORGED']);
                } elseif ($fault === 'new_history_delete' || $fault === 'new_history_change') {
                    $query = ISOActivityLog::withoutGlobalScopes()->where('subject_id', $first->id)->where('event', 'updated');
                    $fault === 'new_history_delete' ? $query->delete() : $query->update(['description' => 'FORGED']);
                } elseif ($fault === 'history_inject') {
                    activity()->performedOn($first)->causedBy($actor)->event('injected')->log('FORGED');
                } elseif ($fault === 'actor_permission') {
                    $actor->syncPermissions([]);
                } elseif ($fault === 'actor_active') {
                    User::whereKey($actor->id)->update(['is_active' => false]);
                } elseif ($fault === 'actor_verified') {
                    User::whereKey($actor->id)->update(['email_verified_at' => null]);
                } elseif ($fault === 'actor_archived') {
                    User::whereKey($actor->id)->update(['deleted_at' => now()]);
                }
            });
        }
        $rows = [['name' => $first->name, 'content' => '<p>First revision</p>'], ['name' => $second->name, 'content' => '<p>Second revision</p>']];
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => $this->file($rows, 'json')])
            ->assertStatus(str_starts_with($fault, 'actor_') ? 403 : 409);
        $this->assertSame($before, $this->snapshot());
    }

    public static function batchFaults(): array
    {
        return array_map(fn (string $fault): array => [$fault], ['second_save_veto', 'earlier_root', 'earlier_delete', 'earlier_created_at',
            'prior_history_delete', 'prior_history_change', 'new_history_delete', 'new_history_change', 'history_inject', 'pending_root',
            'actor_permission', 'actor_active', 'actor_verified', 'actor_archived']);
    }

    public function test_new_roots_are_rolled_back_when_a_later_save_is_vetoed(): void
    {
        [$actor] = $this->fixture();
        $before = $this->snapshot();
        VAPProposalTemplate::saving(fn (VAPProposalTemplate $record): ?bool => $record->name === 'Second new' ? false : null);
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => $this->file([
            ['name' => 'First new', 'content' => '<p>First</p>'], ['name' => 'Second new', 'content' => '<p>Second</p>'],
        ], 'json')])->assertStatus(409);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_unchanged_rows_still_receive_batch_preservation_checks(): void
    {
        [$actor,$first] = $this->fixture();
        $first->update(['category' => 'general', 'description' => null, 'theme_preset' => null, 'is_active' => true, 'layout_schema' => [], 'export_settings' => []]);
        $before = $this->snapshot();
        ISOActivityLog::creating(function (ISOActivityLog $audit) use ($first): void {
            if ($audit->properties->get('origin') === 'import') {
                DB::table('proposal_templates')->where('id', $first->id)->update(['content' => 'FORGED']);
            }
        });
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => $this->file([
            ['name' => $first->name, 'content' => $first->content], ['name' => 'Second new', 'content' => '<p>Second</p>'],
        ], 'json')])->assertStatus(409);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_archived_name_semantics_stay_unchanged_and_ambiguous_live_names_fail_closed(): void
    {
        [$actor,$template] = $this->fixture();
        $template->delete();
        $before = $template->fresh()->getAttributes();
        app(ImportProposalTemplates::class)->execute($actor->id, [['name' => $template->name, 'content' => '<p>New live</p>']]);
        $this->assertSame($before, $template->fresh()->getAttributes());
        $live = VAPProposalTemplate::where('name', $template->name)->sole();
        $this->assertNotSame($template->id, $live->id);
        VAPProposalTemplate::create(['name' => $live->name, 'content' => '<p>Duplicate</p>', 'user_id' => $actor->id]);
        $before = $this->snapshot();
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => $this->file([
            ['name' => 'Other new', 'content' => '<p>Other</p>'], ['name' => $live->name, 'content' => '<p>Ambiguous</p>'],
        ], 'json')])->assertUnprocessable();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_spreadsheet_exports_keep_formula_like_content_as_literal_text(): void
    {
        [$actor,$template] = $this->fixture();
        $template->update(['content' => '=HYPERLINK("https://example.invalid","Body")']);
        $binary = Excel::raw(new ProposalTemplatesExport, \Maatwebsite\Excel\Excel::XLSX);
        $file = UploadedFile::fake()->createWithContent('models.xlsx', $binary);
        $sheet = IOFactory::load($file->path())->getActiveSheet();
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('F2')->getDataType());
        $this->assertSame($template->content, $sheet->getCell('F2')->getValue());
        $this->actingAs($actor)->postJson(route('vap-proposals.templates.import'), ['template_file' => $file])->assertOk();
        $this->assertSame($template->content, $template->fresh()->content);
    }

    public function test_import_only_authority_is_required_for_internal_calls(): void
    {
        [$actor] = $this->fixture();
        $actor->syncPermissions([Permission::findOrCreate('add_proposal_templates', 'web'), Permission::findOrCreate('edit_proposal_templates', 'web')]);
        $before = $this->snapshot();
        try {
            app(ImportProposalTemplates::class)->execute($actor->id, [['name' => 'Unauthorized import', 'content' => '<p>Body</p>']]);
            $this->fail('Import permission is distinct from add/edit.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame($before, $this->snapshot());
    }

    /** @return array{User,VAPProposalTemplate} */
    private function fixture(): array
    {
        $actor = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        $actor->givePermissionTo(Permission::findOrCreate('import_proposal_templates', 'web'));
        $creator = User::factory()->create();
        $template = VAPProposalTemplate::create(['name' => 'Import fixture '.str()->uuid(), 'content' => '<p>Retained</p>', 'user_id' => $creator->id]);
        activity()->performedOn($template)->causedBy($creator)->event('fixture_history')->log('Retained history');
        activity()->performedOn($template)->causedBy($creator)->event('fixture_history')->log('Retained alias');
        ISOActivityLog::withoutGlobalScopes()->latest('id')->firstOrFail()->update(['subject_type' => ProposalTemplate::class]);

        return [$actor, $template];
    }

    /** @param list<array<string,mixed>> $rows */
    private function file(array $rows, string $format): UploadedFile
    {
        if ($format === 'json') {
            return UploadedFile::fake()->createWithContent('models.json', json_encode($rows, JSON_THROW_ON_ERROR));
        }
        $headers = ['name', 'category', 'description', 'theme_preset', 'is_active', 'content', 'layout_schema_json', 'export_settings_json', 'created_by', 'created_at', 'updated_at'];
        $data = array_map(function (array $row) use ($headers): array {
            $result = [];
            foreach ($headers as $header) {
                if ($header === 'layout_schema_json' || $header === 'export_settings_json') {
                    $result[] = json_encode($row[str_replace('_json', '', $header)] ?? [], JSON_THROW_ON_ERROR);
                } elseif ($header === 'is_active') {
                    $result[] = array_key_exists($header, $row) ? ($row[$header] ? '1' : '0') : null;
                } else {
                    $result[] = $row[$header] ?? null;
                }
            }

            return $result;
        }, $rows);
        $export = new class($data, $headers) implements FromArray, WithHeadings
        {
            public function __construct(private array $rows, private array $headers) {}

            public function array(): array
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return $this->headers;
            }
        };
        $binary = Excel::raw($export, $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX);

        return UploadedFile::fake()->createWithContent('models.'.$format, $binary);
    }

    /** @return array<string,list<array<string,mixed>>> */
    private function snapshot(): array
    {
        return ['templates' => VAPProposalTemplate::withTrashed()->orderBy('id')->get()->map->getAttributes()->all(),
            'history' => ISOActivityLog::withoutGlobalScopes()->orderBy('id')->get()->map->getAttributes()->all(),
            'users' => User::withTrashed()->orderBy('id')->get()->map->getAttributes()->all(),
            'permissions' => DB::table('model_has_permissions')->orderBy('model_id')->orderBy('permission_id')->get()->map(fn (object $row): array => (array) $row)->all()];
    }
}
