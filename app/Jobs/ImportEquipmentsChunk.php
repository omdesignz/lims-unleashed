<?php

namespace App\Jobs;

use App\Models\InventoryItem;
use App\Models\Department;
use App\Models\User;
use App\Models\OccurrenceOrigin;
use App\Models\OccurrenceCategory;
use App\Models\ItemStatus;
use App\Models\InventoryItemType;
use App\Models\InventoryItemSupplier;
use App\Models\EquipmentCategory;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ImportEquipmentsChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public array $rows;

    /**
     * Create a new job instance.
     *
     * @param array $rows Array of CSV rows (each row is an array of column values)
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $insertData = [];

        // dd($this->rows);

        foreach ($this->rows as $row) {
            // Map CSV row columns to database fields
            $data = $this->mapRow($row);

            // Validate row data
            $validator = Validator::make($data, [
                'internal_code' => 'required',
            ]);

            if ($validator->fails()) {
                // Log invalid row and skip it
                Log::warning('Invalid CSV row skipped during import', [
                    'errors' => $validator->errors()->all(),
                    'row' => $row,
                ]);
                continue;
            }

            $insertData[] = $data;
        }

        if (count($insertData) > 0) {
            // Insert all valid rows in a single query
            DB::table('i_items')->insert($insertData);
        }
    }

    /**
     * Map CSV row columns to occurrence table columns, resolving foreign keys.
     *
     * Assumes CSV columns order matches your migration structure, adjust indexes accordingly.
     *
     * @param array $row
     * @return array
     */
    private function mapRow(array $row): array
    {
        return [
            'internal_code' => $row[0] ?? null,
            'seq' => isset($row[1]) && is_numeric($row[1]) ? (int)$row[1] : null,
            'category_id' => isset($row[2]) && is_numeric($row[2]) ? (int)$row[2] : null,
            'name' => $row[5] ?? null,
            'description' => $row[6] ?? null,
            'serial_number' => $row[8] ?? null,
            'software' => $row[9] ?? null,
            'firmware' => $row[10] ?? null,
            'location' => $row[11] ?? null,
            'obs' => $row[14] ?? null,
            'range' => $row[15] ?? null,
            'lot' => $row[16] ?? null,
            'acceptance_criteria' => $row[17] ?? null,

            // Foreign keys resolved by helper methods with caching
            'department_id' => $this->resolveDepartmentId($row[12] ?? null),
            'status_id' => $this->resolveStatusId($row[13] ?? null),
            'supplier_id' => $this->resolveSupplierId($row[7] ?? null),
            'type_id' => $this->resolveItemTypeId($row[4] ?? null),
            'eq_cat_id' => $this->resolveEquipmentCategoryId($row[3] ?? null),

            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ];
    }

    /**
     * Convert various truthy/falsy values to boolean.
     */
    private function toBool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Parse date string to Y-m-d format or return null.
     */
    private function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Resolve department name to ID with caching.
     */
    private function resolveDepartmentId(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        return Cache::rememberForever('department_id_' . Str::slug($name), function () use ($name) {
            return Department::firstOrCreate(['name' => $name])->id;
        });
    }

    /**
     * Resolve user email to ID with caching.
     */
    private function resolveUserId(?string $email): ?int
    {
        if (empty($email)) {
            return null;
        }

        return Cache::rememberForever('user_id_' . Str::slug($email), function () use ($email) {
            return User::where('email', $email)->value('id');
        });
    }

    /**
     * Resolve occurrence origin name to ID with caching.
     */
    private function resolveOriginId(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        return Cache::rememberForever('origin_id_' . Str::slug($name), function () use ($name) {
            return OccurrenceOrigin::firstOrCreate(['name' => $name])->id;
        });
    }

    /**
     * Resolve occurrence category name to ID with caching.
     */
    private function resolveCategoryId(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        return Cache::rememberForever('category_id_' . Str::slug($name), function () use ($name) {
            return OccurrenceCategory::firstOrCreate(['name' => $name])->id;
        });
    }

    /**
     * Resolve status name to ID with caching.
     */
    private function resolveStatusId(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        return Cache::rememberForever('status_id_' . Str::slug($name), function () use ($name) {
            return ItemStatus::firstOrCreate(['name' => $name])->id;
        });
    }

    /**
     * Resolve supplier name to ID with caching.
     */
    private function resolveSupplierId(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        return Cache::rememberForever('supplier_id_' . Str::slug($name), function () use ($name) {
            return InventoryItemSupplier::firstOrCreate(['name' => $name])->id;
        });
    }

    /**
     * Resolve equipment category name to ID with caching.
     */
    private function resolveEquipmentCategoryId(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        return Cache::rememberForever('equipment_category_id_' . Str::slug($name), function () use ($name) {
            return EquipmentCategory::firstOrCreate(['name' => $name])->id;
        });
    }

    /**
     * Resolve item type name to ID with caching.
     */
    private function resolveItemTypeId(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        return Cache::rememberForever('item_type_id_' . Str::slug($name), function () use ($name) {
            return InventoryItemType::firstOrCreate(['name' => $name])->id;
        });
    }
}
