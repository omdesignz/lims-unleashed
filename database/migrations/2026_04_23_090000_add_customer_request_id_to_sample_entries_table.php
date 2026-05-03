<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sample_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('sample_entries', 'customer_request_id')) {
                $table->foreignId('customer_request_id')
                    ->nullable()
                    ->after('proposal_id')
                    ->constrained('customer_requests')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sample_entries', function (Blueprint $table) {
            if (Schema::hasColumn('sample_entries', 'customer_request_id')) {
                $table->dropConstrainedForeignId('customer_request_id');
            }
        });
    }
};
