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
        Schema::table('sample_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('sample_entries', 'collection_product_id')) {
                $table->foreignId('collection_product_id')
                    ->nullable()
                    ->after('proposal_id')
                    ->constrained('collection_product')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sample_entries', function (Blueprint $table) {
            if (Schema::hasColumn('sample_entries', 'collection_product_id')) {
                $table->dropConstrainedForeignId('collection_product_id');
            }
        });
    }
};
