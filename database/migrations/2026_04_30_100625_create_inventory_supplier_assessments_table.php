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
        Schema::create('inventory_supplier_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_supplier_id');
            $table->foreignId('department_id')->nullable();
            $table->foreignId('assessed_by_user_id')->nullable();
            $table->date('assessment_date');
            $table->date('next_review_at')->nullable();
            $table->string('status', 50)->default('approved');
            $table->string('risk_level', 50)->default('medium');
            $table->unsignedSmallInteger('total_score')->default(0);
            $table->unsignedTinyInteger('delivery_score')->nullable();
            $table->unsignedTinyInteger('quality_score')->nullable();
            $table->unsignedTinyInteger('compliance_score')->nullable();
            $table->unsignedTinyInteger('responsiveness_score')->nullable();
            $table->string('evidence_reference')->nullable();
            $table->boolean('approved_supplier')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('strengths')->nullable();
            $table->text('gaps')->nullable();
            $table->text('corrective_actions')->nullable();
            $table->text('follow_up_actions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('inventory_item_supplier_id', 'inv_sup_assessment_supplier_fk')->references('id')->on('i_suppliers');
            $table->foreign('department_id', 'inv_sup_assessment_department_fk')->references('id')->on('departments');
            $table->foreign('assessed_by_user_id', 'inv_sup_assessment_user_fk')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_supplier_assessments');
    }
};
