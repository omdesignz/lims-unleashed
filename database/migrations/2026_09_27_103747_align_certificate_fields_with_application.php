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
        Schema::table('import_certificates', function (Blueprint $table) {
            $table->renameColumn('code', 'cert_no');
            $table->renameColumn('entry_port', 'port_entry');
            $table->renameColumn('exit_port', 'port_exit');
            $table->renameColumn('authorization', 'authorized_personnel');
            $table->renameColumn('freight_cost', 'cost_freight');
            $table->renameColumn('insurance_cost', 'cost_insurance');
            $table->date('date')->nullable()->index();
            $table->foreignId('trans_type_id')->nullable()->index()->constrained('trans_categories')->restrictOnDelete();
            $table->foreignId('destination_country_id')->nullable()->index()->constrained('countries')->restrictOnDelete();
            $table->decimal('vat', 10, 2)->nullable();
            $table->decimal('vat_cost', 10, 2)->nullable();
            $table->decimal('cost_final', 10, 2)->nullable();
        });

        Schema::table('export_certificates', function (Blueprint $table) {
            $table->renameColumn('code', 'cert_no');
            $table->renameColumn('transport_id', 'trans_type_id');
            $table->renameColumn('authorization', 'authorized_personnel');
            $table->foreignId('country_origin_id')->nullable()->index()->constrained('countries')->restrictOnDelete();
            $table->foreignId('country_destination_id')->nullable()->index()->constrained('countries')->restrictOnDelete();
        });

        Schema::rename('importcert_items', 'import_certificate_items');
        Schema::rename('exportcert_items', 'export_certificate_items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('export_certificate_items', 'exportcert_items');
        Schema::rename('import_certificate_items', 'importcert_items');

        Schema::table('export_certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_origin_id');
            $table->dropConstrainedForeignId('country_destination_id');
            $table->renameColumn('cert_no', 'code');
            $table->renameColumn('trans_type_id', 'transport_id');
            $table->renameColumn('authorized_personnel', 'authorization');
        });

        Schema::table('import_certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trans_type_id');
            $table->dropConstrainedForeignId('destination_country_id');
            $table->dropColumn(['date', 'vat', 'vat_cost', 'cost_final']);
            $table->renameColumn('cert_no', 'code');
            $table->renameColumn('port_entry', 'entry_port');
            $table->renameColumn('port_exit', 'exit_port');
            $table->renameColumn('authorized_personnel', 'authorization');
            $table->renameColumn('cost_freight', 'freight_cost');
            $table->renameColumn('cost_insurance', 'insurance_cost');
        });
    }
};
