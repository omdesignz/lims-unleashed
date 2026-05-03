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
        Schema::table('customer_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_requests', 'reference')) {
                $table->string('reference')->nullable()->after('id')->index();
            }

            if (! Schema::hasColumn('customer_requests', 'title')) {
                $table->string('title')->nullable()->after('warehouse_id');
            }

            if (! Schema::hasColumn('customer_requests', 'request_type')) {
                $table->string('request_type')->default('general_support')->after('title')->index();
            }

            if (! Schema::hasColumn('customer_requests', 'status')) {
                $table->string('status')->default('pending')->after('request_type')->index();
            }

            if (! Schema::hasColumn('customer_requests', 'priority')) {
                $table->string('priority')->default('normal')->after('status');
            }

            if (! Schema::hasColumn('customer_requests', 'preferred_date')) {
                $table->date('preferred_date')->nullable()->after('priority');
            }

            if (! Schema::hasColumn('customer_requests', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('preferred_date');
            }

            if (! Schema::hasColumn('customer_requests', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('submitted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_requests', function (Blueprint $table) {
            $dropColumns = collect([
                'reference',
                'title',
                'request_type',
                'status',
                'priority',
                'preferred_date',
                'submitted_at',
                'resolved_at',
            ])->filter(fn (string $column) => Schema::hasColumn('customer_requests', $column))->all();

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
