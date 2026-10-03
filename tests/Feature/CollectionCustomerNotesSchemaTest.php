<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class CollectionCustomerNotesSchemaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_notes_are_nullable_text_and_preserve_full_unicode_values(): void
    {
        $this->assertSame('text', Schema::getColumnType('collection_product', 'customer_submitted_info'));
        $notes = str_repeat('é', 2000);
        $id = DB::table('collection_product')->insertGetId(['customer_submitted_info' => $notes]);
        $empty = DB::table('collection_product')->insertGetId(['customer_submitted_info' => null]);
        $migration = require database_path('migrations/2026_09_28_104148_widen_collection_customer_notes.php');
        $migration->up();
        $migration->up();
        $this->assertSame($notes, DB::table('collection_product')->where('id', $id)->value('customer_submitted_info'));
        $this->assertNull(DB::table('collection_product')->where('id', $empty)->value('customer_submitted_info'));
    }

    public function test_upgrade_and_safe_rollback_preserve_actual_short_values(): void
    {
        $notes = 'Condição original da amostra';
        $id = DB::table('collection_product')->insertGetId(['customer_submitted_info' => $notes]);
        Schema::table('collection_product', fn (Blueprint $table) => $table->string('customer_submitted_info')->nullable()->change());
        $migration = require database_path('migrations/2026_09_28_104148_widen_collection_customer_notes.php');
        $migration->up();
        $this->assertSame('text', Schema::getColumnType('collection_product', 'customer_submitted_info'));
        $migration->down();
        $this->assertSame('varchar', Schema::getColumnType('collection_product', 'customer_submitted_info'));
        $this->assertSame($notes, DB::table('collection_product')->where('id', $id)->value('customer_submitted_info'));
    }

    public function test_rollback_refuses_to_shorten_existing_notes(): void
    {
        $notes = str_repeat('á', 256);
        $id = DB::table('collection_product')->insertGetId(['customer_submitted_info' => $notes]);
        $migration = require database_path('migrations/2026_09_28_104148_widen_collection_customer_notes.php');

        try {
            $migration->down();
            $this->fail('Rollback must not truncate retained sample notes.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cannot narrow collection customer notes while retained values exceed 255 characters.', $exception->getMessage());
        }

        $this->assertSame('text', Schema::getColumnType('collection_product', 'customer_submitted_info'));
        $this->assertSame($notes, DB::table('collection_product')->where('id', $id)->value('customer_submitted_info'));
    }
}
