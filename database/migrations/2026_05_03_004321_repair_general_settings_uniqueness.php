<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateIds = DB::table('settings as duplicate_settings')
            ->join('settings as newest_settings', function ($join): void {
                $join->on('duplicate_settings.group', '=', 'newest_settings.group')
                    ->on('duplicate_settings.name', '=', 'newest_settings.name')
                    ->on('duplicate_settings.id', '<', 'newest_settings.id');
            })
            ->distinct()
            ->pluck('duplicate_settings.id');

        DB::table('settings')->whereIn('id', $duplicateIds)->delete();

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
