<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class TagLaboratoryOwnershipMigrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_retained_tags_prevent_removal_of_laboratory_ownership(): void
    {
        $lab = VAPLab::factory()->create();
        Tag::query()->create(['lab_id' => $lab->id, 'name' => 'Controlled']);
        $migration = require database_path('migrations/2026_09_30_072229_add_laboratory_owner_to_tags_table.php');

        try {
            $migration->down();
            $this->fail('Expected rollback to preserve retained tag ownership.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cannot remove laboratory ownership from retained document tags.', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('tags', 'lab_id'));
    }

    public function test_empty_tag_schema_can_roll_back_and_reapply_laboratory_ownership(): void
    {
        $this->assertDatabaseCount('tags', 0);
        $migration = require database_path('migrations/2026_09_30_072229_add_laboratory_owner_to_tags_table.php');

        $migration->down();
        $this->assertFalse(Schema::hasColumn('tags', 'lab_id'));

        $migration->up();
        $this->assertTrue(Schema::hasColumn('tags', 'lab_id'));
    }

    public function test_retained_unowned_tags_prevent_automatic_laboratory_assignment(): void
    {
        $this->assertDatabaseCount('tags', 0);
        $migration = require database_path('migrations/2026_09_30_072229_add_laboratory_owner_to_tags_table.php');
        $migration->down();
        Tag::query()->create(['name' => 'Unassigned']);

        try {
            $migration->up();
            $this->fail('Expected migration to reject unassigned retained tags.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Assign retained document tags to laboratories before adding ownership.', $exception->getMessage());
        }

        $this->assertFalse(Schema::hasColumn('tags', 'lab_id'));
    }
}
