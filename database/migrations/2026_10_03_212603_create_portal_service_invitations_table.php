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
        Schema::create('portal_service_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('issued_by_id')->constrained('users')->restrictOnDelete();
            $table->uuid('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->foreignId('customer_request_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->index(['lab_id', 'warehouse_id', 'consumed_at'], 'portal_service_invitations_recipient_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_service_invitations');
    }
};
