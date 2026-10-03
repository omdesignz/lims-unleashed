<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $this->assertUnlinkedItems();
            foreach ($this->tables() as $tableName => $legacyConstraint) {
                Schema::table($tableName, function (Blueprint $table) use ($legacyConstraint): void {
                    $table->dropForeign($legacyConstraint);
                    $table->foreign('product_id')->references('id')->on('phytosanitary_products')->restrictOnDelete();
                });
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $this->assertUnlinkedItems();
            foreach ($this->tables() as $tableName => $legacyConstraint) {
                Schema::table($tableName, function (Blueprint $table) use ($legacyConstraint): void {
                    $table->dropForeign(['product_id']);
                    $table->foreign('product_id', $legacyConstraint)->references('id')->on('products');
                });
            }
        });
    }

    /** @return array<string, string> */
    private function tables(): array
    {
        return ['import_certificate_items' => 'importcert_items_product_id_foreign',
            'export_certificate_items' => 'exportcert_items_product_id_foreign'];
    }

    private function assertUnlinkedItems(): void
    {
        DB::statement('LOCK TABLE export_certificate_items, import_certificate_items IN ACCESS EXCLUSIVE MODE');
        foreach (array_keys($this->tables()) as $tableName) {
            if (DB::table($tableName)->whereNotNull('product_id')->exists()) {
                throw new RuntimeException('Cannot change certificate product catalogs while retained product links exist. Review and map those records explicitly.');
            }
        }
    }
};
