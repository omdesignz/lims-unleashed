<?php

use App\Enums\Proposals\ProposalTrackingStatus;
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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seq');
            $table->string('proposal_year')->nullable();
            $table->string('proposal_no')->nullable();
            $table->string('service_location')->nullable();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->constrained('proposal_templates')->onDelete('cascade');
            $table->string('status')->default(ProposalTrackingStatus::PENDING); // Draft, Sent, Accepted, Rejected, Expired
            $table->json('details'); // Store proposal details as JSON for flexibility
            $table->date('expiry_date')->virtualAs("DATE_ADD(created_at, INTERVAL tolerance_days DAY)");
            $table->boolean('is_original')->default(true);
            $table->foreignId('discount_type')->nullable()->constrained('discount_categories');
            $table->string('file_path')->nullable();
            $table->longText('obs')->nullable();
            $table->decimal('sub_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->longText('unique_hash')->nullable();
            $table->boolean('use_matrix_price')->default(true);
            $table->unsignedBigInteger('tolerance_days')->default(7);
            $table->decimal('withholding_tax_amount', 10, 2)->default(0);
            $table->decimal('withholding_tax_percentage', 10, 2)->default(0);
            $table->decimal('global_discount_amount', 10, 2)->default(0);
            $table->decimal('global_discount_percentage', 10, 2)->default(0);
            $table->boolean('withhold_tax')->default(false);
            $table->boolean('converted_to_invoice')->default(false);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
