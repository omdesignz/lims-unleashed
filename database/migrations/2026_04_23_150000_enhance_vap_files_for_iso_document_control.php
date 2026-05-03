<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('v_files', function (Blueprint $table) {
            $table->string('document_number')->nullable()->after('name');
            $table->string('document_type')->nullable()->after('type');
            $table->string('category')->nullable()->after('document_type');
            $table->string('revision_code')->nullable()->after('category');
            $table->enum('status', ['draft', 'in_review', 'approved', 'effective', 'obsolete', 'archived'])
                ->default('draft')
                ->after('content');
            $table->enum('confidentiality_level', ['public', 'internal', 'confidential', 'restricted'])
                ->default('internal')
                ->after('status');
            $table->boolean('is_controlled')->default(true)->after('confidentiality_level');
            $table->boolean('requires_periodic_review')->default(true)->after('is_controlled');
            $table->unsignedInteger('retention_period_days')->nullable()->after('requires_periodic_review');
            $table->timestamp('effective_at')->nullable()->after('retention_period_days');
            $table->timestamp('review_due_at')->nullable()->after('effective_at');
            $table->timestamp('approved_at')->nullable()->after('review_due_at');
            $table->timestamp('obsolete_at')->nullable()->after('approved_at');
            $table->foreignId('owner_id')->nullable()->after('obsolete_at')->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('owner_id')->constrained('users')->nullOnDelete();
            $table->uuid('superseded_by')->nullable()->after('approved_by');
            $table->text('change_reason')->nullable()->after('superseded_by');
            $table->json('meta')->nullable()->after('change_reason');

            $table->foreign('superseded_by')->references('id')->on('v_files')->nullOnDelete();
            $table->unique(['parent_id', 'document_number']);
            $table->index(['status', 'review_due_at']);
            $table->index(['owner_id', 'status']);
        });

        Schema::table('v_file_versions', function (Blueprint $table) {
            $table->string('revision_code')->nullable()->after('file_id');
            $table->string('mime_type')->nullable()->after('content');
            $table->unsignedBigInteger('size')->nullable()->after('mime_type');
            $table->string('checksum', 64)->nullable()->after('size');
            $table->text('change_reason')->nullable()->after('comment');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });

        Schema::table('v_file_versions', function (Blueprint $table) {
            $table->dropColumn([
                'revision_code',
                'mime_type',
                'size',
                'checksum',
                'change_reason',
            ]);
        });

        Schema::table('v_files', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['superseded_by']);
            $table->dropUnique(['parent_id', 'document_number']);
            $table->dropIndex(['status', 'review_due_at']);
            $table->dropIndex(['owner_id', 'status']);

            $table->dropColumn([
                'document_number',
                'document_type',
                'category',
                'revision_code',
                'status',
                'confidentiality_level',
                'is_controlled',
                'requires_periodic_review',
                'retention_period_days',
                'effective_at',
                'review_due_at',
                'approved_at',
                'obsolete_at',
                'owner_id',
                'approved_by',
                'superseded_by',
                'change_reason',
                'meta',
            ]);
        });
    }
};
