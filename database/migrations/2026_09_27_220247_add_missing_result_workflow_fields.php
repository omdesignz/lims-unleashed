<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $existing = Schema::getColumnListing('results');
        Schema::table('results', function (Blueprint $table) use ($existing): void {
            foreach (['insertion_notes', 'verification_notes', 'approval_notes'] as $column) {
                if (! in_array($column, $existing, true)) {
                    $table->text($column)->nullable();
                }
            }
            foreach (['verification_status', 'ref_val_origin', 'insertion_method', 'sumC', 'volume', 'n1', 'n2', 'dilution', 'd1', 'd2', 'cfu1', 'cfu2'] as $column) {
                if (! in_array($column, $existing, true)) {
                    $table->string($column)->nullable();
                }
            }
            foreach (['is_calculated', 'is_override'] as $column) {
                if (! in_array($column, $existing, true)) {
                    $table->boolean($column)->default(false);
                }
            }
            if (! in_array('calculation_metadata', $existing, true)) {
                $table->json('calculation_metadata')->nullable();
            }
            if (! in_array('calculated_at', $existing, true)) {
                $table->timestamp('calculated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Legacy installations may already contain these fields; preserve analytical evidence.
    }
};
