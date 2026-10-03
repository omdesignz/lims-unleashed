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
        if (! Schema::hasColumn('contract_guide_items', 'du_no')) {
            Schema::table('contract_guide_items', function (Blueprint $table): void {
                $table->string('du_no')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('contract_guide_items')->whereNotNull('du_no')->exists()) {
            throw new RuntimeException('Cannot remove customs references while retained contract guide items contain values.');
        }

        Schema::table('contract_guide_items', function (Blueprint $table): void {
            $table->dropColumn('du_no');
        });
    }
};
