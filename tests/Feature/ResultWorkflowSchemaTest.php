<?php

namespace Tests\Feature;

use App\Models\Result;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResultWorkflowSchemaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_result_fillable_fields_have_postgresql_columns_and_preserve_calculation_literals(): void
    {
        foreach ((new Result)->getFillable() as $column) {
            $this->assertTrue(Schema::hasColumn('results', $column), $column);
        }
        $result = Result::query()->create(['inserted_value' => '1.5', 'insertion_notes' => 'Original observation',
            'verification_notes' => 'Peer review', 'approval_notes' => 'Release note', 'verification_status' => 'verified',
            'is_calculated' => true, 'is_override' => false, 'sumC' => '0.00000000000000000001',
            'calculation_metadata' => ['inputs' => ['mass' => '1e-20']], 'calculated_at' => now(), 'insertion_method' => 'batch']);
        $stored = $result->fresh();
        $this->assertSame('0.00000000000000000001', $stored->sumC);
        $this->assertSame(['inputs' => ['mass' => '1e-20']], $stored->calculation_metadata);
        $this->assertTrue($stored->is_calculated);
        $this->assertFalse($stored->is_override);
        $this->assertSame('Release note', $stored->approval_notes);
    }

    public function test_repair_adds_missing_fields_without_overwriting_legacy_evidence_and_rollback_retains_it(): void
    {
        $result = Result::query()->create(['inserted_value' => '2.25', 'approval_notes' => 'Legacy approval', 'is_calculated' => true]);
        Schema::table('results', fn ($table) => $table->dropColumn(['calculation_metadata', 'calculated_at', 'cfu2']));
        $migration = require database_path('migrations/2026_09_27_220247_add_missing_result_workflow_fields.php');
        $migration->up();
        $migration->up();
        $migration->down();
        $stored = DB::table('results')->find($result->id);
        $this->assertSame('Legacy approval', $stored->approval_notes);
        $this->assertSame('2.25', $stored->inserted_value);
        $this->assertTrue($stored->is_calculated);
        $this->assertTrue(Schema::hasColumns('results', ['calculation_metadata', 'calculated_at', 'cfu2']));
    }
}
