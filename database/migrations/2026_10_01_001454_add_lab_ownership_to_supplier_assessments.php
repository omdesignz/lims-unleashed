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
        if (DB::table('inventory_supplier_assessments')->exists()) {
            throw new RuntimeException('Assign an owning laboratory to retained supplier assessments before migrating.');
        }

        Schema::table('inventory_supplier_assessments', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->index(['lab_id', 'assessment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('inventory_supplier_assessments')->exists()) {
            throw new RuntimeException('Cannot remove supplier-assessment ownership while assessments exist.');
        }

        Schema::table('inventory_supplier_assessments', function (Blueprint $table): void {
            $table->dropIndex(['lab_id', 'assessment_date']);
            $table->dropConstrainedForeignId('lab_id');
        });
    }
};
