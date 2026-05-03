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
        Schema::create('occurrence_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->string('code')->unique()->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('occurrence_origins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->string('code')->unique()->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('occurrences', function (Blueprint $table) {
            $table->id();
            $table->string('occurrence_no')->nullable();
            $table->string('occurrence_year');
            $table->integer('seq')->nullable();
            $table->date('date_reported');
            $table->string('issue_description');
            $table->text('corrective_action')->nullable();
            $table->date('date_resolved')->nullable();
            $table->date('notification_date')->nullable();
            $table->date('client_process_open_notification_date')->nullable();
            $table->longText('analysis')->nullable();
            $table->boolean('has_risk_correction_budget')->default(false);
            $table->string('reason_for_no_risk_correction_budget')->nullable();
            $table->boolean('has_non_conformity_terms')->default(false);
            $table->longText('effect_corrective_actions')->nullable();
            $table->longText('cause_corrective_actions')->nullable();
            $table->date('implementation_date')->nullable();
            $table->boolean('update_risk_matrix')->default(false);
            $table->date('client_process_close_notification_date')->nullable();
            $table->boolean('client_acceptance')->default(false);
            $table->string('client_acceptance_comments')->nullable();
            $table->date('date_closed')->nullable();
            $table->longText('obs')->nullable();
            $table->boolean('was_effective')->default(false);
            $table->unsignedBigInteger('status_id')->nullable();
            $table->string('responsible_name')->nullable();

            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('origin_id')->nullable()->constrained('occurrence_origins')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('occurrence_categories')->onDelete('cascade');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_conformances');
    }
};
