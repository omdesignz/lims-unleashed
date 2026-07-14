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
        Schema::create('integration_connectors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('inventory_item_id')->nullable()->constrained('i_items')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 120);
            $table->string('key', 80)->unique();
            $table->string('direction', 20)->default('inbound')->index();
            $table->string('adapter', 40)->index();
            $table->string('status', 20)->default('draft')->index();
            $table->string('health_status', 20)->default('unknown')->index();
            $table->text('description')->nullable();
            $table->json('configuration')->nullable();
            $table->text('credentials')->nullable();
            $table->string('ingest_token_hash', 64)->nullable();
            $table->text('signing_secret')->nullable();
            $table->json('event_types')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('last_tested_at')->nullable();
            $table->text('health_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_connectors');
    }
};
