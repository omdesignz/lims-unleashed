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
        Schema::create('proposal_compliance_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->onDelete('cascade');
            $table->boolean('confidentiality')->default(false);
            $table->boolean('impartiality')->default(false);
            $table->boolean('nondisclosure')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->ipAddress('client_ip')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_compliance_agreements');
    }
};
