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
        Schema::create('document_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_type')->index();
            $table->unsignedBigInteger('document_id')->index();
            $table->json('recipients');
            $table->json('cc')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('attachment_name')->nullable();
            $table->string('status')->default('queued')->index();
            $table->text('failure_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'document_id', 'created_at'], 'document_delivery_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_deliveries');
    }
};
