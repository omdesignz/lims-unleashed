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
        Schema::create('labs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedBigInteger('lab_id')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            foreach (['room_no', 'contact', 'extension', 'email'] as $column) {
                $table->string($column)->nullable();
            }
            $table->text('description')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('technical_head_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('labs', function (Blueprint $table): void {
            $table->foreign('lab_id')->references('id')->on('labs')->restrictOnDelete();
        });

        Schema::create('sample_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('sample_year', 4)->nullable();
            $table->unsignedBigInteger('seq')->nullable();
            $table->string('sample_type')->nullable();
            $table->string('status')->default('POR_INICIAR')->index();
            $table->json('requested_services')->nullable();
            $table->json('client_submitted_info')->nullable();
            $table->foreignId('proposal_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('collection_product_id')->nullable()->constrained('collection_product')->restrictOnDelete();
            $table->foreignId('customer_request_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('lab_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('packaging_id')->nullable()->constrained('packaging_categories')->restrictOnDelete();
            $table->foreignId('received_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('received_by_label')->nullable();
            $table->boolean('collected_by_lab')->default(false);
            foreach (['received_at', 'collected_at', 'analysis_start_date', 'analysis_end_date'] as $column) {
                $table->timestamp($column)->nullable();
            }
            $table->text('obs')->nullable();
            $table->unsignedInteger('retention_period_days')->nullable();
            $table->date('retention_due_at')->nullable()->index();
            $table->date('discard_scheduled_at')->nullable();
            $table->string('retention_status')->default('in_custody');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['sample_year', 'seq']);
        });

        Schema::create('sample_discards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lab_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('sample_id')->constrained('sample_entries')->restrictOnDelete();
            $table->foreignId('discarded_by_id')->constrained('users')->restrictOnDelete();
            $table->text('discard_method');
            $table->decimal('qty', 18, 4)->nullable();
            $table->timestamp('discarded_at');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('i_inventory_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventory')->restrictOnDelete();
            $table->string('batch_number')->index();
            $table->decimal('qty_received', 18, 4);
            $table->decimal('qty_remaining', 18, 4);
            $table->date('expiry_date')->nullable()->index();
            $table->date('received_date')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('i_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('seq')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('is_reagent')->default(false);
            $table->decimal('last_purchase_price', 18, 4)->nullable();
            $table->decimal('standard_cost', 18, 4)->nullable();
            $table->text('acceptance_criteria')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('i_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['seq', 'is_reagent', 'last_purchase_price', 'standard_cost', 'acceptance_criteria']);
        });
        Schema::dropIfExists('i_inventory_batches');
        Schema::dropIfExists('sample_discards');
        Schema::dropIfExists('sample_entries');
        Schema::dropIfExists('labs');
    }
};
