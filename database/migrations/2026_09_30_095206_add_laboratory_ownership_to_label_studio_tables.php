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
        if (DB::table('labels')->whereNull('lab_id')->exists() || DB::table('label_templates')->exists()) {
            throw new RuntimeException('Assign retained labels and custom templates to laboratories before enforcing Label Studio ownership.');
        }

        Schema::table('labels', function (Blueprint $table): void {
            $table->foreign('lab_id')->references('id')->on('labs')->restrictOnDelete();
            $table->index(['lab_id', 'is_active']);
        });
        DB::statement('ALTER TABLE labels ALTER COLUMN lab_id SET NOT NULL');

        Schema::table('label_templates', function (Blueprint $table): void {
            $table->foreignId('lab_id')->nullable()->constrained('labs')->restrictOnDelete();
            $table->boolean('is_system')->default(false);
            $table->index(['lab_id', 'is_active']);
        });
        DB::statement('ALTER TABLE label_templates ADD CONSTRAINT label_templates_owner_check CHECK ((is_system AND lab_id IS NULL) OR (NOT is_system AND lab_id IS NOT NULL))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('labels')->exists() || DB::table('label_templates')->exists()) {
            throw new RuntimeException('Cannot remove laboratory ownership while labels or custom templates are retained.');
        }

        DB::statement('ALTER TABLE label_templates DROP CONSTRAINT label_templates_owner_check');
        Schema::table('label_templates', function (Blueprint $table): void {
            $table->dropIndex(['lab_id', 'is_active']);
            $table->dropConstrainedForeignId('lab_id');
            $table->dropColumn('is_system');
        });

        DB::statement('ALTER TABLE labels ALTER COLUMN lab_id DROP NOT NULL');
        Schema::table('labels', function (Blueprint $table): void {
            $table->dropIndex(['lab_id', 'is_active']);
            $table->dropForeign(['lab_id']);
        });
    }
};
