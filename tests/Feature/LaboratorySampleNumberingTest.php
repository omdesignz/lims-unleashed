<?php

namespace Tests\Feature;

use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class LaboratorySampleNumberingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_creating_sample_issues_code_before_first_insert(): void
    {
        $this->travelTo(now()->setDate(2090, 1, 1));
        $sample = VAPSampleEntry::factory()->create(['code' => null]);
        $this->assertSame('2090', $sample->sample_year);
        $this->assertSame(1, $sample->seq);
        $this->assertSame('SMP-2090-L'.$sample->lab_id.'-AGU-00001', $sample->fresh()->code);
        $this->assertSame($sample->code, $sample->generateCode());
        $sample->update(['name' => 'Updated sample']);
        $this->assertSame(1, $sample->fresh()->seq);
    }

    public function test_numbering_is_separate_per_lab_and_year_but_shared_by_sample_types(): void
    {
        $firstLab = VAPLab::factory()->create();
        $secondLab = VAPLab::factory()->create();
        $first = VAPSampleEntry::factory()->create(['code' => null, 'lab_id' => $firstLab->id, 'sample_year' => '2090']);
        $second = VAPSampleEntry::factory()->create(['code' => null, 'lab_id' => $firstLab->id, 'sample_year' => '2090', 'sample_type' => 'SOLO']);
        $otherLab = VAPSampleEntry::factory()->create(['code' => null, 'lab_id' => $secondLab->id, 'sample_year' => '2090']);
        $nextYear = VAPSampleEntry::factory()->create(['code' => null, 'lab_id' => $firstLab->id, 'sample_year' => '2091']);
        $this->assertSame([1, 2, 1, 1], [$first->seq, $second->seq, $otherLab->seq, $nextYear->seq]);
        $this->assertSame('SMP-2090-L'.$firstLab->id.'-SOL-00002', $second->code);
        $this->assertNotSame($first->code, $otherLab->code);
    }

    public function test_deleted_samples_and_preexisting_numbers_are_never_reused(): void
    {
        $lab = VAPLab::factory()->create();
        DB::table('sample_entries')->insert([
            'name' => 'Legacy sample', 'lab_id' => $lab->id, 'sample_year' => '2090', 'seq' => 49,
            'code' => 'Legacy-49', 'deleted_at' => now(),
        ]);
        $sample = VAPSampleEntry::factory()->create(['code' => null, 'lab_id' => $lab->id, 'sample_year' => '2090']);
        $this->assertSame(50, $sample->seq);
        $sample->forceDelete();
        $this->assertSame(51, VAPSampleEntry::factory()->create(['code' => null, 'lab_id' => $lab->id, 'sample_year' => '2090'])->seq);
    }

    public function test_manual_codes_remain_unchanged_and_auto_codes_skip_occupied_codes(): void
    {
        $lab = VAPLab::factory()->create();
        $manual = VAPSampleEntry::factory()->create([
            'code' => 'SMP-2090-L'.$lab->id.'-AGU-00001',
            'lab_id' => $lab->id,
            'sample_year' => '2090',
        ]);
        $this->assertNull($manual->seq);
        $manual->delete();
        $sample = VAPSampleEntry::factory()->create(['code' => null, 'lab_id' => $lab->id, 'sample_year' => '2090']);
        $this->assertSame('SMP-2090-L'.$lab->id.'-AGU-00002', $sample->code);
        $this->assertSame(2, $sample->seq);
    }

    public function test_explicit_numbers_advance_counter_and_duplicate_numbers_are_rejected(): void
    {
        $lab = VAPLab::factory()->create();
        $sample = VAPSampleEntry::factory()->create(['code' => null, 'lab_id' => $lab->id, 'sample_year' => '2090', 'seq' => 40]);
        $this->assertSame(40, $sample->seq);
        $this->assertSame(41, VAPSampleEntry::factory()->create(['code' => null, 'lab_id' => $lab->id, 'sample_year' => '2090'])->seq);
        $this->expectException(LogicException::class);
        VAPSampleEntry::factory()->create(['code' => null, 'lab_id' => $lab->id, 'sample_year' => '2090', 'seq' => 40]);
    }

    public function test_issued_sequence_cannot_be_changed(): void
    {
        $sample = VAPSampleEntry::factory()->create(['code' => null, 'sample_year' => '2090']);
        $this->expectException(LogicException::class);
        $sample->update(['seq' => 20]);
    }

    public function test_issued_year_cannot_be_changed(): void
    {
        $sample = VAPSampleEntry::factory()->create(['code' => null, 'sample_year' => '2090']);
        $this->expectException(LogicException::class);
        $sample->update(['sample_year' => '2091']);
    }

    public function test_issued_sample_cannot_be_moved_to_another_lab(): void
    {
        $sample = VAPSampleEntry::factory()->create(['code' => null, 'sample_year' => '2090']);
        $otherLab = VAPLab::factory()->create();

        $this->expectException(LogicException::class);
        $sample->update(['lab_id' => $otherLab->id]);
    }

    public function test_new_sample_requires_a_laboratory_even_with_a_manual_code(): void
    {
        $this->expectException(LogicException::class);
        VAPSampleEntry::query()->create([
            'name' => 'Unowned sample',
            'code' => 'MANUAL-UNOWNED',
            'sample_year' => '2090',
        ]);
    }

    public function test_issued_code_cannot_be_cleared(): void
    {
        $sample = VAPSampleEntry::factory()->create(['code' => null, 'sample_year' => '2090']);
        $this->expectException(LogicException::class);
        $sample->update(['code' => null]);
    }

    public function test_issued_code_cannot_be_rewritten(): void
    {
        $sample = VAPSampleEntry::factory()->create(['code' => null, 'sample_year' => '2090']);
        $this->expectException(LogicException::class);
        $sample->update(['code' => 'REWRITTEN-ISSUED-CODE']);
    }

    public function test_rolled_back_intake_does_not_leave_counter_or_sample_rows(): void
    {
        $lab = VAPLab::factory()->create();
        DB::beginTransaction();
        $sample = VAPSampleEntry::factory()->create(['code' => null, 'sample_year' => '2090', 'lab_id' => $lab->id]);
        DB::rollBack();
        $this->assertModelMissing($sample);
        $retry = VAPSampleEntry::factory()->create(['code' => null, 'sample_year' => '2090', 'lab_id' => $lab->id]);
        $this->assertSame(1, $retry->seq);
    }

    public function test_generating_before_save_reserves_only_one_number(): void
    {
        $lab = VAPLab::factory()->create();
        $sample = VAPSampleEntry::factory()->make(['code' => null, 'lab_id' => $lab->id, 'sample_year' => '2090']);
        $code = $sample->generateCode();
        $this->assertSame($code, $sample->generateCode());
        $sample->save();
        $this->assertSame(1, $sample->seq);
        $this->assertSame(2, VAPSampleEntry::factory()->create(['code' => null, 'lab_id' => $lab->id, 'sample_year' => '2090'])->seq);
    }

    public function test_existing_legacy_sample_can_receive_missing_code_without_reassigning_its_number(): void
    {
        $id = DB::table('sample_entries')->insertGetId(['name' => 'Legacy sample', 'sample_year' => '2090', 'seq' => 42]);
        $sample = VAPSampleEntry::findOrFail($id);
        $this->assertSame('SMP-2090-GEN-00042', $sample->generateCode());
        $sample->save();
        $this->assertSame(42, $sample->fresh()->seq);
    }

    public function test_legacy_number_without_year_requires_explicit_repair(): void
    {
        $id = DB::table('sample_entries')->insertGetId(['name' => 'Legacy sample', 'seq' => 42]);
        $this->expectException(LogicException::class);
        VAPSampleEntry::findOrFail($id)->generateCode();
    }

    public function test_numbering_scope_migration_preserves_legacy_codes_and_numbers(): void
    {
        $migration = require database_path('migrations/2026_09_27_201927_scope_sample_entry_sequences_to_laboratory_and_year.php');
        $migration->down();
        $lab = VAPLab::factory()->create();
        $legacyId = DB::table('sample_entries')->insertGetId([
            'name' => 'Legacy annual sample',
            'lab_id' => $lab->id,
            'sample_year' => '2090',
            'seq' => 49,
            'code' => 'SMP-2090-AGU-00049',
        ]);

        $migration->up();
        $migration->up();
        $legacy = VAPSampleEntry::findOrFail($legacyId);
        $this->assertSame('SMP-2090-AGU-00049', $legacy->code);
        $this->assertSame(49, $legacy->seq);
        $next = VAPSampleEntry::factory()->create(['lab_id' => $lab->id, 'sample_year' => '2090', 'code' => null]);
        $this->assertSame(50, $next->seq);

        $migration->down();
        $this->assertSame('SMP-2090-AGU-00049', $legacy->fresh()->code);
        $this->assertTrue(Schema::hasIndex('sample_entries', 'sample_entries_sample_year_seq_unique'));
        $migration->up();
    }

    public function test_numbering_scope_rollback_refuses_to_renumber_issued_samples(): void
    {
        $first = VAPSampleEntry::factory()->create(['code' => null, 'sample_year' => '2090']);
        $second = VAPSampleEntry::factory()->create(['code' => null, 'sample_year' => '2090']);
        $this->assertSame([1, 1], [$first->seq, $second->seq]);
        $migration = require database_path('migrations/2026_09_27_201927_scope_sample_entry_sequences_to_laboratory_and_year.php');

        try {
            $migration->down();
            $this->fail('Rollback must refuse overlapping numbers rather than changing issued identifiers.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('overlapping numbers', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasIndex('sample_entries', 'sample_entries_lab_year_seq_unique'));
        $this->assertSame($first->code, $first->fresh()->code);
        $this->assertSame($second->code, $second->fresh()->code);
    }
}
