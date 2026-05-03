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
            $table->string('profile_photo_path', 2048)->nullable();
            $table->string('username')->unique()->nullable();
            $table->dateTime('password_changed_at', 0)->nullable();
            $table->string('primary_phone')->nullable();
            $table->string('secondary_phone')->nullable();
            $table->string('id_number')->unique()->nullable();
            $table->date('dob')->nullable();
            $table->boolean('is_active')->default(1);
            $table->boolean('password_changed_by_user')->default(0);
            $table->char('birthday', 5)->virtualAs('date_format(dob, "%m-%d")')->index();
            $table->enum('gender', ['M', 'F', 'O']);
            $table->softDeletes('deleted_at', 0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_photo_path');
            $table->dropColumn('username');
            $table->dropColumn('password_changed_at');
            $table->dropColumn('primary_phone');
            $table->dropColumn('secondary_phone');
            $table->dropColumn('id_number');
            $table->dropColumn('dob');
            $table->dropColumn('is_active');
            $table->dropColumn('password_changed_by_user');
            $table->dropColumn('birthday');
            $table->dropColumn('gender');
            $table->dropColumn('deleted_at');
        });
    }
};
