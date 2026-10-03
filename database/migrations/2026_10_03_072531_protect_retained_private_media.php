<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->softDeletes();
        });
        Schema::table('gestlab_media', function (Blueprint $table): void {
            $table->string('disk')->default('public');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (DB::table('media')->whereNotNull('deleted_at')->exists()
            || DB::table('gestlab_media')->whereNotNull('deleted_at')->exists()
            || DB::table('gestlab_media')->where('disk', '<>', 'public')->exists()) {
            throw new RuntimeException('Cannot discard retained private-document state.');
        }
        Schema::table('gestlab_media', function (Blueprint $table): void {
            $table->dropColumn(['disk', 'deleted_at']);
        });
        Schema::table('media', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
