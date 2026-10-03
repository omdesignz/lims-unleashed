<?php

namespace Tests\Unit;

use App\Services\ScopedSequenceAllocator;
use App\Traits\HasScopedSequence;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Facade;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

class ScopedSequenceAllocatorTest extends TestCase
{
    private Manager $database;

    private mixed $previousFacadeApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = new Manager;
        $this->database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->database->setEventDispatcher(new Dispatcher($this->database->getContainer()));
        $this->database->setAsGlobal();
        $this->database->bootEloquent();
        Model::clearBootedModels();

        $this->previousFacadeApplication = Facade::getFacadeApplication();
        $this->database->getContainer()->instance('db.schema', $this->database->getConnection()->getSchemaBuilder());
        Facade::clearResolvedInstance('db.schema');
        Facade::setFacadeApplication($this->database->getContainer());

        $migration = require __DIR__.'/../../database/migrations/2026_09_26_205156_create_sequence_counters_table.php';
        $migration->up();

        $this->database->schema()->create('sequence_test_records', function (Blueprint $table): void {
            $table->id();
            $table->string('year')->nullable();
            $table->string('kind')->nullable();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('seq')->nullable();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        $this->database->getConnection()->disconnect();
        Facade::clearResolvedInstance('db.schema');
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Model::clearBootedModels();

        parent::tearDown();
    }

    public function test_allocates_independent_sequences_per_scope(): void
    {
        $first = SequenceTestRecord::create(['year' => '2026', 'kind' => 'sample']);
        $second = SequenceTestRecord::create(['year' => '2026', 'kind' => 'sample']);
        $otherYear = SequenceTestRecord::create(['year' => '2027', 'kind' => 'sample']);
        $otherKind = SequenceTestRecord::create(['year' => '2026', 'kind' => 'invoice']);

        $this->assertSame([1, 2, 1, 1], [$first->seq, $second->seq, $otherYear->seq, $otherKind->seq]);
    }

    public function test_does_not_renumber_or_reuse_deleted_numbers(): void
    {
        $first = SequenceTestRecord::create(['year' => '2026']);
        $second = SequenceTestRecord::create(['year' => '2026']);
        $first->delete();
        $this->assertSame(2, $second->fresh()->seq);
        $second->forceDelete();

        $this->assertSame(3, SequenceTestRecord::create(['year' => '2026'])->seq);
        $first->restore();
        $this->assertSame(1, $first->fresh()->seq);
    }

    public function test_bootstraps_above_existing_numbers_including_soft_deleted_records(): void
    {
        $this->database->table('sequence_test_records')->insert([
            'year' => '2026', 'seq' => 49, 'deleted_at' => '2026-01-01 00:00:00',
        ]);

        $this->assertSame(50, SequenceTestRecord::create(['year' => '2026'])->seq);
    }

    public function test_explicit_imported_number_advances_the_counter_without_rewriting_it(): void
    {
        $record = SequenceTestRecord::create(['year' => '2026', 'seq' => 100]);

        $this->assertSame(100, $record->seq);
        $this->assertSame(101, SequenceTestRecord::create(['year' => '2026', 'seq' => 0])->seq);
    }

    public function test_rejects_duplicate_explicit_numbers_even_after_soft_deletion(): void
    {
        SequenceTestRecord::create(['year' => '2026', 'seq' => 5])->delete();

        $this->expectException(LogicException::class);
        SequenceTestRecord::create(['year' => '2026', 'seq' => 5]);
    }

    public function test_rejects_invalid_numbers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SequenceTestRecord::create(['seq' => -1]);
    }

    public function test_rejects_scope_that_contains_the_sequence_field(): void
    {
        $this->expectException(LogicException::class);
        (new ScopedSequenceAllocator)->assign(new SequenceTestRecord, ['group' => ['year', 'seq']]);
    }

    public function test_prevents_editing_issued_numbers(): void
    {
        $record = SequenceTestRecord::create(['year' => '2026']);

        $this->expectException(LogicException::class);
        $record->update(['seq' => 10]);
    }

    public function test_prevents_moving_issued_numbers_to_another_scope(): void
    {
        $record = SequenceTestRecord::create(['year' => '2026']);

        $this->expectException(LogicException::class);
        $record->update(['year' => '2027']);
    }

    public function test_allows_normal_record_edits(): void
    {
        $record = SequenceTestRecord::create(['year' => '2026']);
        $record->update(['name' => 'Updated sample']);

        $this->assertSame(1, $record->fresh()->seq);
        $this->assertSame('Updated sample', $record->fresh()->name);
    }

    public function test_created_callback_can_save_non_identity_fields_without_renumbering(): void
    {
        $record = SequencePostCreationSaveRecord::create(['year' => '2026', 'kind' => 'guide']);

        $this->assertSame(1, $record->fresh()->seq);
        $this->assertSame('Document 1', $record->fresh()->name);
        $this->assertSame(1, $this->database->table('sequence_counters')->value('last_value'));
    }

    public function test_created_callback_cannot_replace_the_issued_number(): void
    {
        new SequencePostCreationSaveRecord;
        SequencePostCreationSaveRecord::created(function (SequencePostCreationSaveRecord $record): void {
            $record->update(['seq' => 10]);
        });

        try {
            SequencePostCreationSaveRecord::create(['year' => '2026', 'kind' => 'guide']);
            $this->fail('A created callback must not change its issued number.');
        } catch (LogicException) {
            $this->assertSame(1, (int) $this->database->table('sequence_test_records')->value('seq'));
        }
    }

    public function test_created_callback_cannot_replace_the_issued_scope(): void
    {
        new SequencePostCreationSaveRecord;
        SequencePostCreationSaveRecord::created(function (SequencePostCreationSaveRecord $record): void {
            $record->update(['year' => '2027']);
        });

        try {
            SequencePostCreationSaveRecord::create(['year' => '2026', 'kind' => 'guide']);
            $this->fail('A created callback must not change its issued scope.');
        } catch (LogicException) {
            $this->assertSame('2026', $this->database->table('sequence_test_records')->value('year'));
        }
    }

    public function test_models_sharing_a_table_share_the_same_counter(): void
    {
        $first = SequenceTestRecord::create(['year' => '2026']);
        $second = SequenceAliasTestRecord::create(['year' => '2026']);

        $this->assertSame([1, 2], [$first->seq, $second->seq]);
        $this->assertSame(1, $this->database->table('sequence_counters')->count());
    }

    public function test_null_scopes_are_distinct_from_empty_scopes(): void
    {
        $this->assertSame(1, SequenceTestRecord::create(['year' => null])->seq);
        $this->assertSame(1, SequenceTestRecord::create(['year' => ''])->seq);
        $this->assertSame(2, SequenceTestRecord::create(['year' => null])->seq);
    }

    public function test_outer_transaction_rolls_back_record_and_counter_together(): void
    {
        $connection = $this->database->getConnection();
        $connection->beginTransaction();
        SequenceTestRecord::create(['year' => '2026']);
        $connection->rollBack();

        $this->assertSame(0, $this->database->table('sequence_counters')->count());
        $this->assertSame(1, SequenceTestRecord::create(['year' => '2026'])->seq);
    }

    public function test_scope_order_and_numeric_types_do_not_create_separate_counters(): void
    {
        $allocator = new ScopedSequenceAllocator;
        $first = new SequenceTestRecord(['year' => 2026, 'kind' => 'sample']);
        $second = new SequenceTestRecord(['year' => '2026', 'kind' => 'sample']);
        $allocator->assign($first, ['group' => ['year', 'kind']]);
        $allocator->assign($second, ['group' => ['kind', 'year']]);

        $this->assertSame([1, 2], [$first->seq, $second->seq]);
    }

    public function test_sequence_order_scope_is_available(): void
    {
        SequenceTestRecord::create(['seq' => 1]);
        SequenceTestRecord::create(['seq' => 3]);

        $this->assertSame([1, 3], SequenceTestRecord::sequenced()->pluck('seq')->all());
        $this->assertSame([3, 1], SequenceTestRecord::sequenced('desc')->pluck('seq')->all());
    }

    public function test_an_explicit_number_cannot_reuse_an_uncommitted_reservation(): void
    {
        $allocator = new ScopedSequenceAllocator;
        $allocator->assign(new SequenceTestRecord(['seq' => 5]), ['group' => ['year', 'kind']]);

        $this->expectException(LogicException::class);
        $allocator->assign(new SequenceTestRecord(['seq' => 5]), ['group' => ['year', 'kind']]);
    }

    public function test_legacy_text_sequences_use_numeric_not_lexicographic_order(): void
    {
        $this->database->schema()->table('sequence_test_records', function (Blueprint $table): void {
            $table->string('legacy_seq')->nullable();
        });
        $this->database->table('sequence_test_records')->insert([
            ['legacy_seq' => '9'], ['legacy_seq' => '10'],
        ]);
        $record = new SequenceTestRecord;
        (new ScopedSequenceAllocator)->assign($record, ['fieldName' => 'legacy_seq']);

        $this->assertSame(11, $record->legacy_seq);
    }

    public function test_counter_migration_can_be_rolled_back(): void
    {
        $migration = require __DIR__.'/../../database/migrations/2026_09_26_205156_create_sequence_counters_table.php';
        $migration->down();

        $this->assertFalse($this->database->schema()->hasTable('sequence_counters'));
    }
}

class SequenceTestRecord extends Model
{
    use HasScopedSequence, SoftDeletes;

    public $timestamps = false;

    protected $table = 'sequence_test_records';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['seq' => 'integer'];
    }

    public function sequence(): array
    {
        return ['group' => ['year', 'kind'], 'fieldName' => 'seq'];
    }
}

class SequenceAliasTestRecord extends SequenceTestRecord {}

class SequencePostCreationSaveRecord extends SequenceTestRecord
{
    protected static function boot(): void
    {
        parent::boot();

        static::created(function (self $record): void {
            $record->update(['name' => 'Document '.$record->seq]);
        });
    }
}
