<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('management_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable()->unique();
            $table->date('review_date');
            $table->string('status')->default('planned');
            $table->string('scope')->nullable();
            $table->text('summary')->nullable();
            $table->text('decisions')->nullable();
            $table->text('risks_and_opportunities')->nullable();
            $table->text('improvements')->nullable();
            $table->foreignId('conducted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_reviews');
    }
};
