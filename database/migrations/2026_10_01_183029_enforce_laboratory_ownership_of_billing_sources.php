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
        DB::transaction(function (): void {
            $this->lockTables();
            $this->ensureEmpty();
            foreach ($this->parents() as $name) {
                Schema::table($name, function (Blueprint $table) use ($name): void {
                    $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
                    $table->unique(['id', 'lab_id'], $name.'_id_lab_unique');
                    $table->index(['lab_id', 'date', 'id']);
                    $table->foreign(['invoice_id', 'lab_id'], $name.'_invoice_lab_foreign')
                        ->references(['id', 'lab_id'])->on('invoices')->restrictOnDelete();
                });
            }
            foreach ($this->children() as $name => [$column, $parent]) {
                Schema::table($name, function (Blueprint $table) use ($name, $column, $parent): void {
                    $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
                    $table->index(['lab_id', 'id']);
                    $table->unsignedBigInteger($column)->nullable(false)->change();
                    $table->foreign([$column, 'lab_id'], $name.'_parent_lab_foreign')
                        ->references(['id', 'lab_id'])->on($parent)->restrictOnDelete();
                });
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            $this->lockTables();
            $this->ensureEmpty();
            foreach ($this->children() as $name => [$column, $parent]) {
                Schema::table($name, function (Blueprint $table) use ($name, $column): void {
                    $table->dropForeign($name.'_parent_lab_foreign');
                    $table->unsignedBigInteger($column)->nullable()->change();
                    $table->dropIndex(['lab_id', 'id']);
                    $table->dropConstrainedForeignId('lab_id');
                });
            }
            foreach ($this->parents() as $name) {
                Schema::table($name, function (Blueprint $table) use ($name): void {
                    $table->dropForeign($name.'_invoice_lab_foreign');
                    $table->dropIndex(['lab_id', 'date', 'id']);
                    $table->dropUnique($name.'_id_lab_unique');
                    $table->dropConstrainedForeignId('lab_id');
                });
            }
        });
    }

    /** @return list<string> */
    private function parents(): array
    {
        return ['quotes', 'import_certificates', 'export_certificates'];
    }

    /** @return array<string, array{string, string}> */
    private function children(): array
    {
        return [
            'quote_items' => ['quote_id', 'quotes'],
            'import_certificate_items' => ['certificate_id', 'import_certificates'],
            'export_certificate_items' => ['certificate_id', 'export_certificates'],
        ];
    }

    private function lockTables(): void
    {
        DB::statement('LOCK TABLE export_certificate_items, export_certificates, import_certificate_items, import_certificates, quote_items, quotes IN ACCESS EXCLUSIVE MODE');
    }

    private function ensureEmpty(): void
    {
        foreach ([...$this->parents(), ...array_keys($this->children())] as $name) {
            if (DB::table($name)->exists()) {
                throw new RuntimeException('Resolve retained billing source ownership before changing its laboratory schema. Records will not be reassigned or removed.');
            }
        }
    }
};
