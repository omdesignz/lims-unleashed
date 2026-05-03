<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('proposal_templates', 'category')) {
            Schema::table('proposal_templates', function (Blueprint $table) {
                $table->string('category')->default('general')->after('name');
            });
        }

        if (! Schema::hasColumn('proposal_templates', 'description')) {
            Schema::table('proposal_templates', function (Blueprint $table) {
                $table->text('description')->nullable()->after('content');
            });
        }

        if (! Schema::hasColumn('proposal_templates', 'is_active')) {
            Schema::table('proposal_templates', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('user_id');
            });
        }

        if (! Schema::hasColumn('proposal_templates', 'theme_preset')) {
            Schema::table('proposal_templates', function (Blueprint $table) {
                $table->string('theme_preset')->nullable()->after('is_active');
            });
        }

        if (! Schema::hasColumn('proposal_templates', 'layout_schema')) {
            Schema::table('proposal_templates', function (Blueprint $table) {
                $table->json('layout_schema')->nullable()->after('theme_preset');
            });
        }

        if (! Schema::hasColumn('proposal_templates', 'export_settings')) {
            Schema::table('proposal_templates', function (Blueprint $table) {
                $table->json('export_settings')->nullable()->after('layout_schema');
            });
        }
    }

    public function down(): void
    {
        $columns = [
            'category',
            'description',
            'is_active',
            'theme_preset',
            'layout_schema',
            'export_settings',
        ];

        $existingColumns = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn('proposal_templates', $column)
        ));

        if ($existingColumns === []) {
            return;
        }

        Schema::table('proposal_templates', function (Blueprint $table) use ($existingColumns) {
            $table->dropColumn($existingColumns);
        });
    }
};
