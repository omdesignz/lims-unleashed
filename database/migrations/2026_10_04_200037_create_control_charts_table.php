<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Control charts for the internal quality control of a test (ISO/IEC
     * 17025:2017, 7.7.1): a mean chart (X) of a control sample's values or a
     * range chart (R) of duplicate determinations. The limits are kept on the
     * chart with who set them, when and on what basis.
     */
    public function up(): void
    {
        Schema::create('control_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained('labs')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('chart_type', 20);
            $table->foreignId('parameter_id')->nullable()->constrained('parameters')->nullOnDelete();
            $table->string('method', 160)->nullable();
            $table->string('matrix', 160)->nullable();
            $table->string('control_material', 160)->nullable();
            $table->string('material_lot', 80)->nullable();
            $table->string('unit', 40)->nullable();
            $table->decimal('centre_line', 20, 8)->nullable();
            $table->decimal('standard_deviation', 20, 8)->nullable();
            $table->string('limits_source', 20)->nullable();
            $table->text('limits_basis')->nullable();
            $table->unsignedInteger('limits_point_count')->nullable();
            $table->timestamp('limits_set_at')->nullable();
            $table->foreignId('limits_set_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['lab_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_charts');
    }
};
