<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parameters', function (Blueprint $table): void {
            $table->index(['deleted_at', 'created_at'], 'parameters_export_status_date_index');
            $table->index(['active', 'deleted_at'], 'parameters_export_active_index');
        });
        Schema::table('profiles', fn (Blueprint $table) => $table->index(['deleted_at', 'created_at'], 'profiles_export_status_date_index'));
        Schema::table('matrixes', fn (Blueprint $table) => $table->index(['deleted_at', 'created_at'], 'matrixes_export_status_date_index'));
        Schema::table('warehouses', fn (Blueprint $table) => $table->index(['deleted_at', 'created_at'], 'warehouses_export_status_date_index'));
        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['customer_id', 'date'], 'invoices_export_customer_date_index');
            $table->index(['deleted_at', 'date'], 'invoices_export_status_date_index');
        });
        Schema::table('quotes', function (Blueprint $table): void {
            $table->index(['customer_id', 'date'], 'quotes_export_customer_date_index');
            $table->index(['converted_to_invoice', 'date'], 'quotes_export_conversion_date_index');
            $table->index(['deleted_at', 'date'], 'quotes_export_status_date_index');
        });
        Schema::table('credit_notes', function (Blueprint $table): void {
            $table->index(['customer_id', 'date'], 'credit_notes_export_customer_date_index');
            $table->index(['reason', 'date'], 'credit_notes_export_reason_date_index');
            $table->index(['deleted_at', 'date'], 'credit_notes_export_status_date_index');
        });
        Schema::table('receipts', function (Blueprint $table): void {
            $table->index(['customer_id', 'date'], 'receipts_export_customer_date_index');
            $table->index(['deleted_at', 'date'], 'receipts_export_status_date_index');
        });
        Schema::table('contract_guides', fn (Blueprint $table) => $table->index(['deleted_at', 'date'], 'contract_guides_export_status_date_index'));
        Schema::table('import_certificates', function (Blueprint $table): void {
            $table->index(['importer_id', 'date'], 'import_cert_export_customer_date_index');
            $table->index(['invoiced', 'date'], 'import_cert_export_invoice_date_index');
            $table->index(['deleted_at', 'date'], 'import_cert_export_status_date_index');
        });
        Schema::table('export_certificates', function (Blueprint $table): void {
            $table->index(['exporter_id', 'date'], 'export_cert_export_customer_date_index');
            $table->index(['invoiced', 'date'], 'export_cert_export_invoice_date_index');
            $table->index(['deleted_at', 'date'], 'export_cert_export_status_date_index');
        });
        Schema::table('quality_certificates', function (Blueprint $table): void {
            $table->index(['customer_id', 'created_at'], 'quality_cert_export_customer_date_index');
            $table->index(['validated_at', 'created_at'], 'quality_cert_export_validation_index');
            $table->index(['deleted_at', 'created_at'], 'quality_cert_export_status_date_index');
        });
        Schema::table('customer_requests', function (Blueprint $table): void {
            $table->index(['customer_id', 'created_at'], 'customer_requests_export_customer_index');
            $table->index(['status', 'priority', 'created_at'], 'customer_requests_export_workflow_index');
            $table->index(['deleted_at', 'created_at'], 'customer_requests_export_status_index');
        });
        Schema::table('occurrences', function (Blueprint $table): void {
            $table->index(['status_id', 'date_reported'], 'occurrences_export_status_date_index');
            $table->index(['deleted_at', 'date_reported'], 'occurrences_export_record_date_index');
        });
    }

    public function down(): void
    {
        $this->drop('parameters', ['parameters_export_status_date_index', 'parameters_export_active_index']);
        $this->drop('profiles', ['profiles_export_status_date_index']);
        $this->drop('matrixes', ['matrixes_export_status_date_index']);
        $this->drop('warehouses', ['warehouses_export_status_date_index']);
        $this->drop('invoices', ['invoices_export_customer_date_index', 'invoices_export_status_date_index']);
        $this->drop('quotes', ['quotes_export_customer_date_index', 'quotes_export_conversion_date_index', 'quotes_export_status_date_index']);
        $this->drop('credit_notes', ['credit_notes_export_customer_date_index', 'credit_notes_export_reason_date_index', 'credit_notes_export_status_date_index']);
        $this->drop('receipts', ['receipts_export_customer_date_index', 'receipts_export_status_date_index']);
        $this->drop('contract_guides', ['contract_guides_export_status_date_index']);
        $this->drop('import_certificates', ['import_cert_export_customer_date_index', 'import_cert_export_invoice_date_index', 'import_cert_export_status_date_index']);
        $this->drop('export_certificates', ['export_cert_export_customer_date_index', 'export_cert_export_invoice_date_index', 'export_cert_export_status_date_index']);
        $this->drop('quality_certificates', ['quality_cert_export_customer_date_index', 'quality_cert_export_validation_index', 'quality_cert_export_status_date_index']);
        $this->drop('customer_requests', ['customer_requests_export_customer_index', 'customer_requests_export_workflow_index', 'customer_requests_export_status_index']);
        $this->drop('occurrences', ['occurrences_export_status_date_index', 'occurrences_export_record_date_index']);
    }

    /**
     * @param  array<int, string>  $indexes
     */
    private function drop(string $tableName, array $indexes): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($indexes): void {
            foreach ($indexes as $index) {
                $table->dropIndex($index);
            }
        });
    }
};
