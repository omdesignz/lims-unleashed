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
        Schema::table('proposal_compliance_agreement_logs', function (Blueprint $table) {
            $table->boolean('nondisclosure')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE proposal_compliance_agreement_logs IN ACCESS EXCLUSIVE MODE');

            if (DB::table('proposal_compliance_agreement_logs')->whereNotNull('nondisclosure')->exists()) {
                throw new RuntimeException('Cannot remove recorded nondisclosure consent evidence.');
            }

            Schema::table('proposal_compliance_agreement_logs', function (Blueprint $table) {
                $table->dropColumn('nondisclosure');
            });
        });
    }
};
