<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('equipment_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('code');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('equipment_categories')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('phytosanitary_products', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('paid_services', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('charge_tax')->default(true);
            $table->boolean('withhold_tax')->default(false);
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('fixed_price', 10, 2)->default(0);
            $table->decimal('tax_percentage', 10, 2)->default(0);
            $table->foreignId('exemption_id')->nullable()->constrained('tax_exemptions')->restrictOnDelete();
            $table->string('exemption_code')->nullable();
            $table->foreignId('tax_id')->nullable()->constrained('tax_types')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('item_statuses', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->constrained('item_categories')->restrictOnDelete();
        });

        Schema::table('i_items', function (Blueprint $table): void {
            $table->foreignId('eq_cat_id')->nullable()->constrained('equipment_categories')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('equipment_categories')->exists()
            || DB::table('phytosanitary_products')->exists()
            || DB::table('paid_services')->exists()
            || DB::table('item_statuses')->whereNotNull('category_id')->exists()
            || DB::table('i_items')->whereNotNull('eq_cat_id')->exists()) {
            throw new RuntimeException('Cannot remove active catalog schema while retained records or category links exist.');
        }

        Schema::table('i_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('eq_cat_id');
        });

        Schema::table('item_statuses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('paid_services');
        Schema::dropIfExists('phytosanitary_products');
        Schema::dropIfExists('equipment_categories');
    }
};
