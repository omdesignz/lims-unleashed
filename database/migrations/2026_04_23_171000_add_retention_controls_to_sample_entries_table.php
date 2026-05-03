<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sample_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('sample_entries', 'retention_period_days')) {
                $table->unsignedInteger('retention_period_days')->nullable()->after('client_submitted_info');
            }

            if (! Schema::hasColumn('sample_entries', 'retention_due_at')) {
                $table->date('retention_due_at')->nullable()->after('retention_period_days');
            }

            if (! Schema::hasColumn('sample_entries', 'discard_scheduled_at')) {
                $table->date('discard_scheduled_at')->nullable()->after('retention_due_at');
            }

            if (! Schema::hasColumn('sample_entries', 'retention_status')) {
                $table->string('retention_status')->default('active')->after('discard_scheduled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sample_entries', function (Blueprint $table) {
            foreach (['retention_status', 'discard_scheduled_at', 'retention_due_at', 'retention_period_days'] as $column) {
                if (Schema::hasColumn('sample_entries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
