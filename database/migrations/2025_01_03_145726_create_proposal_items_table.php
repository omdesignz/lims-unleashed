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
        Schema::create('proposal_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('item_id')->nullable();
            $table->string('item_description')->nullable();
            $table->foreignId('proposal_id')->nullable()->constrained('proposals');
            $table->foreignId('exemption_id')->nullable()->constrained('tax_exemptions');
            $table->foreignId('standard_id')->nullable()->constrained('standards');
            $table->string('exemption_code')->nullable();
            $table->foreignId('discount_id')->nullable()->constrained('discount_categories');
            $table->foreignId('unit_id')->references('id')->on('units')->nullable();
            $table->foreignId('tax_id')->nullable()->constrained('tax_types');
            $table->decimal('qty', 10, 2)->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('discount_percentage', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('tax_percentage', 10, 2)->default(0);
            $table->longText('obs')->nullable();
            $table->boolean('charge_tax')->default(true);
            $table->boolean('withhold_tax')->default(false);
            $table->decimal('global_discount_amount', 10, 2)->default(0);
            $table->decimal('global_discount_portion_percentage', 10, 2)->default(0);
            $table->nullableMorphs('itemable');
            $table->json('extra_data')->nullable();


            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_items');
    }
};
