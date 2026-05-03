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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->after('email')->index();
            }

            if (! Schema::hasColumn('users', 'github_id')) {
                $table->string('github_id')->nullable()->after('google_id')->index();
            }

            if (! Schema::hasColumn('users', 'microsoft_id')) {
                $table->string('microsoft_id')->nullable()->after('github_id')->index();
            }

            if (! Schema::hasColumn('users', 'x_id')) {
                $table->string('x_id')->nullable()->after('microsoft_id')->index();
            }

            if (! Schema::hasColumn('users', 'microsoft_data')) {
                $table->json('microsoft_data')->nullable()->after('x_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['google_id', 'github_id', 'microsoft_id', 'x_id', 'microsoft_data'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
