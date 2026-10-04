<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The control values of a chart. A range chart keeps both duplicates and
     * plots their absolute difference. An excluded point stays on record with
     * its reason; the action taken on a point out of control is kept with it.
     */
    public function up(): void
    {
        Schema::create('control_chart_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_chart_id')->constrained('control_charts')->cascadeOnDelete();
            $table->dateTime('measured_at');
            $table->decimal('value', 20, 8);
            $table->decimal('replicate_a', 20, 8)->nullable();
            $table->decimal('replicate_b', 20, 8)->nullable();
            $table->string('run_reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('excluded')->default(false);
            $table->text('exclusion_reason')->nullable();
            $table->text('corrective_action')->nullable();
            $table->timestamp('corrective_action_at')->nullable();
            $table->foreignId('corrective_action_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['control_chart_id', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_chart_points');
    }
};
