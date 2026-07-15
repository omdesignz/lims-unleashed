<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->index('created_at', 'activity_log_created_at_index');
            $table->index(['event', 'created_at'], 'activity_log_event_created_at_index');
            $table->index('batch_uuid', 'activity_log_batch_uuid_index');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->index(['deleted_at', 'created_at'], 'customers_export_status_date_index');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->index(['deleted_at', 'created_at'], 'products_export_status_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropIndex('activity_log_created_at_index');
            $table->dropIndex('activity_log_event_created_at_index');
            $table->dropIndex('activity_log_batch_uuid_index');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex('customers_export_status_date_index');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_export_status_date_index');
        });
    }
};
