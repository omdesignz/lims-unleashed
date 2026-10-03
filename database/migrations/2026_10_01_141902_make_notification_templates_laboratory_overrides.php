<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::table('notification_templates')->exists()) {
            throw new RuntimeException('Existing global notification templates need explicit laboratory assignment before conversion.');
        }

        Schema::table('notification_templates', function (Blueprint $table): void {
            $table->dropUnique(['key']);
            $table->dropColumn(['name', 'category', 'description', 'audience_permission', 'variables']);
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->foreignId('updated_by_id')->constrained('users')->restrictOnDelete();
            $table->unique(['lab_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('notification_templates')->exists()) {
            throw new RuntimeException('Retained laboratory overrides must not be converted to global templates.');
        }

        Schema::table('notification_templates', function (Blueprint $table): void {
            $table->dropUnique(['lab_id', 'key']);
            $table->dropConstrainedForeignId('lab_id');
            $table->dropConstrainedForeignId('updated_by_id');
            $table->string('name');
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->string('audience_permission')->nullable()->index();
            $table->json('variables')->nullable();
            $table->unique('key');
        });
    }
};
