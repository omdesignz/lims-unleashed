<?php

use App\Models\User;
use App\Models\Warehouse;
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
        Schema::table('passkeys', function (Blueprint $table) {
            $table->string('authenticatable_type')->nullable()->after('id')->index();
        });

        DB::table('passkeys')->update([
            'authenticatable_type' => User::class,
        ]);

        Schema::table('passkeys', function (Blueprint $table) {
            $table->dropForeign('passkeys_authenticatable_fk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('passkeys')
            ->where('authenticatable_type', Warehouse::class)
            ->delete();

        DB::table('passkeys')
            ->whereNull('authenticatable_type')
            ->update(['authenticatable_type' => User::class]);

        Schema::table('passkeys', function (Blueprint $table) {
            $table
                ->foreign('authenticatable_id', 'passkeys_authenticatable_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->dropColumn('authenticatable_type');
        });
    }
};
