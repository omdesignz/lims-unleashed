<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $this->assertEmptyQuotes();
            Schema::table('quotes', function (Blueprint $table): void {
                $table->boolean('is_service')->default(false);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            $this->assertEmptyQuotes();
            Schema::table('quotes', fn (Blueprint $table) => $table->dropColumn('is_service'));
        });
    }

    private function assertEmptyQuotes(): void
    {
        DB::statement('LOCK TABLE quotes IN ACCESS EXCLUSIVE MODE');
        if (DB::table('quotes')->exists()) {
            throw new RuntimeException('Cannot change quote pricing-mode schema while retained quotes exist. Review their intended pricing mode explicitly.');
        }
    }
};
