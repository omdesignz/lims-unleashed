<?php

namespace App\Http\Controllers;

use App\Exports\ActivityLogExport;
use App\Http\Requests\ExportActivityLogRequest;
use App\Http\Requests\FilterActivityLogRequest;
use App\Http\Resources\SystemActivityResource;
use App\Http\Resources\UserResource;
use App\Models\SystemActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class SystemActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FilterActivityLogRequest $request)
    {
        abort_if(! auth()->user()->can('view_activity_log'), 403, '');

        // dd($request->all());

        $query = Activity::with(['causer' => function ($query) {
            $query->select('id', 'name', 'email');
        }])->latest();

        // Apply filters
        $this->applyFilters($query, $request);

        // Get paginated results
        $activities = $query->paginate($request->get('per_page', 25))
            ->withQueryString();

        // Prepare data for Vue component
        $responseData = [
            'record' => $activities,
            'logNameOptions' => $this->getLogNameOptions(),
            'causerOptions' => $this->getCauserOptions(),
            'subjectOptions' => $this->getSubjectOptions(),
            'eventOptions' => $this->getEventOptions(),
            'propertiesOptions' => $this->getPropertiesOptions(),
        ];

        // Return JSON for Inertia requests, view for regular requests
        // if ($request->wantsJson() || $request->inertia()) {
        //     return response()->json($responseData);
        // }

        return Inertia::render('SystemActivity/Index', $responseData);

        // return Inertia::render('SystemActivity/Index', [
        //     'record' => SystemActivityResource::collection(
        //         Activity::query()
        //                     ->with('causer')
        //                     ->when(request()->input('search'), function($query, $search){
        //                         $query->where('description', 'like', "%{$search}%");
        //                     })
        //                     // ->when(request()->input('filter'), function($query, $filter){
        //                     //     if($filter = 'trashed'){
        //                     //         $query->withTrashed();
        //                     //     }
        //                     // })
        //                     ->when(request()->input('user_id'), function($query, $user_id){
        //                         if(!is_null($user_id)){
        //                             $query->whereCauserType('user')
        //                             ->whereCauserId($user_id);
        //                         }
        //                     })
        //                     ->latest()
        //                     ->paginate(10)
        //                     ->withQueryString()
        //                 ),
        //     'users' => UserResource::collection(User::with('departments')->get()),
        //     'fields' => [
        //         [
        //             'name' => 'Código',
        //             'value' => 'code'
        //         ],
        //         [
        //             'name' => 'Lei',
        //             'value' => 'law'
        //         ],
        //         [
        //             'name' => 'Motivo',
        //             'value' => 'reason'
        //         ],
        //     ],
        //     'model' => SystemActivity::MENU_NAME,
        //     'abilities' => method_exists(SystemActivity::class, 'getAbilities') ? collect(SystemActivity::ABILITIES)->map(function($item){
        //         return $item . '_' . SystemActivity::MENU_NAME;
        //     }) : collect(config('gestlab.default_abilities'))->map(function($item){
        //         return $item . '_' . SystemActivity::MENU_NAME;
        //     }),
        //     'query' => request()->only(['search', 'user_id'])
        // ]);
    }

    /**
     * Show the specified activity log.
     */
    // public function show(Activity $activity)
    // {
    //     if (!auth()->user()->can('view_activity_log')) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     // Load relationships
    //     $activity->load(['causer', 'subject']);

    //     return response()->json([
    //         'activity' => $activity,
    //         'properties_formatted' => $this->formatProperties($activity->properties),
    //     ]);
    // }

    public function show(Activity $activity) // Use route model binding
    {
        if (! auth()->user()->can('view_activity_log')) {
            abort(403, 'Unauthorized action.');
        }

        // Load relationships
        $activity->load(['causer', 'subject']);

        // Get properties and ensure they're properly formatted
        $properties = $activity->properties;

        // If properties is a string, decode it
        if (is_string($properties)) {
            $properties = json_decode($properties, true);
            $jsonError = json_last_error();

            // If JSON decode failed, use original string
            if ($jsonError !== JSON_ERROR_NONE) {
                $properties = $activity->properties;
            }
        }

        // If properties is null or empty, provide default
        if (empty($properties)) {
            $properties = ['No properties available'];
        }

        return response()->json([
            'activity' => $activity,
            'properties_formatted' => $properties,
        ]);
    }

    /**
     * Remove the specified activity log.
     */
    public function destroy(Activity $activity)
    {
        if (! auth()->user()->can('delete_activity_log')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $activity->delete();

            return back();

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete activity log.'),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete all activity logs.
     */
    public function destroyAll(Request $request)
    {
        if (! auth()->user()->can('delete_activity_log')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $count = Activity::count();

            // Use chunk to avoid memory issues with large datasets
            Activity::chunk(1000, function ($activities) {
                $activities->each->delete();
            });

            return response()->json([
                'success' => true,
                'message' => __(':count activity logs deleted successfully.', ['count' => $count]),
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete activity logs.'),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export activity logs to Excel.
     */
    // public function export(ExportActivityLogRequest $request)
    // {
    //     // dd($request->all());

    //     if (!auth()->user()->can('export_activity_log')) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     $query = Activity::with(['causer' => function ($query) {
    //         $query->select('id', 'name', 'email');
    //     }]);

    //     // Apply same filters as index
    //     $this->applyFilters($query, $request);

    //     // Get all results for export
    //     $activities = $query->get();

    //     $filename = 'activity-logs-' . Carbon::now()->format('Y-m-d-His') . '.xlsx';

    //     return Excel::download(new ActivityLogExport($activities), $filename);
    // }
    public function export(ExportActivityLogRequest $request)
    {
        // REMOVE THIS LINE
        // dd($request->all());

        if (! auth()->user()->can('export_activity_log')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Activity::with(['causer' => function ($query) {
            $query->select('id', 'name', 'email');
        }]);

        // Apply same filters as index
        $this->applyFilters($query, $request);

        // Get all results for export
        $activities = $query->get();

        $filename = 'activity-logs-'.Carbon::now()->format('Y-m-d-His').'.xlsx';

        // Fix the export response
        // return (new ActivityLogExport($activities))->download($filename);

        return Excel::download(new ActivityLogExport($activities), $filename);

    }

    /**
     * Get statistics for activity logs.
     */
    public function stats(Request $request)
    {
        if (! auth()->user()->can('view_activity_log')) {
            abort(403, 'Unauthorized action.');
        }

        $stats = [
            'total' => Activity::count(),
            'today' => Activity::whereDate('created_at', Carbon::today())->count(),
            'yesterday' => Activity::whereDate('created_at', Carbon::yesterday())->count(),
            'last_7_days' => Activity::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            'last_30_days' => Activity::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
            'by_log_name' => $this->getStatsByLogName(),
            'by_event' => $this->getStatsByEvent(),
            'by_causer' => $this->getStatsByCauser(),
            'by_hour' => $this->getStatsByHour(),
        ];

        return response()->json($stats);
    }

    /**
     * Get activity log stream for real-time updates.
     */
    public function stream(Request $request)
    {
        if (! auth()->user()->can('view_activity_log')) {
            abort(403, 'Unauthorized action.');
        }

        // Set headers for Server-Sent Events
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $lastId = $request->get('last_id', 0);

        while (true) {
            // Get new activities since last ID
            $newActivities = Activity::where('id', '>', $lastId)
                ->with(['causer' => function ($query) {
                    $query->select('id', 'name', 'email');
                }])
                ->latest()
                ->take(10)
                ->get();

            if ($newActivities->isNotEmpty()) {
                $lastActivity = $newActivities->first();
                $lastId = $lastActivity->id;

                echo 'data: '.json_encode([
                    'activities' => $newActivities,
                    'last_id' => $lastId,
                    'timestamp' => now()->toISOString(),
                ])."\n\n";

                ob_flush();
                flush();
            }

            // Sleep for 5 seconds before checking again
            sleep(5);

            // Break if client disconnected
            if (connection_aborted()) {
                break;
            }
        }
    }

    /**
     * Get cleanup recommendations.
     */
    public function cleanupRecommendations()
    {
        if (! auth()->user()->can('manage_activity_log')) {
            abort(403, 'Unauthorized action.');
        }

        $recommendations = [];

        // Check for old logs
        $oldLogsCount = Activity::where('created_at', '<', Carbon::now()->subMonths(6))->count();
        if ($oldLogsCount > 0) {
            $recommendations[] = [
                'type' => 'old_logs',
                'title' => __('Old Activity Logs'),
                'description' => __('You have :count activity logs older than 6 months.', ['count' => $oldLogsCount]),
                'action' => 'cleanup_old',
                'severity' => 'low',
            ];
        }

        // Check for large logs table
        $totalLogs = Activity::count();
        if ($totalLogs > 10000) {
            $recommendations[] = [
                'type' => 'large_table',
                'title' => __('Large Activity Log Table'),
                'description' => __('Your activity log table has :count entries, consider archiving old data.', ['count' => $totalLogs]),
                'action' => 'archive',
                'severity' => 'medium',
            ];
        }

        // Check for frequent errors
        $errorLogsLastHour = Activity::where('log_name', 'error')
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->count();

        if ($errorLogsLastHour > 10) {
            $recommendations[] = [
                'type' => 'frequent_errors',
                'title' => __('Frequent Errors Detected'),
                'description' => __(':count error logs in the last hour detected.', ['count' => $errorLogsLastHour]),
                'action' => 'investigate_errors',
                'severity' => 'high',
            ];
        }

        return response()->json($recommendations);
    }

    /**
     * Apply filters to the query.
     */
    private function applyFilters($query, $request)
    {
        // Log name filter
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->get('log_name'));
        }

        // Causer filter
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->get('causer_id'))
                ->where('causer_type', User::class);
        }

        // Subject filter
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->get('subject_id'));
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->get('subject_type'));
        }

        // Event filter
        if ($request->filled('event')) {
            $query->where('event', $request->get('event'));
        }

        // Property filter (search in properties JSON)
        if ($request->filled('property')) {
            $property = $request->get('property');
            $query->where('properties', 'LIKE', '%'.$property.'%');
        }

        // Description filter
        if ($request->filled('description')) {
            $query->where('description', 'LIKE', '%'.$request->get('description').'%');
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->get('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->get('end_date'));
        }

        // Batch UUID filter
        if ($request->filled('batch_uuid')) {
            $query->where('batch_uuid', $request->get('batch_uuid'));
        }

        return $query;
    }

    /**
     * Get unique log names for filter options.
     */
    private function getLogNameOptions()
    {
        return Activity::select('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->get()
            ->map(function ($activity) {
                return [
                    'value' => $activity->log_name,
                    'label' => $activity->log_name ?: __('System'),
                ];
            })
            ->toArray();
    }

    /**
     * Get unique causers for filter options.
     */
    private function getCauserOptions()
    {
        return Activity::whereNotNull('causer_id')
            ->with('causer:id,name,email')
            ->select('causer_id')
            ->distinct()
            ->get()
            ->map(function ($activity) {
                return [
                    'value' => $activity->causer_id,
                    'label' => $activity->causer ? $activity->causer->name.' ('.$activity->causer->email.')' : 'Unknown',
                ];
            })
            ->toArray();
    }

    /**
     * Get unique subjects for filter options.
     */
    private function getSubjectOptions()
    {
        return Activity::whereNotNull('subject_type')
            ->select('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->get()
            ->map(function ($activity) {
                return [
                    'value' => $activity->subject_type,
                    'label' => class_basename($activity->subject_type),
                ];
            })
            ->toArray();
    }

    /**
     * Get unique events for filter options.
     */
    private function getEventOptions()
    {
        return Activity::select('event')
            ->distinct()
            ->orderBy('event')
            ->get()
            ->map(function ($activity) {
                return [
                    'value' => $activity->event,
                    'label' => ucfirst($activity->event),
                ];
            })
            ->toArray();
    }

    /**
     * Get unique property keys for filter options.
     */
    private function getPropertiesOptions()
    {
        // This is a simplified version - in production you might want
        // to extract all unique keys from JSON properties
        $commonProperties = ['attributes', 'old', 'new', 'ip_address', 'user_agent', 'url'];

        return collect($commonProperties)->map(function ($property) {
            return [
                'value' => $property,
                'label' => ucwords(str_replace('_', ' ', $property)),
            ];
        })->toArray();
    }

    /**
     * Format properties for display.
     */
    private function formatProperties($properties)
    {
        if (is_string($properties)) {
            $properties = json_decode($properties, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($properties)) {
            return $properties;
        }

        return $properties;
    }

    /**
     * Get statistics grouped by log name.
     */
    private function getStatsByLogName()
    {
        return Activity::select('log_name', DB::raw('count(*) as count'))
            ->groupBy('log_name')
            ->orderBy('count', 'desc')
            ->take(10)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->log_name ?: 'system' => $item->count];
            });
    }

    /**
     * Get statistics grouped by event.
     */
    private function getStatsByEvent()
    {
        return Activity::select('event', DB::raw('count(*) as count'))
            ->groupBy('event')
            ->orderBy('count', 'desc')
            ->take(10)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->event => $item->count];
            });
    }

    /**
     * Get statistics grouped by causer.
     */
    private function getStatsByCauser()
    {
        return Activity::whereNotNull('causer_id')
            ->with('causer:id,name')
            ->select('causer_id', DB::raw('count(*) as count'))
            ->groupBy('causer_id')
            ->orderBy('count', 'desc')
            ->take(10)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->causer->name ?? 'Unknown' => $item->count];
            });
    }

    /**
     * Get statistics grouped by hour of day.
     */
    private function getStatsByHour()
    {
        return Activity::select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get()
            ->mapWithKeys(function ($item) {
                return [str_pad($item->hour, 2, '0', STR_PAD_LEFT).':00' => $item->count];
            });
    }

    /**
     * Archive old activity logs.
     */
    public function archive(Request $request)
    {
        if (! auth()->user()->can('manage_activity_log')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'archive_older_than' => 'required|integer|min:1|max:36',
        ]);

        $months = $request->get('archive_older_than');
        $cutoffDate = Carbon::now()->subMonths($months);

        try {
            // Create archive table if it doesn't exist
            if (! Schema::hasTable('activity_log_archive')) {
                Schema::create('activity_log_archive', function (Blueprint $table) {
                    $table->id();
                    $table->string('log_name')->nullable();
                    $table->text('description');
                    $table->string('subject_type')->nullable();
                    $table->unsignedBigInteger('subject_id')->nullable();
                    $table->string('causer_type')->nullable();
                    $table->unsignedBigInteger('causer_id')->nullable();
                    $table->json('properties')->nullable();
                    $table->string('batch_uuid')->nullable();
                    $table->string('event')->nullable();
                    $table->timestamps();
                    $table->index(['subject_type', 'subject_id']);
                    $table->index(['causer_type', 'causer_id']);
                    $table->index('log_name');
                    $table->index('created_at');
                });
            }

            // Archive old logs
            $oldActivities = Activity::where('created_at', '<', $cutoffDate)->get();
            $count = $oldActivities->count();

            DB::transaction(function () use ($oldActivities, $cutoffDate) {
                // Insert into archive
                foreach ($oldActivities as $activity) {
                    DB::table('activity_log_archive')->insert([
                        'log_name' => $activity->log_name,
                        'description' => $activity->description,
                        'subject_type' => $activity->subject_type,
                        'subject_id' => $activity->subject_id,
                        'causer_type' => $activity->causer_type,
                        'causer_id' => $activity->causer_id,
                        'properties' => json_encode($activity->properties),
                        'batch_uuid' => $activity->batch_uuid,
                        'event' => $activity->event,
                        'created_at' => $activity->created_at,
                        'updated_at' => $activity->updated_at,
                    ]);
                }

                // Delete from main table
                Activity::where('created_at', '<', $cutoffDate)->delete();
            });

            return response()->json([
                'success' => true,
                'message' => __('Successfully archived :count activity logs older than :months months.', [
                    'count' => $count,
                    'months' => $months,
                ]),
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to archive activity logs.'),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Restore archived activity logs.
     */
    public function restoreArchive(Request $request)
    {
        if (! auth()->user()->can('manage_activity_log')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'restore_older_than' => 'required|integer|min:1|max:36',
        ]);

        $months = $request->get('restore_older_than');
        $cutoffDate = Carbon::now()->subMonths($months);

        try {
            $archivedActivities = DB::table('activity_log_archive')
                ->where('created_at', '<', $cutoffDate)
                ->get();

            $count = $archivedActivities->count();

            DB::transaction(function () use ($archivedActivities, $cutoffDate) {
                // Restore to main table
                foreach ($archivedActivities as $activity) {
                    Activity::create([
                        'log_name' => $activity->log_name,
                        'description' => $activity->description,
                        'subject_type' => $activity->subject_type,
                        'subject_id' => $activity->subject_id,
                        'causer_type' => $activity->causer_type,
                        'causer_id' => $activity->causer_id,
                        'properties' => json_decode($activity->properties, true),
                        'batch_uuid' => $activity->batch_uuid,
                        'event' => $activity->event,
                        'created_at' => $activity->created_at,
                        'updated_at' => $activity->updated_at,
                    ]);
                }

                // Delete from archive
                DB::table('activity_log_archive')
                    ->where('created_at', '<', $cutoffDate)
                    ->delete();
            });

            return response()->json([
                'success' => true,
                'message' => __('Successfully restored :count archived activity logs.', [
                    'count' => $count,
                ]),
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to restore archived activity logs.'),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
