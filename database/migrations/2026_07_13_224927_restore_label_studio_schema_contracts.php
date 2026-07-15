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
        if (! Schema::hasTable('label_templates')) {
            Schema::create('label_templates', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category');
                $table->string('preview_image')->nullable();
                $table->json('template_data');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_featured')->default(false);
                $table->timestamps();

                $table->index(['category', 'is_active']);
                $table->index(['is_featured', 'is_active']);
            });
        }

        if (! Schema::hasTable('labels')) {
            Schema::create('labels', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('name');
                $table->enum('type', ['equipment', 'material', 'sample', 'custom'])->default('custom');
                $table->text('content');
                $table->decimal('width', 8, 2);
                $table->decimal('height', 8, 2);
                $table->string('background_color', 7)->default('#ffffff');
                $table->string('text_color', 7)->default('#000000');
                $table->integer('font_size')->default(12);
                $table->string('logo_path')->nullable();
                $table->integer('border_width')->default(1);
                $table->string('border_color', 7)->default('#000000');
                $table->json('template_data')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('lab_id')->nullable();
                $table->foreignId('department_id')->nullable()->constrained('departments');
                $table->foreignId('user_id')->nullable()->constrained('users');
                $table->json('text_position')->nullable();
                $table->json('logo_position')->nullable();
                $table->enum('text_alignment', ['left', 'center', 'right', 'justify'])->default('center');
                $table->decimal('logo_size', 5, 2)->nullable();
                $table->boolean('has_qr_code')->default(false);
                $table->string('qr_code_content')->nullable();
                $table->json('qr_code_position')->nullable();
                $table->decimal('qr_code_size', 5, 2)->nullable();
                $table->boolean('has_barcode')->default(false);
                $table->string('barcode_content')->nullable();
                $table->string('barcode_type')->default('CODE128');
                $table->json('barcode_position')->nullable();
                $table->decimal('barcode_width', 5, 2)->nullable();
                $table->decimal('barcode_height', 5, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // These tables predate this migration in established installations.
    }
};
