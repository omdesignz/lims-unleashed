<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Jobs\ImportMaintenanceTasksChunk;
use Illuminate\Support\LazyCollection;
use League\Csv\Reader;

class MaintenanceTaskImportController extends Controller
{
    public function form()
    {
        return inertia('MaintenanceTasks/maintenance-tasks-import-form');
    }

    public function upload(Request $request)
    {
        // Validate uploaded file
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:51200', // max 50MB
        ]);

        // Store uploaded file in storage/app/imports
        $path = $request->file('file')->storeAs('imports', now()->format('Y-m-d') . '_' . now()->format('H-i-s') . '.csv');

        $filePath = Storage::path($path);

        // // Open file and validate CSV header
        // $handle = fopen($filePath, 'r');
        // if (!$handle) {
        //     return back()->withErrors(['file' => 'Unable to open uploaded file.']);
        // }

        // Use league/csv to read the file
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setDelimiter(';');  // <-- Set delimiter to semicolon
        $csv->setHeaderOffset(0); // First row as header

        // $header = fgetcsv($handle);
        // fclose($handle);

        // Define expected CSV columns in exact order
        $expectedColumns = [
            'equipment_id',
            'seq',
            'category_id',
            'range',
            'calibration_points',
            'acceptance_criteria',
            'periodicity_unit',
            'previous_date',
            'next_date',
            'supplier_id',
            'result',
            'calibration_certificate_no',
            'calibration_status',
            'obs',
            'maintenance_task_year',
            'maintenance_task_no',

        ];

        $header = $csv->getHeader();

        // dd($header);

        if ($header !== $expectedColumns) {
            Storage::delete($path);
            return back()->withErrors([
                'file' => 'Os dados não correspondem ao formato esperado. Por favor, use o modelo correto.',
            ]);
        }

        // Use LazyCollection to chunk records for queue jobs
        $records = LazyCollection::make(function () use ($csv) {
            foreach ($csv->getRecords() as $record) {
                // $record is an associative array with header keys
                yield array_values($record); // Convert to indexed array if your job expects it
            }
        });

        // dd($records);

        $chunkSize = 1000; // Tune chunk size based on memory and queue worker capacity
        $jobs = [];

        foreach ($records->chunk($chunkSize) as $chunk) {
            $jobs[] = new ImportMaintenanceTasksChunk($chunk->all());
        }

        // Dispatch batch of jobs
        $batch = Bus::batch($jobs)
            ->then(function () {
                // Optional: Notify user on success, e.g. via Notification
            })
            ->catch(function () {
                // Optional: Notify user on failure
            })
            ->finally(function () use ($path) {
                // Optional: Delete uploaded file after import finishes
                Storage::delete($path);
            })
            ->dispatch();


        return redirect()->route('maintenancetasks.import.progress', ['batchId' => $batch->id]);

        // Return Inertia response with batch ID for frontend progress tracking
        // return inertia()->render('ImportProgress', [
        //     'batchId' => $batch->id,
        // ]);
    }


    public function progress($batchId)
    {
        return inertia()->render('ImportProgress', ['batchId' => $batchId]); 
    }
}
