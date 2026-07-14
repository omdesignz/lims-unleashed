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
        Schema::create('integration_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connector_id')->constrained('integration_connectors')->cascadeOnDelete();
            $table->string('event_type', 100)->index();
            $table->nullableMorphs('subject');
            $table->uuid('idempotency_key')->unique();
            $table->json('payload');
            $table->string('status', 24)->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('response_excerpt')->nullable();
            $table->text('last_error')->nullable();
            $table->dateTime('next_attempt_at')->nullable()->index();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_deliveries');
    }
};
