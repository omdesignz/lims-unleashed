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
        Schema::table('reagent_consumption', function (Blueprint $table): void {
            $table->unique(['id', 'lab_id'], 'reagent_consumption_id_lab_unique');
        });
        Schema::create('reagent_consumption_reversals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->foreignId('consumption_id')->unique();
            $table->foreignId('inventory_transaction_id')->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('reversed_at');
            $table->timestamps();
            $table->foreign(['consumption_id', 'lab_id'], 'consumption_reversal_source_lab_fk')
                ->references(['id', 'lab_id'])->on('reagent_consumption')->restrictOnDelete();
            $table->foreign(['inventory_transaction_id', 'lab_id'], 'consumption_reversal_transaction_lab_fk')
                ->references(['id', 'lab_id'])->on('itransactions')->restrictOnDelete();
            $table->index(['lab_id', 'reversed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('reagent_consumption_reversals')->exists()) {
            throw new RuntimeException('Cannot remove retained consumption reversal evidence.');
        }
        Schema::dropIfExists('reagent_consumption_reversals');
        Schema::table('reagent_consumption', function (Blueprint $table): void {
            $table->dropUnique('reagent_consumption_id_lab_unique');
        });
    }
};
