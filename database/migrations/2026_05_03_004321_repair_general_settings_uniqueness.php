<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            DELETE duplicate_settings
            FROM settings AS duplicate_settings
            INNER JOIN settings AS newest_settings
                ON duplicate_settings.`group` = newest_settings.`group`
                AND duplicate_settings.`name` = newest_settings.`name`
                AND duplicate_settings.id < newest_settings.id
        ');

        DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'app_logo_url')
            ->update(['locked' => false]);

        Schema::table('settings', function (Blueprint $table) {
            $table->unique(['group', 'name'], 'settings_group_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_group_name_unique');
        });
    }
};
