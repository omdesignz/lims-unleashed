<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proficiency_tests', function (Blueprint $table) {
            $table->string('role')->default('participant')->after('scheme_type');
            $table->string('organizer_name')->nullable()->after('provider_name');
            $table->json('participants')->nullable()->after('organizer_name');
            $table->json('parameters')->nullable()->after('participants');
            $table->json('participant_results')->nullable()->after('results');
            $table->json('assigned_values')->nullable()->after('participant_results');
            $table->json('performance_summary')->nullable()->after('assigned_values');
            $table->timestamp('enrollment_deadline_at')->nullable()->after('scheduled_at');
            $table->timestamp('submission_deadline_at')->nullable()->after('enrollment_deadline_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proficiency_tests', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'organizer_name',
                'participants',
                'parameters',
                'participant_results',
                'assigned_values',
                'performance_summary',
                'enrollment_deadline_at',
                'submission_deadline_at',
            ]);
        });
    }
};
