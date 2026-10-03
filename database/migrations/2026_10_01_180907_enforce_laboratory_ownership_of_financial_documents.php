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
            foreach (['invoices', 'credit_notes', 'receipts'] as $name) {
                Schema::table($name, function (Blueprint $table) use ($name): void {
                    $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
                    $table->unique(['id', 'lab_id'], $name.'_id_lab_unique');
                    $table->index(['lab_id', 'date', 'id']);
                });
            }
            foreach (['credit_notes', 'receipts'] as $name) {
                Schema::table($name, function (Blueprint $table) use ($name): void {
                    $table->foreign(['invoice_id', 'lab_id'], $name.'_invoice_lab_foreign')
                        ->references(['id', 'lab_id'])->on('invoices')->restrictOnDelete();
                });
            }
            foreach ($this->children() as $name => $parents) {
                Schema::table($name, function (Blueprint $table) use ($name, $parents): void {
                    $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
                    $table->index(['lab_id', 'id']);
                    foreach ($parents as $column => $parent) {
                        $table->unsignedBigInteger($column)->nullable(false)->change();
                        $table->foreign([$column, 'lab_id'], $name.'_'.$column.'_lab_foreign')
                            ->references(['id', 'lab_id'])->on($parent)->restrictOnDelete();
                    }
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
            foreach ($this->children() as $name => $parents) {
                Schema::table($name, function (Blueprint $table) use ($name, $parents): void {
                    foreach ($parents as $column => $parent) {
                        $table->dropForeign($name.'_'.$column.'_lab_foreign');
                        $table->unsignedBigInteger($column)->nullable()->change();
                    }
                    $table->dropIndex(['lab_id', 'id']);
                    $table->dropConstrainedForeignId('lab_id');
                });
            }
            foreach (['credit_notes', 'receipts'] as $name) {
                Schema::table($name, fn (Blueprint $table) => $table->dropForeign($name.'_invoice_lab_foreign'));
            }
            foreach (['receipts', 'credit_notes', 'invoices'] as $name) {
                Schema::table($name, function (Blueprint $table) use ($name): void {
                    $table->dropIndex(['lab_id', 'date', 'id']);
                    $table->dropUnique($name.'_id_lab_unique');
                    $table->dropConstrainedForeignId('lab_id');
                });
            }
        });
    }

    /** @return array<string, array<string, string>> */
    private function children(): array
    {
        return [
            'invoice_items' => ['invoice_id' => 'invoices'],
            'credit_note_items' => ['note_id' => 'credit_notes'],
            'invoice_receipt' => ['invoice_id' => 'invoices', 'receipt_id' => 'receipts'],
        ];
    }

    private function ensureEmpty(): void
    {
        foreach (['invoices', 'credit_notes', 'receipts', ...array_keys($this->children())] as $name) {
            if (DB::table($name)->exists()) {
                throw new RuntimeException('Resolve retained financial document ownership before changing its laboratory schema. Records will not be reassigned or removed.');
            }
        }
    }

    private function lockTables(): void
    {
        DB::statement('LOCK TABLE credit_note_items, credit_notes, invoice_items, invoice_receipt, invoices, receipts IN ACCESS EXCLUSIVE MODE');
    }
};
