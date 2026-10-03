<?php

namespace Tests\Feature;

use App\Models\BroadcastNotification;
use App\Models\VAPLab;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class BroadcastNotificationOwnershipMigrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_broadcast_log_requires_a_valid_laboratory_owner(): void
    {
        try {
            DB::transaction(fn () => DB::table('broadcast_notifications')->insert([
                'title' => 'Unowned', 'message' => 'Must fail', 'recipient_type' => 'all',
            ]));
            $this->fail('Expected an unowned broadcast to be rejected.');
        } catch (QueryException $exception) {
            $this->assertSame('23502', $exception->getCode());
        }

        try {
            DB::transaction(fn () => DB::table('broadcast_notifications')->insert([
                'lab_id' => -1, 'title' => 'Foreign key', 'message' => 'Must fail', 'recipient_type' => 'all',
            ]));
            $this->fail('Expected an invalid laboratory to be rejected.');
        } catch (QueryException $exception) {
            $this->assertSame('23503', $exception->getCode());
        }
    }

    public function test_retained_broadcasts_prevent_removal_of_laboratory_ownership(): void
    {
        $lab = VAPLab::factory()->create();
        BroadcastNotification::query()->create([
            'lab_id' => $lab->id,
            'title' => 'Retained notice',
            'message' => 'Private',
            'recipient_type' => 'all',
        ]);
        $migration = require database_path('migrations/2026_10_01_092849_add_lab_ownership_to_broadcast_notifications.php');

        try {
            $migration->down();
            $this->fail('Expected rollback to preserve retained ownership.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cannot remove laboratory ownership while broadcast notifications exist.', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('broadcast_notifications', 'lab_id'));
    }

    public function test_empty_log_can_roll_back_and_reapply_without_guessing_owners(): void
    {
        $this->assertDatabaseCount('broadcast_notifications', 0);
        $migration = require database_path('migrations/2026_10_01_092849_add_lab_ownership_to_broadcast_notifications.php');

        $migration->down();
        $this->assertFalse(Schema::hasColumn('broadcast_notifications', 'lab_id'));

        DB::table('broadcast_notifications')->insert([
            'title' => 'Unassigned retained notice', 'message' => 'Private', 'recipient_type' => 'all',
        ]);
        try {
            $migration->up();
            $this->fail('Expected a retained unassigned broadcast to block migration.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Assign an owning laboratory to retained broadcast notifications before migrating.', $exception->getMessage());
        }

        DB::table('broadcast_notifications')->delete();
        $migration->up();
        $this->assertTrue(Schema::hasColumn('broadcast_notifications', 'lab_id'));
    }
}
