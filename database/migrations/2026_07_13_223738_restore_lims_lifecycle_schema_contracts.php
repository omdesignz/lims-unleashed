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
        $this->restoreCollectionProductColumns();
        $this->restoreQualityCertificateColumns();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // These columns predate the migration in some installations, so rollback must not remove operational data.
    }

    private function restoreCollectionProductColumns(): void
    {
        $missingColumns = collect([
            'quote_id',
            'sampling_plan_ref',
            'customer_submitted_info',
            'sample_status',
            'collected_by_lab',
            'analysis_start_date',
            'analysis_end_date',
            'quoted',
        ])->filter(fn (string $column): bool => ! Schema::hasColumn('collection_product', $column));

        if ($missingColumns->isEmpty()) {
            return;
        }

        Schema::table('collection_product', function (Blueprint $table) use ($missingColumns): void {
            if ($missingColumns->contains('quote_id')) {
                $table->foreignId('quote_id')->nullable()->constrained('quotes');
            }

            if ($missingColumns->contains('sampling_plan_ref')) {
                $table->string('sampling_plan_ref')->nullable();
            }

            if ($missingColumns->contains('customer_submitted_info')) {
                $table->string('customer_submitted_info')->nullable();
            }

            if ($missingColumns->contains('sample_status')) {
                $table->string('sample_status')->nullable();
            }

            if ($missingColumns->contains('collected_by_lab')) {
                $table->boolean('collected_by_lab')->default(true);
            }

            if ($missingColumns->contains('analysis_start_date')) {
                $table->date('analysis_start_date')->nullable();
            }

            if ($missingColumns->contains('analysis_end_date')) {
                $table->date('analysis_end_date')->nullable();
            }

            if ($missingColumns->contains('quoted')) {
                $table->boolean('quoted')->nullable()->default(false);
            }
        });
    }

    private function restoreQualityCertificateColumns(): void
    {
        $missingColumns = collect([
            'product_id',
            'validated_by',
            'validated_by_id',
            'validated_at',
            'validated_on_behalf_of',
            'validated_on_behalf_of_id',
        ])->filter(fn (string $column): bool => ! Schema::hasColumn('quality_certificates', $column));

        if ($missingColumns->isEmpty()) {
            return;
        }

        Schema::table('quality_certificates', function (Blueprint $table) use ($missingColumns): void {
            if ($missingColumns->contains('product_id')) {
                $table->foreignId('product_id')->nullable()->constrained('products');
            }

            if ($missingColumns->contains('validated_by')) {
                $table->string('validated_by')->nullable();
            }

            if ($missingColumns->contains('validated_by_id')) {
                $table->foreignId('validated_by_id')->nullable()->constrained('users');
            }

            if ($missingColumns->contains('validated_at')) {
                $table->timestamp('validated_at')->nullable();
            }

            if ($missingColumns->contains('validated_on_behalf_of')) {
                $table->boolean('validated_on_behalf_of')->default(false);
            }

            if ($missingColumns->contains('validated_on_behalf_of_id')) {
                $table->foreignId('validated_on_behalf_of_id')->nullable()->constrained('users');
            }
        });
    }
};
