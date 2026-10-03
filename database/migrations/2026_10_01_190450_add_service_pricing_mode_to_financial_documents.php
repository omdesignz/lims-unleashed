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
            $this->assertEmptyDocuments();
            foreach (['invoices', 'credit_notes'] as $name) {
                Schema::table($name, fn (Blueprint $table) => $table->boolean('is_service')->default(false));
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            $this->assertEmptyDocuments();
            foreach (['invoices', 'credit_notes'] as $name) {
                Schema::table($name, fn (Blueprint $table) => $table->dropColumn('is_service'));
            }
        });
    }

    private function assertEmptyDocuments(): void
    {
        DB::statement('LOCK TABLE credit_notes, invoices IN ACCESS EXCLUSIVE MODE');
        if (DB::table('invoices')->exists() || DB::table('credit_notes')->exists()) {
            throw new RuntimeException('Cannot change financial pricing-mode schema while retained documents exist. Review their intended pricing mode explicitly.');
        }
    }
};
