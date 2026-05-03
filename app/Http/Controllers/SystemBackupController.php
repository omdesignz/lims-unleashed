<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StandardRequest;
use App\Http\Resources\StandardResource;
use App\Jobs\CreateBackup;
use Illuminate\Support\Facades\DB;
use App\Models\Standard;
use App\Rules\BackupDisk;
use App\Rules\PathToZip;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Helpers\Format;
use Spatie\Backup\Config\Config;


class SystemBackupController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     */
    public function backups()
    {
        abort_if( !auth()->user()->can('view_settings'), 403, '');

        return Inertia::render('Backups/Index', []);
    }
    
     /**
     * Display a listing of the resource.
     *
     */

    public function index(Request $request)
    {
        $validated = $request->validate([
            'disk' => ['nullable', new BackupDisk()],
        ]);

        $disk = $validated['disk'] ?? collect(config('backup.backup.destination.disks'))->first();

        abort_unless($disk, 422, 'No backup disk is configured.');

        $backupDestination = BackupDestination::create($disk, config('backup.backup.name'));

        return Cache::remember("backups-{$disk}", now()->addSeconds(4), function () use ($backupDestination) {
            return $backupDestination
                ->backups()
                ->map(function (Backup $backup) {
                    $size = method_exists($backup, 'sizeInBytes') ? $backup->sizeInBytes() : $backup->size();

                    return [
                        'path' => $backup->path(),
                        'date' => $backup->date()->format('Y-m-d H:i:s'),
                        'size' => Format::humanReadableSize($size),
                    ];
                })
                ->toArray();
        });
    }

    public function create(Request $request)
    {
        abort_if( !auth()->user()->can('edit_settings'), 403, '');

        $option = $request->input('option', '');

        dispatch(new CreateBackup($option))
            ->onQueue(config('queue.default'));

        return redirect()->back()->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => trans('gestlab.toasts.record_successfully_created'),
                ]
            ]);   
    }

    public function delete(Request $request)
    {
        abort_if( !auth()->user()->can('edit_settings'), 403, '');

        $validated = $request->validate([
            'disk' => new BackupDisk(),
            'path' => ['required', new PathToZip()],
        ]);

        $backupDestination = BackupDestination::create($validated['disk'], config('backup.backup.name'));

        $backupDestination
            ->backups()
            ->first(function (Backup $backup) use ($validated) {
                return $backup->path() === $validated['path'];
            })
            ->delete();

            return redirect()->back()->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => trans('gestlab.toasts.record_successfully_deleted'),
                ]
            ]);

        // $this->respondSuccess();
    }

}
