<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proficiency_tests', function (Blueprint $table) {
            $table->string('scheme_type')->default('proficiency')->after('name');
            $table->string('provider_name')->nullable()->after('scheme_type');
            $table->string('round_reference')->nullable()->after('provider_name');
            $table->string('status')->default('planned')->after('round_reference');
            $table->timestamp('scheduled_at')->nullable()->after('date');
            $table->timestamp('closed_at')->nullable()->after('scheduled_at');
            $table->string('scope')->nullable()->after('closed_at');
            $table->string('outcome')->default('pending')->after('scope');
            $table->decimal('z_score', 6, 2)->nullable()->after('outcome');
            $table->text('corrective_actions')->nullable()->after('z_score');
            $table->text('notes')->nullable()->after('corrective_actions');
        });
    }

    public function down(): void
    {
        Schema::table('proficiency_tests', function (Blueprint $table) {
            $table->dropColumn([
                'scheme_type',
                'provider_name',
                'round_reference',
                'status',
                'scheduled_at',
                'closed_at',
                'scope',
                'outcome',
                'z_score',
                'corrective_actions',
                'notes',
            ]);
        });
    }
};
