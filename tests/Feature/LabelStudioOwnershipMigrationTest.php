<?php

namespace Tests\Feature;

use App\Models\VAPLab;
use App\Models\VAPLabel;
use App\Models\VAPLabelTemplate;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class LabelStudioOwnershipMigrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_database_requires_an_owner_for_labels_and_custom_templates(): void
    {
        try {
            DB::transaction(fn () => DB::table('labels')->insert([
                'name' => 'Unassigned label', 'content' => 'Private', 'width' => 50, 'height' => 25,
            ]));
            $this->fail('Expected an unassigned label to be rejected.');
        } catch (QueryException $exception) {
            $this->assertSame('23502', $exception->getCode());
        }

        try {
            DB::transaction(fn () => DB::table('label_templates')->insert([
                'name' => 'Unassigned template', 'category' => 'samples', 'template_data' => '{}',
            ]));
            $this->fail('Expected an unassigned custom template to be rejected.');
        } catch (QueryException $exception) {
            $this->assertSame('23514', $exception->getCode());
        }

        $lab = VAPLab::factory()->create();
        try {
            DB::transaction(fn () => DB::table('label_templates')->insert([
                'name' => 'Misowned system preset', 'category' => 'samples', 'template_data' => '{}',
                'lab_id' => $lab->id, 'is_system' => true,
            ]));
            $this->fail('Expected a system preset with an owner to be rejected.');
        } catch (QueryException $exception) {
            $this->assertSame('23514', $exception->getCode());
        }
    }

    public function test_retained_label_data_prevents_removal_of_laboratory_ownership(): void
    {
        $lab = VAPLab::factory()->create();
        VAPLabel::query()->create([
            'lab_id' => $lab->id, 'name' => 'Retained label', 'content' => 'Private',
            'width' => 50, 'height' => 25,
        ]);
        VAPLabelTemplate::query()->create([
            'lab_id' => $lab->id, 'name' => 'Retained template', 'category' => 'samples',
            'template_data' => ['content' => '{name}'],
        ]);
        $migration = require database_path('migrations/2026_09_30_095206_add_laboratory_ownership_to_label_studio_tables.php');

        try {
            $migration->down();
            $this->fail('Expected rollback to preserve retained Label Studio ownership.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cannot remove laboratory ownership while labels or custom templates are retained.', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('label_templates', 'lab_id'));
        $this->assertTrue(Schema::hasColumn('label_templates', 'is_system'));
    }

    public function test_empty_schema_can_roll_back_and_reapply_without_guessing_retained_owners(): void
    {
        $this->assertDatabaseCount('labels', 0);
        $this->assertDatabaseCount('label_templates', 0);
        $migration = require database_path('migrations/2026_09_30_095206_add_laboratory_ownership_to_label_studio_tables.php');

        $migration->down();
        $this->assertFalse(Schema::hasColumn('label_templates', 'lab_id'));
        $this->assertTrue(Schema::hasColumn('labels', 'lab_id'));

        DB::table('labels')->insert(['name' => 'Existing unassigned label', 'content' => 'Private', 'width' => 50, 'height' => 25]);
        try {
            $migration->up();
            $this->fail('Expected migration to reject an unassigned retained label.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Assign retained labels and custom templates to laboratories before enforcing Label Studio ownership.', $exception->getMessage());
        }

        DB::table('labels')->delete();
        $migration->up();
        $this->assertTrue(Schema::hasColumn('label_templates', 'lab_id'));
        $this->assertTrue(Schema::hasColumn('label_templates', 'is_system'));
    }
}
