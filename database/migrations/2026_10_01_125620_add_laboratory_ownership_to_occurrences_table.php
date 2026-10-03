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
        if (DB::table('occurrences')->exists()) {
            throw new RuntimeException('Assign an owning laboratory to retained occurrences before migrating.');
        }

        Schema::table('occurrences', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->unique(['lab_id', 'occurrence_year', 'seq']);
            $table->unique('occurrence_no');
            $table->index(['lab_id', 'date_reported']);
            $table->boolean('client_acceptance')->nullable()->default(null)->change();
            $table->boolean('was_effective')->nullable()->default(null)->change();

            foreach (['department_id', 'user_id', 'origin_id', 'category_id'] as $column) {
                $table->dropForeign([$column]);
            }
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('origin_id')->references('id')->on('occurrence_origins')->restrictOnDelete();
            $table->foreign('category_id')->references('id')->on('occurrence_categories')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('occurrences')->exists()) {
            throw new RuntimeException('Cannot remove laboratory ownership while occurrences exist.');
        }

        Schema::table('occurrences', function (Blueprint $table): void {
            $table->dropIndex(['lab_id', 'date_reported']);
            $table->dropUnique(['lab_id', 'occurrence_year', 'seq']);
            $table->dropUnique(['occurrence_no']);
            $table->dropConstrainedForeignId('lab_id');
            $table->boolean('client_acceptance')->nullable(false)->default(false)->change();
            $table->boolean('was_effective')->nullable(false)->default(false)->change();

            foreach (['department_id', 'user_id', 'origin_id', 'category_id'] as $column) {
                $table->dropForeign([$column]);
            }
            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('origin_id')->references('id')->on('occurrence_origins')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('occurrence_categories')->cascadeOnDelete();
        });
    }
};
