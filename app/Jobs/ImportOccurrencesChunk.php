<?php

namespace App\Jobs;

use App\Models\Occurrence;
use App\Models\Department;
use App\Models\User;
use App\Models\OccurrenceOrigin;
use App\Models\OccurrenceCategory;
use App\Models\OccurrenceStatus;
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

class ImportOccurrencesChunk implements ShouldQueue
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
                'occurrence_year' => 'required',
                'date_reported' => 'required',
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
            DB::table('occurrences')->insert($insertData);
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
        // return [
        //     'occurrence_no' => $row[0] ?: null,
        //     'occurrence_year' => $row[1],
        //     'seq' => is_numeric($row[2]) ? (int)$row[2] : null,
        //     'date_reported' => $this->parseDate($row[3]),
        //     'issue_description' => $row[6],
        //     'notification_date' => $this->parseDate($row[8]),
        //     'client_process_open_notification_date' => $this->parseDate($row[9]),
        //     'analysis' => $row[10] ?: null,
        //     'has_risk_correction_budget' => $this->toBool($row[11]),
        //     'reason_for_no_risk_correction_budget' => $row[12] ?: null,
        //     'has_non_conformity_terms' => $this->toBool($row[13]),
        //     'effect_corrective_actions' => $row[14] ?: null,
        //     'cause_corrective_actions' => $row[15] ?: null,
        //     'implementation_date' => $this->parseDate($row[16]),
        //     'responsible_name' => $row[17] ?: null,
        //     'update_risk_matrix' => $this->toBool($row[18]),
        //     'client_process_close_notification_date' => $this->parseDate($row[19]),
        //     'client_acceptance' => $this->toBool($row[20]),
        //     'date_closed' => $this->parseDate($row[22]),
        //     'was_effective' => $this->toBool($row[23]),
        //     'client_acceptance_comments' => $row[24] ?: null,


        //     // Foreign keys resolved by helper methods with caching
        //     'department_id' => $this->resolveDepartmentId($row[7] ?? null),
        //     'origin_id' => $this->resolveOriginId($row[5] ?? null),
        //     'category_id' => $this->resolveCategoryId($row[4] ?? null),
        //     'status_id' => $this->resolveStatusId($row[21] ?? null),

        //     'created_at' => now(),
        //     'updated_at' => now(),
        //     'deleted_at' => null,
        // ];

        return [
            'occurrence_no' => $row[0] ?? null,
            'occurrence_year' => $row[1] ?? null,
            'seq' => isset($row[2]) && is_numeric($row[2]) ? (int)$row[2] : null,
            'date_reported' => $this->parseDate($row[3] ?? null),
            'issue_description' => $row[6] ?? null,
            'notification_date' => $this->parseDate($row[8] ?? null),
            'client_process_open_notification_date' => $this->parseDate($row[9] ?? null),
            'analysis' => $row[10] ?? null,
            'has_risk_correction_budget' => $this->toBool($row[11] ?? false),
            'reason_for_no_risk_correction_budget' => $row[12] ?? null,
            'has_non_conformity_terms' => $this->toBool($row[13] ?? false),
            'effect_corrective_actions' => $row[14] ?? null,
            'cause_corrective_actions' => $row[15] ?? null,
            'implementation_date' => $this->parseDate($row[16] ?? null),
            'update_risk_matrix' => $this->toBool($row[18] ?? false),
            'client_process_close_notification_date' => $this->parseDate($row[19] ?? null),
            'client_acceptance' => $this->toBool($row[20] ?? false),
            'client_acceptance_comments' => $row[24] ?? null,
            'date_closed' => $this->parseDate($row[22] ?? null),
            'was_effective' => $this->toBool($row[23] ?? false),
            // 'status_id' => isset($row['status_id']) && is_numeric($row['status_id']) ? (int)$row['status_id'] : null,
            'responsible_name' => $row[17] ?? null,

            // Foreign keys resolved by helper methods with caching
            'department_id' => $this->resolveDepartmentId($row[7] ?? null),
            'origin_id' => $this->resolveOriginId($row[5] ?? null),
            'category_id' => $this->resolveCategoryId($row[4] ?? null),
            'status_id' => $this->resolveStatusId($row[21] ?? null),

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
            return OccurrenceStatus::firstOrCreate(['name' => $name])->id;
        });
    }
}
