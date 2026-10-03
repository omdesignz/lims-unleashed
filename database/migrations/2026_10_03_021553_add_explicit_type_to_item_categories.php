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
        Schema::table('item_categories', function (Blueprint $table) {
            $table->string('inventory_type', 20)->nullable()->index();
        });
        Schema::create('inventory_category_usage', function (Blueprint $table): void {
            $table->foreignId('category_id')->primary()->constrained('item_categories')->restrictOnDelete();
            $table->timestampTz('first_used_at')->useCurrent();
        });
        DB::statement("ALTER TABLE item_categories ALTER COLUMN inventory_type SET DEFAULT 'material'");
        DB::statement("ALTER TABLE item_categories ADD CONSTRAINT item_categories_inventory_type_check CHECK (inventory_type IN ('material', 'equipment'))");
        DB::unprepared(<<<'SQL'
            CREATE FUNCTION guard_inventory_category_type() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF OLD.inventory_type IS NULL AND NEW.inventory_type IS DISTINCT FROM
                    (CASE WHEN OLD.id = 1 THEN 'equipment' ELSE 'material' END) THEN
                    RAISE EXCEPTION 'Legacy category classification must be initialized without changing its type' USING ERRCODE = '23514';
                END IF;
                IF OLD.inventory_type IS NOT NULL AND NEW.inventory_type IS DISTINCT FROM OLD.inventory_type
                    AND EXISTS (SELECT 1 FROM inventory_category_usage WHERE category_id = OLD.id) THEN
                    RAISE EXCEPTION 'Inventory category type is locked after first use' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER guard_inventory_category_type BEFORE UPDATE OF inventory_type ON item_categories
                FOR EACH ROW EXECUTE FUNCTION guard_inventory_category_type();
            CREATE FUNCTION retain_inventory_category_usage() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                old_category_id bigint;
                new_category_id bigint;
            BEGIN
                IF TG_OP <> 'INSERT' THEN old_category_id := OLD.category_id; END IF;
                IF TG_OP <> 'DELETE' THEN new_category_id := NEW.category_id; END IF;
                PERFORM id FROM item_categories WHERE id IN (old_category_id, new_category_id) ORDER BY id FOR UPDATE;
                INSERT INTO inventory_category_usage (category_id)
                    SELECT DISTINCT category_id FROM (VALUES (old_category_id), (new_category_id)) AS used(category_id)
                    WHERE category_id IS NOT NULL ON CONFLICT DO NOTHING;
                IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER retain_inventory_category_usage BEFORE INSERT OR UPDATE OF category_id OR DELETE ON i_items
                FOR EACH ROW EXECUTE FUNCTION retain_inventory_category_usage();
            CREATE FUNCTION guard_inventory_category_usage() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Inventory category usage evidence is immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER guard_inventory_category_usage BEFORE UPDATE OR DELETE ON inventory_category_usage
                FOR EACH ROW EXECUTE FUNCTION guard_inventory_category_usage();
            CREATE TRIGGER guard_inventory_category_usage_truncate BEFORE TRUNCATE ON inventory_category_usage
                FOR EACH STATEMENT EXECUTE FUNCTION guard_inventory_category_usage();
            CREATE TRIGGER guard_inventory_item_truncate BEFORE TRUNCATE ON i_items
                FOR EACH STATEMENT EXECUTE FUNCTION guard_inventory_category_usage();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('inventory_category_usage')->exists()) {
            throw new RuntimeException('Cannot remove category types while retained first-use evidence exists.');
        }
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS guard_inventory_item_truncate ON i_items;
            DROP TRIGGER IF EXISTS guard_inventory_category_usage_truncate ON inventory_category_usage;
            DROP TRIGGER guard_inventory_category_usage ON inventory_category_usage;
            DROP FUNCTION guard_inventory_category_usage();
            DROP TRIGGER retain_inventory_category_usage ON i_items;
            DROP FUNCTION retain_inventory_category_usage();
            DROP TRIGGER guard_inventory_category_type ON item_categories;
            DROP FUNCTION guard_inventory_category_type();
            ALTER TABLE item_categories DROP CONSTRAINT item_categories_inventory_type_check;
            SQL);
        Schema::table('item_categories', function (Blueprint $table) {
            $table->dropColumn('inventory_type');
        });
        Schema::dropIfExists('inventory_category_usage');
    }
};
