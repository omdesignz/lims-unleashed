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
        Schema::create('integration_transmissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connector_id')->constrained('integration_connectors')->cascadeOnDelete();
            $table->foreignId('mapping_id')->nullable()->constrained('integration_mappings')->nullOnDelete();
            $table->foreignId('matched_sample_id')->nullable()->constrained('samples')->nullOnDelete();
            $table->foreignId('matched_parameter_id')->nullable()->constrained('parameters')->nullOnDelete();
            $table->foreignId('matched_result_id')->nullable()->constrained('results')->nullOnDelete();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_id', 191);
            $table->string('correlation_id', 191)->nullable()->index();
            $table->string('direction', 20)->default('inbound')->index();
            $table->string('status', 24)->default('received')->index();
            $table->string('checksum', 64)->index();
            $table->string('content_type', 100)->default('application/json');
            $table->longText('raw_payload');
            $table->json('normalized_payload')->nullable();
            $table->string('sample_code', 191)->nullable()->index();
            $table->string('parameter_code', 191)->nullable()->index();
            $table->text('measured_value')->nullable();
            $table->string('measured_unit', 80)->nullable();
            $table->dateTime('measured_at')->nullable();
            $table->json('diagnostics')->nullable();
            $table->dateTime('received_at')->index();
            $table->dateTime('processed_at')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['connector_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_transmissions');
    }
};
