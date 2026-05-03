<?php

namespace App\Jobs;

use App\Models\InventoryItem;
use App\Models\MaintenanceTask;
use App\Models\MaintenanceCategory;
use App\Models\InventoryItemSupplier;
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

class ImportMaintenanceTasksChunk implements ShouldQueue
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
                'equipment_id' => 'required',
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
            DB::table('maintenance_tasks')->insert($insertData);
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
            'seq' => isset($row[1]) && is_numeric($row[1]) ? (int)$row[1] : null,
            'range' => $row[3] ?? null,
            'calibration_points' => $row[4] ?? null,
            'acceptance_criteria' => $row[5] ?? null,
            'periodicity_unit' => $row[6] ?? null,
            'category_id' => $row[2] ?? null,
            'previous_date' => $this->parseDate($row[7] ?? null),
            'next_date' => $this->parseDate($row[8] ?? null),
            'result' => $row[10] ?? null,
            'calibration_certificate_no' => $row[11] ?? null,
            'calibration_status' => $row[12] ?? null,
            'obs' => $row[13] ?? null,
            'maintenance_task_year' => $row[14] ?? null,
            'maintenance_task_no' => $row[15] ?? null,

            // Foreign keys resolved by helper methods with caching
            'equipment_id' => $this->resolveEquipmentId($row[0] ?? null),
            'supplier_id' => $this->resolveSupplierId($row[9] ?? null),

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

    private function resolveEquipmentId(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        return Cache::rememberForever('equipment_id_' . Str::slug($name), function () use ($name) {
            return InventoryItem::firstOrCreate(['internal_code' => $name])->id;
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
            return MaintenanceCategory::firstOrCreate(['name' => $name])->id;
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

}
