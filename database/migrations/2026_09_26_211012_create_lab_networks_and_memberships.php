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
        Schema::create('lab_networks', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('main_lab_id')->nullable()->constrained('labs')->restrictOnDelete();
            $table->char('primary_color', 7)->default('#24664f');
            $table->timestamps();
        });
        Schema::table('labs', function (Blueprint $table): void {
            $table->foreignId('network_id')->nullable()->constrained('lab_networks')->restrictOnDelete();
            $table->char('primary_color', 7)->nullable();
        });
        Schema::create('lab_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lab_id')->constrained('labs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('can_view_network')->default(false);
            $table->boolean('can_manage_branding')->default(false);
            $table->timestamps();
            $table->unique(['lab_id', 'user_id']);
        });
        Schema::table('i_warehouses', function (Blueprint $table): void {
            $table->foreignId('lab_id')->nullable()->constrained('labs')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('i_warehouses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('lab_id');
        });
        Schema::dropIfExists('lab_user');
        Schema::table('labs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('network_id');
            $table->dropColumn('primary_color');
        });
        Schema::dropIfExists('lab_networks');
    }
};
