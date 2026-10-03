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
            $this->ensureEmpty();
            Schema::table('document_deliveries', function (Blueprint $table): void {
                $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
                $table->uuid('attempt_id')->nullable();
                $table->timestamp('dispatch_started_at')->nullable();
                $table->index(['lab_id', 'document_type', 'document_id']);
            });
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $this->ensureEmpty();
            Schema::table('document_deliveries', function (Blueprint $table): void {
                $table->dropIndex(['lab_id', 'document_type', 'document_id']);
                $table->dropConstrainedForeignId('lab_id');
                $table->dropColumn(['attempt_id', 'dispatch_started_at']);
            });
        });
    }

    private function ensureEmpty(): void
    {
        DB::statement('LOCK TABLE document_deliveries IN ACCESS EXCLUSIVE MODE');
        if (DB::table('document_deliveries')->exists()) {
            throw new RuntimeException('Resolve retained delivery ownership before changing its schema. No delivery evidence will be reassigned or removed.');
        }
    }
};
