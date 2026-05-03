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

        // Countries
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('phone_code');
            $table->foreignId('user_id')->nullable();
            $table->softDeletes('deleted_at', 0);
            $table->timestamps();
        });


        // Departments
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->string('contact')->nullable();
            $table->string('extension')->nullable();
            $table->string('code')->unique()->nullable();
            $table->string('email')->unique()->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');

            $table->softDeletes();
            $table->timestamps();
        });


        // Department & User Pivot
        Schema::create('department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->foreignId('user_id')->nullable()->constrained('users');

            $table->index(['department_id', 'user_id']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Customer Categories
        Schema::create('customer_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->string('code')->unique()->nullable();

            $table->softDeletes();
            $table->timestamps();
        });


        // Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->string('code')->unique()->nullable();
            $table->foreignId('category_id')->nullable()->constrained('customer_categories');
            $table->unsignedBigInteger('warehouse_id')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });


        // Warehouses
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('invoicing_email')->nullable();
            $table->string('primary_phone')->nullable();
            $table->string('alternative_phone')->nullable();
            $table->string('nif')->nullable();
            $table->string('focal_point')->nullable();
            $table->string('focal_point_email')->nullable();
            $table->string('focal_point_contact')->nullable();
            $table->longText('address')->nullable();
            $table->longText('description')->nullable();
            $table->string('municipality')->unique()->nullable();
            $table->string('province')->unique()->nullable();
            $table->string('code')->unique()->nullable();
            $table->string('name')->unique()->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();

            $table->index(['id', 'nif']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Warehouses Password Reset Tokens
        Schema::create('warehouse_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });


        // Contact Categories
        Schema::create('contact_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->string('code')->unique()->nullable();

            $table->softDeletes();
            $table->timestamps();
        });


        // Complementary Contacts
        Schema::create('complementary_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('contact');
            $table->foreignId('category_id')->nullable()->constrained('contact_categories');
            $table->foreignId('country_id')->nullable()->constrained('countries');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->longText('description')->nullable();

            $table->index(['id', 'contact']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Parameters
        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->longText('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('tax_percentage', 10, 2)->default(0);
            $table->boolean('charge_tax')->default(true);
            $table->boolean('withhold_tax')->default(false);
            $table->boolean('active')->default(true);
            $table->boolean('result_is_qualitative')->default(false);
            $table->foreignId('exemption_id')->nullable()->constrained('tax_exemptions');
            $table->string('exemption_code')->nullable();
            $table->foreignId('tax_id')->nullable()->constrained('tax_types');

            $table->index(['id', 'name']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Matrixes
        Schema::create('matrixes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->longText('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('fixed_price', 10, 2)->default(0);
            $table->decimal('tax_percentage', 10, 2)->default(0);
            $table->boolean('charge_tax')->default(true);
            $table->boolean('withhold_tax')->default(false);
            $table->foreignId('exemption_id')->nullable()->constrained('tax_exemptions');
            $table->string('exemption_code')->nullable();
            $table->foreignId('tax_id')->nullable()->constrained('tax_types');

            $table->index(['id', 'code']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('charge_tax')->default(true);
            $table->boolean('withhold_tax')->default(false);
            $table->longText('description')->nullable();
            $table->foreignId('matrix_id')->nullable()->constrained('matrixes');
            $table->foreignId('exemption_id')->nullable()->constrained('tax_exemptions');

            $table->index(['id', 'name']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Inspection Products
        Schema::create('inspection_products', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date')->nullable();
            $table->string('du_no')->nullable();
            $table->string('container_no')->nullable();
            $table->string('company')->nullable();
            $table->string('location')->nullable();
            $table->string('product_type')->nullable();
            $table->date('col_date')->nullable();
            $table->longText('obs')->nullable();

            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('product_id')->nullable()->constrained('products');

            $table->index(['id', 'du_no']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Units
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();

            $table->index(['id', 'code']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Normative Work Procedures
        Schema::create('nwps', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();

            $table->index(['id', 'code']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Collection Colaborations
        Schema::create('collection_collaborations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();

            $table->index(['id', 'name']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Collection End Results
        Schema::create('collection_end_results', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();

            $table->index(['id', 'name']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Transport Categories
        Schema::create('trans_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();

            $table->index(['id', 'name']);
            $table->softDeletes();
            $table->timestamps();
        });


        // FAQ Categories
        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();

            $table->index(['id', 'name']);
            $table->softDeletes();
            $table->timestamps();
        });


        // FAQs
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('faq_categories');
            $table->string('description')->nullable();
            $table->json('extra_data')->nullable();


            $table->softDeletes();
            $table->timestamps();
        });


        // FAQ Answers
        Schema::create('faq_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_id')->nullable()->constrained('faq');
            $table->longText('description')->nullable();
            $table->json('extra_data')->nullable();


            $table->softDeletes();
            $table->timestamps();
        });


        // Customer Request Categories
        Schema::create('customer_request_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();

            $table->index(['id', 'name']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Customer Requests
        Schema::create('customer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('customer_request_categories');
            $table->foreignId('customer_id')->nullable()->constrained('customer_id');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouse_id');
            $table->longText('description')->nullable();
            $table->boolean('answered')->default(false);
            $table->string('contact')->nullable();
            $table->string('email')->nullable();
            $table->json('extra_data')->nullable();


            $table->softDeletes();
            $table->timestamps();
        });


        // Vehicles
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('trans_categories');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->string('number_plate')->unique()->nullable();
            $table->string('description')->nullable();
            $table->json('extra_data')->nullable();

            $table->index(['id', 'number_plate']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Standards
        Schema::create('standards', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();

            $table->index(['id', 'code']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Protocols
        Schema::create('protocols', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();

            $table->index(['id', 'code']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Analysis Categories
        Schema::create('analysis_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->string('name')->unique();
            $table->string('code')->nullable();
            $table->string('description')->nullable();

            $table->index(['id', 'name']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Packaging Categories
        Schema::create('packaging_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();

            $table->index(['id', 'name']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Temperatures
        Schema::create('temperatures', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable();
            $table->string('description')->nullable();

            $table->index(['id', 'name']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Profiles
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->longText('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->foreignId('category_id')->nullable()->constrained('analysis_categories');

            $table->index(['id', 'name']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Matrix & Profile Pivot
        Schema::create('matrix_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->nullable()->constrained('profiles');
            $table->foreignId('matrix_id')->nullable()->constrained('matrixes');
            $table->string('profile')->nullable();
            $table->string('matrix')->nullable();

            $table->index(['matrix_id', 'profile_id']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Result Categories
        Schema::create('result_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description')->nullable();

            $table->index(['id', 'name']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Parameter & Profile Pivot
        Schema::create('parameter_profile', function (Blueprint $table) {
            $table->id();
            $table->string('unit')->nullable();
            $table->string('method')->nullable();
            $table->string('standard')->nullable();
            $table->string('nwp')->nullable();
            $table->string('category')->nullable();
            $table->boolean('count')->default(true);
            $table->string('min_ref_value')->nullable();
            $table->string('max_ref_value')->nullable();
            $table->string('dilutions')->nullable();
            $table->foreignId('parameter_id')->nullable()->constrained('parameters');
            $table->foreignId('profile_id')->nullable()->constrained('profiles');
            $table->foreignId('category_id')->nullable()->constrained('result_categories');
            $table->foreignId('formula_id')->nullable()->constrained('formulas');
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->foreignId('protocol_id')->nullable()->constrained('protocols');
            $table->foreignId('standard_id')->nullable()->constrained('standards');
            $table->foreignId('nwp_id')->nullable()->constrained('nwps');
            $table->json('extra_data')->nullable();
            $table->index(['parameter_id', 'profile_id']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Invoice Categories
        Schema::create('invoice_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->longText('description')->nullable();

            $table->index(['id', 'code']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Collection Reasons
        Schema::create('collection_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->string('name')->unique();
            $table->string('code')->nullable();
            $table->string('description')->nullable();

            $table->index(['id', 'name']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Collections
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('collectionable');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->boolean('processed')->default(false);
            $table->boolean('recollection')->default(false);

            $table->softDeletes();
            $table->timestamps();
        });


        // Discount Categories
        Schema::create('discount_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('symbol')->unique()->nullable();
            $table->foreignId('user_id')->nullable();
            $table->softDeletes('deleted_at', 0);
            $table->timestamps();
        });


        // Invoice Tax Types
        Schema::create('tax_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('percent', 5, 2);
            $table->boolean('compound_tax')->default(false);
            $table->boolean('collective_tax')->default(false);
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable();

            $table->softDeletes('deleted_at', 0);
            $table->timestamps();
        });


        // Currencies
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('symbol')->nullable();
            $table->string('thousand_separator')->nullable();
            $table->string('decimal_separator')->nullable();
            $table->boolean('swap_currency_symbol')->default(false);

            $table->softDeletes('deleted_at', 0);
            $table->timestamps();
        });


        // Payment Categories
        Schema::create('payment_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->string('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });


        // Invoice Tax Exemptions
        Schema::create('tax_exemptions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('reason')->nullable();
            $table->string('law')->nullable();
            $table->longText('description')->nullable();
            $table->foreignId('user_id')->nullable('users');

            $table->softDeletes();
            $table->timestamps();
        });


        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->nullable()->constrained('invoice_categories');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('discount_type')->nullable()->constrained('discount_categories');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->string('inv_no')->nullable();
            $table->string('invoice_month');
            $table->integer('seq')->nullable();
            $table->string('description')->nullable();
            $table->string('internal_ref')->nullable();
            $table->string('file_path')->nullable();
            $table->date('date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('sub_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('amount_due', 10, 2)->default(0);
            $table->longText('obs')->nullable();
            $table->longText('unique_hash')->nullable();
            $table->string('status_code')->nullable();
            $table->boolean('status')->default(false);
            $table->boolean('is_original')->default(true);
            $table->boolean('use_matrix_price')->default(true);
            $table->boolean('exported_saft')->default(false);
            $table->nullableMorphs('invoiceable');
            $table->json('extra_data')->nullable();
            $table->decimal('withholding_tax_amount', 10, 2)->default(0);
            $table->decimal('withholding_tax_percentage', 10, 2)->default(0);
            $table->decimal('global_discount_amount', 10, 2)->default(0);
            $table->decimal('global_discount_percentage', 10, 2)->default(0);
            $table->boolean('withhold_tax')->default(false);
            $table->index(['id', 'inv_no']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Collection Product Pivot
        Schema::create('collection_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->nullable()->constrained('collections');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('owner_id')->nullable()->constrained('users');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('pack_id')->nullable()->constrained('packaging_categories');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->foreignId('temperature_id')->nullable()->constrained('temperatures');
            $table->foreignId('result_id')->nullable()->constrained('collection_end_results');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles');
            $table->string('comercial_brand')->nullable();
            $table->string('du_no')->nullable();
            $table->string('term_no')->nullable();
            $table->string('origin')->nullable();
            $table->string('temperature_value')->nullable();
            $table->string('container_no')->nullable();
            $table->boolean('recollection')->default(false);
            $table->longText('obs')->nullable();
            $table->boolean('processed')->default(false);
            $table->date('expiry_date')->nullable();
            $table->date('production_date')->nullable();
            $table->date('collection_date')->nullable();
            $table->string('qty')->nullable();
            $table->string('collected_qty')->nullable();
            $table->string('lot')->nullable();
            $table->string('bl')->nullable();
            $table->boolean('invoiced')->default(false)->nullable();
            $table->string('progress')->nullable();
            $table->boolean('status')->default(false)->nullable();
            $table->json('extra_data')->nullable();
            $table->decimal('sumC', 10, 2)->default(0);
            $table->decimal('volume', 10, 2)->default(0);
            $table->decimal('n1', 10, 2)->default(0);
            $table->decimal('n2', 10, 2)->default(0);
            $table->string('dilution')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });


        // Invoice Items
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->foreignId('exemption_id')->nullable()->constrained('tax_exemptions');
            $table->foreignId('tax_id')->nullable()->constrained('tax_types');
            $table->foreignId('discount_id')->nullable()->constrained('discount_categories');
            $table->decimal('qty', 10, 2)->default(0);
            $table->bigInteger('item_id')->nullable();
            $table->string('item_description')->nullable();
            $table->string('exemption_code')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('discount_percentage', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('tax_percentage', 10, 2)->default(0);
            $table->nullableMorphs('itemable');
            $table->longText('obs')->nullable();
            $table->boolean('charge_tax')->default(true);
            $table->boolean('withhold_tax')->default(false);
            $table->decimal('global_discount_amount', 10, 2)->default(0);
            $table->decimal('global_discount_portion_percentage', 10, 2)->default(0);
            $table->json('extra_data')->nullable();


            $table->softDeletes();
            $table->timestamps();
        });


        // Quotes
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('discount_type')->nullable()->constrained('discount_categories');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->string('quote_no')->nullable();
            $table->string('quote_month');
            $table->integer('seq')->nullable();
            $table->string('description')->nullable();
            $table->string('internal_ref')->nullable();
            $table->string('file_path')->nullable();
            $table->date('date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('sub_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->longText('obs')->nullable();
            $table->longText('unique_hash')->nullable();
            $table->boolean('status')->default(false);
            $table->boolean('is_original')->default(true);
            $table->boolean('use_matrix_price')->default(true);
            $table->boolean('exported_saft')->default(false);
            $table->boolean('converted_to_invoice')->default(false);
            $table->json('extra_data')->nullable();
            $table->decimal('withholding_tax_amount', 10, 2)->default(0);
            $table->decimal('withholding_tax_percentage', 10, 2)->default(0);
            $table->decimal('global_discount_amount', 10, 2)->default(0);
            $table->decimal('global_discount_percentage', 10, 2)->default(0);
            $table->boolean('withhold_tax')->default(false);

            $table->index(['id', 'quote_no']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Quote Items
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('item_id')->nullable();
            $table->string('item_description')->nullable();
            $table->foreignId('quote_id')->nullable()->constrained('quotes');
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->foreignId('exemption_id')->nullable()->constrained('tax_exemptions');
            $table->string('exemption_code')->nullable();
            $table->foreignId('discount_id')->nullable()->constrained('discount_categories');
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


        // Receipts
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('payment_type')->nullable()->constrained('payment_categories', 'id');
            $table->string('rec_no')->nullable();
            $table->string('rec_month')->nullable();
            $table->integer('seq')->nullable();
            $table->string('description')->nullable();
            $table->date('date')->nullable();
            $table->text('obs')->nullable();
            $table->longText('unique_hash')->nullable();
            $table->boolean('exported_saft')->default(false);
            $table->boolean('is_original')->default(false);
            $table->string('file_path')->nullable();
            $table->json('extra_data')->nullable();


            $table->index(['id', 'rec_no']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Invoice & Receipt Pivot
        Schema::create('invoice_receipt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->foreignId('receipt_id')->nullable()->constrained('receipts');
            $table->foreignId('payment_id')->nullable()->constrained('payment_categories');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('pending_amount', 10, 2)->default(0);
            $table->decimal('invoice_pending_amount', 10, 2)->default(0);

            $table->longText('obs')->nullable();

            $table->index(['invoice_id', 'receipt_id']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Contract Guides
        Schema::create('contract_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->string('guide_no')->nullable();
            $table->string('ref_no')->nullable();
            $table->string('entry_point')->nullable();
            $table->string('collection_point')->nullable();
            $table->string('guide_month')->nullable();
            $table->string('du_no')->nullable();
            $table->string('nif')->nullable();
            $table->string('contact')->nullable();
            $table->string('email')->nullable();
            $table->string('bl')->nullable();
            $table->longText('obs')->nullable();
            $table->integer('seq')->nullable();
            $table->unsignedBigInteger('collection_id')->nullable();
            $table->date('date')->nullable();
            $table->string('file_path')->nullable();
            $table->json('extra_data')->nullable();


            $table->index(['id', 'guide_no']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Contract Guide Items
        Schema::create('contract_guide_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')->nullable()->constrained('contract_guides');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('country_id')->nullable()->constrained('countries');
            $table->string('bl')->nullable();
            $table->string('lot')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('origin')->nullable();
            $table->string('brand')->nullable();
            $table->longText('obs')->nullable();
            $table->unsignedBigInteger('collection_id')->nullable();
            $table->date('date')->nullable();
            $table->json('extra_data')->nullable();


            $table->softDeletes();
            $table->timestamps();
        });


        // Credit Notes
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->foreignId('discount_type')->nullable()->constrained('discount_categories');
            $table->string('note_no')->nullable();
            $table->string('note_month');
            $table->integer('seq')->nullable();
            $table->string('reason')->nullable();
            $table->string('internal_ref')->nullable();
            $table->string('file_path')->nullable();
            $table->date('date')->nullable();
            $table->decimal('sub_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->longText('obs')->nullable();
            $table->longText('unique_hash')->nullable();
            $table->boolean('status')->default(false);
            $table->boolean('is_original')->default(true);
            $table->boolean('use_matrix_price')->default(true);
            $table->boolean('exported_saft')->default(false);
            $table->unsignedBigInteger('collection_id')->nullable();
            $table->json('extra_data')->nullable();

            $table->decimal('withholding_tax_amount', 10, 2)->default(0);
            $table->decimal('withholding_tax_percentage', 10, 2)->default(0);
            $table->decimal('global_discount_amount', 10, 2)->default(0);
            $table->decimal('global_discount_percentage', 10, 2)->default(0);
            $table->boolean('withhold_tax')->default(false);

            $table->index(['id', 'note_no']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Credit Note Items
        Schema::create('credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->nullable()->constrained('credit_notes');
            $table->bigInteger('item_id')->nullable();
            $table->string('item_description')->nullable();
            $table->string('exemption_code')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->foreignId('exemption_id')->nullable()->constrained('tax_exemptions');
            $table->foreignId('discount_id')->nullable()->constrained('discount_categories');
            $table->foreignId('tax_id')->nullable()->constrained('tax_types');
            $table->decimal('qty', 10, 2)->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('discount_percentage', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('tax_percentage', 10, 2)->default(0);
            $table->longText('obs')->nullable();
            $table->nullableMorphs('itemable');
            $table->boolean('charge_tax')->default(true);
            
            $table->boolean('withhold_tax')->default(false);
            $table->decimal('global_discount_amount', 10, 2)->default(0);
            $table->decimal('global_discount_portion_percentage', 10, 2)->default(0);
            $table->unsignedBigInteger('collection_id')->nullable();
            $table->json('extra_data')->nullable();


            $table->softDeletes();
            $table->timestamps();
        });


        // Collection Collaboration Pivot
        Schema::create('col_collab', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->nullable()->constrained('collections');
            $table->foreignId('collaboration_id')->nullable()->constrained('collection_collaborations');

            $table->index(['collection_id', 'collaboration_id']);
            $table->softDeletes();
            $table->timestamps();
        });

        // Collection Reason Pivot
        Schema::create('col_reason', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->nullable()->constrained('collections');
            $table->foreignId('reason_id')->nullable()->constrained('collection_reasons');

            $table->index(['collection_id', 'reason_id']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Collection Laboratory Codes
        Schema::create('lab_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->nullable()->constrained('collection_product');
            $table->string('code')->nullable();
            $table->string('cl_month');
            $table->integer('seq')->nullable();
            $table->nullableMorphs('codeable');

            $table->index(['id', 'code']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Programmed Collections
        Schema::create('programmed_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('quote_id')->nullable()->constrained('quotes');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles');
            $table->string('collection_location')->nullable();
            $table->string('vehicle_reference')->nullable();
            $table->date('entry_date')->nullable();
            $table->date('col_date')->nullable();
            $table->boolean('status')->default(false);
            $table->boolean('placed_analysis')->default(false);
            $table->boolean('quoted')->default(false);

            $table->index(['id', 'col_date']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Direct Collections
        Schema::create('direct_collections', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->date('col_date')->nullable();

            $table->index(['id', 'col_date']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Samples
        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cl_id')->nullable()->constrained('lab_codes');
            $table->string('code')->nullable();
            $table->string('sample_month');
            $table->integer('seq')->nullable();

            $table->index(['id', 'code']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Recollections
        Schema::create('recollections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->nullable()->constrained('collection_product');
            $table->foreignId('sample_id')->nullable()->constrained('samples');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('code')->nullable();
            $table->string('rec_month');
            $table->integer('seq')->nullable();
            $table->date('date')->nullable();

            $table->index(['id', 'code']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Analysis
        Schema::create('analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cl_id')->nullable()->constrained('lab_codes');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->foreignId('sample_id')->nullable()->constrained('samples');
            $table->foreignId('profile_id')->nullable()->constrained('profiles');
            $table->foreignId('type_id')->nullable()->constrained('analysis_categories');
            $table->boolean('status')->default(false);
            $table->date('entry_date')->nullable();
            $table->timestamp('init_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->date('col_date')->nullable();

            $table->index(['id', 'col_date']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Results
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')->nullable()->constrained('samples');
            $table->foreignId('code_id')->nullable()->constrained('lab_codes');
            $table->foreignId('parameter_id')->nullable()->constrained('parameters');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('profile_id')->nullable()->constrained('profiles');
            $table->foreignId('matrix_id')->nullable()->constrained('matrixes');
            $table->foreignId('collection_id')->nullable()->constrained('collection_product');
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->foreignId('protocol_id')->nullable()->constrained('protocols');
            $table->foreignId('standard_id')->nullable()->constrained('standards');
            $table->foreignId('nwp_id')->nullable()->constrained('nwps');
            $table->foreignId('inserted_by_id')->nullable()->constrained('users');
            $table->foreignId('verified_by_id')->nullable()->constrained('users');
            $table->foreignId('approved_by_id')->nullable()->constrained('users');
            $table->foreignId('type_id')->nullable()->constrained('result_categories');
            $table->boolean('status')->default(false);
            $table->boolean('count')->default(true);
            $table->boolean('requested_counter_analysis')->default(false);
            $table->string('inserted_by')->nullable();
            $table->string('verified_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('inserted_value')->nullable();
            $table->string('verified_value')->nullable();
            $table->string('approved_value')->nullable();
            $table->string('uncertainty_value')->nullable();
            $table->string('min_ref_value')->nullable();
            $table->string('max_ref_value')->nullable();
            $table->nullableMorphs('resultable');
            $table->json('extra_data')->nullable();
            $table->timestamp('inserted_date')->nullable();
            $table->timestamp('verified_date')->nullable();
            $table->timestamp('approved_date')->nullable();
            $table->string('unit_label')->nullable();
            $table->string('protocol_label')->nullable();
            $table->string('standard_label')->nullable();
            $table->string('nwp_label')->nullable();
            $table->string('parameter_label')->nullable();
            $table->string('code_label')->nullable();
            $table->string('category_label')->nullable();
            $table->index(['id', 'collection_id']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Counter Analysis
        Schema::create('counter_analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->nullable()->constrained('results');
            $table->foreignId('analysis_id')->nullable()->constrained('analysis');
            $table->foreignId('cl_id')->nullable()->constrained('lab_codes');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->foreignId('sample_id')->nullable()->constrained('samples');
            $table->foreignId('profile_id')->nullable()->constrained('profiles');
            $table->foreignId('parameter_id')->nullable()->constrained('parameters');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('type_id')->nullable()->constrained('analysis_categories');
            $table->boolean('status')->default(false);
            $table->date('entry_date')->nullable();
            $table->timestamp('init_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->date('col_date')->nullable();
            $table->json('extra_data')->nullable();

            $table->index(['id', 'col_date']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Quality Certificates
        Schema::create('quality_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('collection_id')->nullable()->constrained('collection_product');
            $table->foreignId('cl_id')->nullable()->constrained('lab_codes');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->string('code')->nullable();
            $table->boolean('status')->default(false);
            $table->longText('obs')->nullable();
            $table->string('file_path')->nullable();
            $table->json('extra_data')->nullable();

            $table->index(['id', 'code']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Import Certificates
        Schema::create('import_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('importer_id')->nullable()->constrained('customers');
            $table->foreignId('importer_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('exporter_id')->nullable()->constrained('customers');
            $table->foreignId('exporter_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            $table->string('code')->nullable();
            $table->string('entry_port')->nullable();
            $table->string('exit_port')->nullable();
            $table->string('destination')->nullable();
            $table->string('authorization')->nullable();
            $table->decimal('freight_cost', 10, 2)->default(0);
            $table->decimal('insurance_cost', 10, 2)->default(0);
            $table->decimal('tax_cost', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->boolean('invoiced')->default(false);
            $table->longText('obs')->nullable();
            $table->string('file_path')->nullable();
            $table->json('extra_data')->nullable();

            $table->index(['id', 'code']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Import Certificate Items
        Schema::create('importcert_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->nullable()->constrained('import_certificates');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->decimal('qty', 10, 2)->nullable();
            $table->string('origin')->nullable();
            $table->date('validity')->nullable();
            $table->string('lot')->nullable();
            $table->string('bl_no')->nullable();
            $table->json('extra_data')->nullable();

            $table->index(['id', 'origin']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Export Certificates
        Schema::create('export_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('exporter_id')->nullable()->constrained('customers');
            $table->foreignId('exporter_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->foreignId('transport_id')->nullable()->constrained('trans_categories');
            $table->string('code')->nullable();
            $table->string('origin_country')->nullable();
            $table->string('origin_city')->nullable();
            $table->string('destination_country')->nullable();
            $table->string('destination_city')->nullable();
            $table->date('expedition_date')->nullable();
            $table->string('expedition_location')->nullable();
            $table->date('date')->nullable();

            $table->string('authorization')->nullable();
            $table->boolean('invoiced')->default(false);
            $table->longText('obs')->nullable();
            $table->string('file_path')->nullable();
            $table->json('extra_data')->nullable();

            $table->index(['id', 'code']);
            $table->softDeletes();
            $table->timestamps();
        });


        // Export Certificate Items
        Schema::create('exportcert_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->nullable()->constrained('export_certificates');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->decimal('qty', 10, 2)->nullable();
            $table->json('extra_data')->nullable();

            $table->index(['id', 'product_id']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
