<?php

namespace App\Jobs;

use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceTask;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImportMaintenanceTasksChunk implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $rows;

    /**
     * Create a new job instance.
     *
     * @param  array  $rows  Array of CSV rows (each row is an array of column values)
     */
    public function __construct(array $rows, public readonly int $labId, public readonly int $userId)
    {
        $this->rows = $rows;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        abort_unless(User::query()->whereKey($this->userId)->where('is_active', true)->whereNotNull('email_verified_at')->exists()
            && DB::table('lab_user')->where('lab_id', $this->labId)->where('user_id', $this->userId)->exists(), 403);
        $tasks = [];

        // dd($this->rows);

        foreach ($this->rows as $row) {
            // Map CSV row columns to database fields
            $data = $this->mapRow($row);

            // Validate row data
            $validator = Validator::make($data, [
                'equipment_id' => 'required|integer|exists:i_items,id',
                'category_id' => 'required|integer|exists:maintenance_categories,id',
                'supplier_id' => 'required|integer|exists:i_suppliers,id',
                'name' => 'required|string|max:255',
                'due_date' => 'required|date',
                'maintenance_task_year' => 'required|digits:4',
            ]);

            if ($validator->fails()) {
                // Log invalid row and skip it
                Log::warning('Invalid CSV row skipped during import', [
                    'errors' => $validator->errors()->all(),
                    'row' => $row,
                ]);

                continue;
            }

            $tasks[] = $data;
        }

        if ($tasks !== []) {
            DB::transaction(function () use ($tasks): void {
                foreach ($tasks as $task) {
                    MaintenanceTask::query()->create($task);
                }
            });
        }
    }

    /**
     * Map CSV row columns to occurrence table columns, resolving foreign keys.
     *
     * Assumes CSV columns order matches your migration structure, adjust indexes accordingly.
     */
    private function mapRow(array $row): array
    {
        $dueDate = $this->parseDate($row[8] ?? null);

        return [
            'name' => isset($row[0]) ? 'Manutenção de '.$row[0] : null,
            'due_date' => $dueDate,
            'range' => $row[3] ?? null,
            'calibration_points' => $row[4] ?? null,
            'acceptance_criteria' => $row[5] ?? null,
            'periodicity_unit' => $row[6] ?? null,
            'category_id' => isset($row[2]) && ctype_digit((string) $row[2]) ? (int) $row[2] : null,
            'previous_date' => $this->parseDate($row[7] ?? null),
            'next_date' => $this->parseDate($row[8] ?? null),
            'result' => $row[10] ?? null,
            'calibration_certificate_no' => $row[11] ?? null,
            'calibration_status' => $row[12] ?? null,
            'obs' => $row[13] ?? null,
            'maintenance_task_year' => $dueDate ? substr($dueDate, 0, 4) : null,

            // Foreign keys resolved by helper methods with caching
            'equipment_id' => $this->resolveEquipmentId($row[0] ?? null),
            'supplier_id' => $this->resolveSupplierId($row[9] ?? null),

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

        return InventoryItem::forLaboratory($this->labId)
            ->where('internal_code', $name)
            ->value('id');
    }

    /**
     * Resolve occurrence category name to ID with caching.
     */
    private function resolveCategoryId(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        return Cache::rememberForever('category_id_'.Str::slug($name), function () use ($name) {
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

        return InventoryItemSupplier::query()->where('name', $name)->value('id');
    }
}
