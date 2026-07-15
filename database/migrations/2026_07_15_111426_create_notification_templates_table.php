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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->string('audience_permission')->nullable()->index();
            $table->string('title_template');
            $table->text('in_app_template');
            $table->string('email_subject_template')->nullable();
            $table->text('email_template')->nullable();
            $table->string('action_label_template')->nullable();
            $table->string('action_url_template')->nullable();
            $table->json('channels');
            $table->json('variables')->nullable();
            $table->string('priority')->default('normal')->index();
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
