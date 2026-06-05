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
        Schema::table('gestlab_media', function (Blueprint $table) {
            $table->string('studio_asset_kind')->nullable()->after('author_id');
            $table->string('studio_asset_source')->nullable()->after('studio_asset_kind');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gestlab_media', function (Blueprint $table) {
            $table->dropColumn(['studio_asset_kind', 'studio_asset_source']);
        });
    }
};
