<?php

namespace App\Http\Controllers;

use App\Exports\MaintenanceCalendarExport;
use App\Exports\MaintenanceTasksExport;
use App\Http\Requests\VAPMaintenanceTaskRequest;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceTask;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\User;
use App\Notifications\MaintenanceCompleted;
use App\Notifications\MaintenanceOverdue;
use App\Notifications\MaintenanceReminder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use PDF;

class VAPMaintenanceController extends Controller
{
    private const CALIBRATION_CATEGORY_CODES = ['CAL_INT', 'CAL_EXT'];

    /**
     * Display maintenance dashboard
     */
    public function dashboard(Request $request)
    {
        $query = MaintenanceTask::with(['category', 'equipment', 'supplier'])
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->equipment_id, function ($query, $equipmentId) {
                $query->where('equipment_id', $equipmentId);
            })
            ->when($request->supplier_id, function ($query, $supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'overdue') {
                    $query->where('due_date', '<', now())
                          ->where('is_executed', false);
                } elseif ($status === 'due_soon') {
                    $query->whereBetween('due_date', [now(), now()->addDays(30)])
                          ->where('is_executed', false);
                } elseif ($status === 'executed') {
                    $query->where('is_executed', true);
                } elseif ($status === 'planned') {
                    $query->where('is_planned', true)
                          ->where('is_executed', false);
                }
            })
            ->when($request->cost_min, function ($query, $costMin) {
                $query->where('cost', '>=', $costMin);
            })
            ->when($request->cost_max, function ($query, $costMax) {
                $query->where('cost', '<=', $costMax);
            })
            ->orderBy($request->sort_by ?? 'due_date', $request->sort_direction ?? 'asc');

        $stats = [
            'total_tasks' => MaintenanceTask::count(),
            'overdue' => MaintenanceTask::where('due_date', '<', now())
                ->where('is_executed', false)
                ->count(),
            'due_soon' => MaintenanceTask::whereBetween('due_date', [now(), now()->addDays(30)])
                ->where('is_executed', false)
                ->count(),
            'executed' => MaintenanceTask::where('is_executed', true)->count(),
            'planned' => MaintenanceTask::where('is_planned', true)
                ->where('is_executed', false)
                ->count(),
        ];

        return Inertia::render('VAPMaintenance/Dashboard', [
            'tasks' => $query->paginate($request->per_page ?? 20)->withQueryString(),
            'categories' => MaintenanceCategory::all(),
            'equipment' => InventoryItem::whereNotNull('last_calibration_date')->get(),
            'suppliers' => InventoryItemSupplier::all(),
            'filters' => $request->only(['category_id', 'equipment_id', 'supplier_id', 'status', 'cost_min', 'cost_max', 'sort_by', 'sort_direction']),
            'stats' => $stats,
        ]);
    }

    /**
     * Display maintenance categories
     */
    public function categories(Request $request)
    {
        $query = MaintenanceCategory::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->orderBy('name');

        return Inertia::render('VAPMaintenance/Categories/Index', [
            'categories' => $query->paginate($request->per_page ?? 15)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store new maintenance category
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:maintenance_categories,code',
            'description' => 'nullable|string',
        ]);

        MaintenanceCategory::create($request->all());

        return redirect()->route('vap-maintenance.categories')
            ->with('success', 'Maintenance category created successfully.');
    }

    /**
     * Update maintenance category
     */
    public function updateCategory(Request $request, MaintenanceCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:maintenance_categories,code,' . $category->id,
            'description' => 'nullable|string',
        ]);

        $category->update($request->all());

        return redirect()->route('vap-maintenance.categories')
            ->with('success', 'Maintenance category updated successfully.');
    }

    /**
     * Delete maintenance category
     */
    public function destroyCategory(MaintenanceCategory $category)
    {
        $category->delete();

        return redirect()->route('vap-maintenance.categories')
            ->with('success', 'Maintenance category deleted successfully.');
    }

    /**
     * Display maintenance tasks
     */
    public function tasks(Request $request)
    {
        $query = MaintenanceTask::with(['category', 'equipment', 'supplier'])
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->equipment_id, function ($query, $equipmentId) {
                $query->where('equipment_id', $equipmentId);
            })
            ->when($request->supplier_id, function ($query, $supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'overdue') {
                    $query->where('due_date', '<', now())
                          ->where('is_executed', false);
                } elseif ($status === 'executed') {
                    $query->where('is_executed', true);
                } elseif ($status === 'planned') {
                    $query->where('is_planned', true)
                          ->where('is_executed', false);
                }
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->where('due_date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->where('due_date', '<=', $dateTo);
            })
            ->when($request->cost_min, function ($query, $costMin) {
                $query->where('cost', '>=', $costMin);
            })
            ->when($request->cost_max, function ($query, $costMax) {
                $query->where('cost', '<=', $costMax);
            })
            ->orderBy($request->sort_by ?? 'due_date', $request->sort_direction ?? 'asc');

            $stats = [
                'total_tasks' => MaintenanceTask::count(),
                'monthly_average' => MaintenanceTask::whereBetween('due_date', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count(),
                'overdue' => MaintenanceTask::where('due_date', '<', now())
                    ->where('is_executed', false)
                    ->count(),
                'due_soon' => MaintenanceTask::whereBetween('due_date', [now(), now()->addDays(30)])
                    ->where('is_executed', false)
                    ->count(),
                'executed' => MaintenanceTask::where('is_executed', true)->count(),
                'planned' => MaintenanceTask::where('is_planned', true)
                    ->where('is_executed', false)
                    ->count(),
            ];

        return Inertia::render('VAPMaintenance/Tasks/Index', [
            'tasks' => $query->paginate($request->per_page ?? 20)->withQueryString(),
            'categories' => MaintenanceCategory::all(),
            'equipment' => InventoryItem::all(),
            'suppliers' => InventoryItemSupplier::all(),
            'filters' => $request->only(['category_id', 'equipment_id', 'supplier_id', 'status', 'date_from', 'date_to', 'cost_min', 'cost_max', 'sort_by', 'sort_direction']),
            'stats' => $stats,
        ]);
    }

    /**
     * Show form to create maintenance task
     */
    public function createTask(Request $request)
    {
        return Inertia::render('VAPMaintenance/Tasks/Create', [
            'categories' => MaintenanceCategory::all(),
            'equipment' => InventoryItem::all(),
            'suppliers' => InventoryItemSupplier::all(),
        ]);
    }

    /**
     * Store new maintenance task
     */
    public function storeTask(VAPMaintenanceTaskRequest $request)
    {
        $data = $request->validated();
        $data['maintenance_task_year'] = now()->year;

        // Calculate next date if periodicity is set
        if (!empty($data['periodicity']) && !empty($data['periodicity_unit'])) {
            $dueDate = Carbon::parse($data['due_date']);
            $data['next_date'] = $dueDate->copy()->add($data['periodicity_unit'], (int) $data['periodicity']);
        }

        $task = MaintenanceTask::create($data);

        return redirect()->route('vap-maintenance.tasks')
            ->with('success', 'Tarefa de manutenção criada com sucesso.');
    }

    /**
     * Show maintenance task details
     */
    public function showTask(MaintenanceTask $task)
    {
        $task->load(['category', 'equipment', 'supplier']);
    
        // Get equipment history
        $equipmentHistory = $this->getEquipmentHistory($task->equipment_id);

        return Inertia::render('VAPMaintenance/Tasks/Show', [
            'task' => $task,
            'equipmentHistory' => $equipmentHistory,
        ]);
    }

    /**
     * Update maintenance task
     */
    public function updateTask(VAPMaintenanceTaskRequest $request, MaintenanceTask $task)
    {
        $data = $request->validated();

        // If marking as executed, set previous date and calculate next
        if (($data['is_executed'] ?? false) && ! $task->is_executed) {
            $data['previous_date'] = $task->due_date;

            $periodicity = $data['periodicity'] ?? $task->periodicity;
            $periodicityUnit = $data['periodicity_unit'] ?? $task->periodicity_unit;

            if (!empty($periodicity) && !empty($periodicityUnit)) {
                $referenceDueDate = Carbon::parse($data['due_date'] ?? $task->due_date);
                $data['previous_date'] = $referenceDueDate;
                $data['due_date'] = $referenceDueDate->copy()
                    ->add($periodicityUnit, (int) $periodicity);
                $data['next_date'] = Carbon::parse($data['due_date'])
                    ->add($periodicityUnit, (int) $periodicity);
            }
            
            // Update equipment calibration dates if this is a calibration task
            if (in_array($task->category?->code, self::CALIBRATION_CATEGORY_CODES, true) && $task->equipment) {
                $task->equipment->update([
                    'last_calibration_date' => $task->due_date,
                    'next_calibration_date' => $data['due_date'] ?? $task->next_date,
                ]);
            }
        }

        $task->update($data);

        return redirect()->route('vap-maintenance.tasks.show', $task)
            ->with('success', 'Tarefa de manutenção atualizada com sucesso.');
    }

    /**
     * Delete maintenance task
     */
    public function destroyTask(MaintenanceTask $task)
    {
        $task->delete();

        return redirect()->route('vap-maintenance.tasks')
            ->with('success', 'Tarefa de manutenção eliminada com sucesso.');
    }

    /**
     * Generate maintenance report
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:overdue,upcoming,executed,category_summary,cost_analysis',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'category_id' => 'nullable|exists:maintenance_categories,id',
            'format' => 'required|in:pdf,csv,excel',
        ]);

        $query = MaintenanceTask::with(['category', 'equipment', 'supplier']);

        if ($request->date_from) {
            $query->where('due_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('due_date', '<=', $request->date_to);
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        switch ($request->report_type) {
            case 'overdue':
                $query->where('due_date', '<', now())
                    ->where('is_executed', false);
                break;
            case 'upcoming':
                $query->where('due_date', '>=', now())
                    ->where('is_executed', false);
                break;
            case 'executed':
                $query->where('is_executed', true);
                break;
        }

        $data = $query->get();

        return response()->json([
            'data' => $data,
            'total' => $data->count(),
            'total_cost' => $data->sum('cost'),
        ]);
    }

    /**
     * Bulk update maintenance tasks
     */
    public function bulkUpdate(Request $request)
    {
            $request->validate([
                'task_ids' => 'required|array',
                'task_ids.*' => 'exists:maintenance_tasks,id',
                'action' => 'required|in:mark_executed,reschedule,delete',
                'new_date' => 'required_if:action,reschedule|date',
                'send_notification' => 'boolean',
            ]);

            $tasks = MaintenanceTask::whereIn('id', $request->task_ids)->get();

            switch ($request->action) {
                case 'mark_executed':
                    $tasks->each(function ($task) {
                        $payload = [
                            'is_executed' => true,
                            'previous_date' => $task->due_date,
                            'result' => $task->result ?: 'Concluída em ação em massa.',
                        ];

                        if (!empty($task->periodicity) && !empty($task->periodicity_unit) && $task->due_date) {
                            $payload['due_date'] = Carbon::parse($task->due_date)->add($task->periodicity_unit, (int) $task->periodicity);
                            $payload['next_date'] = Carbon::parse($payload['due_date'])->add($task->periodicity_unit, (int) $task->periodicity);
                        }

                        $task->update($payload);

                        if (in_array($task->category?->code, self::CALIBRATION_CATEGORY_CODES, true) && $task->equipment) {
                            $task->equipment->update([
                                'last_calibration_date' => $task->previous_date,
                                'next_calibration_date' => $task->due_date,
                            ]);
                        }
                    });
                    break;

                case 'reschedule':
                    $tasks->each(function ($task) use ($request) {
                        $task->update(['due_date' => $request->new_date]);
                    });
                    
                    // Send notification if requested
                    if ($request->send_notification) {
                        // Implementation depends on your notification system
                    }
                    break;

                case 'delete':
                    $tasks->each->delete();
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'A ação em massa foi concluída com sucesso.',
            ]);
    }

    /**
     * Send maintenance notifications
     */
    public function sendNotifications(Request $request)
    {
        $request->validate([
            'days_threshold' => 'required|integer|min:1|max:365',
            'notification_type' => 'required|in:upcoming,overdue,both',
        ]);

        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['maintenance_manager', 'lab_manager', 'admin']);
        })->get();

        $tasks = MaintenanceTask::with(['equipment', 'category'])
            ->where('is_executed', false);

        if ($request->notification_type === 'upcoming' || $request->notification_type === 'both') {
            $upcomingTasks = clone $tasks;
            $upcomingTasks->whereBetween('due_date', [
                now(),
                now()->addDays($request->days_threshold)
            ]);

            $upcomingCount = $upcomingTasks->count();

            if ($upcomingCount > 0) {
                Notification::send($users, new MaintenanceReminder(
                    $upcomingTasks->get(),
                    $request->days_threshold
                ));
            }
        }

        if ($request->notification_type === 'overdue' || $request->notification_type === 'both') {
            $overdueTasks = clone $tasks;
            $overdueTasks->where('due_date', '<', now());

            $overdueCount = $overdueTasks->count();

            if ($overdueCount > 0) {
                Notification::send($users, new MaintenanceOverdue(
                    $overdueTasks->get()
                ));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notificações de manutenção enviadas com sucesso.',
            'upcoming_count' => $upcomingCount ?? 0,
            'overdue_count' => $overdueCount ?? 0,
        ]);
    }

    /**
     * Notify task completion
     */
    public function notifyCompletion(MaintenanceTask $task)
    {
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['maintenance_manager', 'lab_manager', 'admin']);
        })->get();

        Notification::send($users, new MaintenanceCompleted($task));

        return response()->json([
            'success' => true,
            'message' => 'Notificação de conclusão enviada com sucesso.',
        ]);
    }

    /**
     * Export maintenance tasks to Excel
     */
    public function exportTasks(Request $request)
    {
        $request->validate([
            'format' => 'required|in:excel,csv,pdf',
            'type' => 'required|in:tasks,calendar,categories',
        ]);

        $filters = $request->only(['category_id', 'status', 'date_from', 'date_to', 'supplier_id', 'cost_min', 'cost_max']);

        if ($request->type === 'tasks') {
            $filename = 'maintenance_tasks_' . date('Y-m-d_H-i-s');
            
            if ($request->format === 'pdf') {
                $tasks = $this->getTasksForExport($filters);
                $pdf = PDF::loadView('exports.maintenance.tasks', [
                    'tasks' => $tasks,
                    'filters' => $filters,
                    'generated_at' => now(),
                ]);
                
                return $pdf->download("{$filename}.pdf");
            }
            
            $writerType = $request->format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;

            return Excel::download(new MaintenanceTasksExport($filters), "{$filename}.{$request->format}", $writerType);
        }

        if ($request->type === 'calendar') {
            $filename = 'maintenance_calendar_' . date('Y-m-d_H-i-s');
            
            if ($request->format === 'pdf') {
                $calendar = $this->getCalendarForExport($filters);
                $pdf = PDF::loadView('exports.maintenance.calendar', [
                    'calendar' => $calendar,
                    'filters' => $filters,
                    'generated_at' => now(),
                ]);
                
                return $pdf->download("{$filename}.pdf");
            }
            
            $writerType = $request->format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;

            return Excel::download(new MaintenanceCalendarExport($filters), "{$filename}.{$request->format}", $writerType);
        }

        return response()->json(['error' => 'Tipo de exportação inválido.'], 400);
    }


    /**
     * Get tasks for export
     */
    private function getTasksForExport($filters)
    {
        return MaintenanceTask::with(['category', 'equipment', 'supplier'])
            ->when($filters['category_id'] ?? false, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($filters['status'] ?? false, function ($query, $status) {
                if ($status === 'overdue') {
                    $query->where('due_date', '<', now())
                        ->where('is_executed', false);
                } elseif ($status === 'executed') {
                    $query->where('is_executed', true);
                }
            })
            ->when($filters['supplier_id'] ?? false, function ($query, $supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->when($filters['date_from'] ?? false, function ($query, $dateFrom) {
                $query->where('due_date', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? false, function ($query, $dateTo) {
                $query->where('due_date', '<=', $dateTo);
            })
            ->when($filters['cost_min'] ?? false, function ($query, $costMin) {
                $query->where('cost', '>=', $costMin);
            })
            ->when($filters['cost_max'] ?? false, function ($query, $costMax) {
                $query->where('cost', '<=', $costMax);
            })
            ->orderBy('due_date')
            ->get();
    }


        /**
     * Get calendar for export
     */
    private function getCalendarForExport($filters)
    {
        $startDate = $filters['date_from'] ?? now()->startOfMonth();
        $endDate = $filters['date_to'] ?? now()->addMonths(3)->endOfMonth();

        $tasks = MaintenanceTask::with(['category', 'equipment'])
            ->whereBetween('due_date', [$startDate, $endDate])
            ->when($filters['category_id'] ?? false, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('due_date')
            ->get();

        $calendar = [];
        $currentDate = Carbon::parse($startDate);
        
        while ($currentDate <= Carbon::parse($endDate)) {
            $dateKey = $currentDate->format('Y-m-d');
            $calendar[$dateKey] = [
                'date' => $currentDate->format('d/m/Y'),
                'day' => $currentDate->format('D'),
                'tasks' => $tasks->filter(function ($task) use ($currentDate) {
                    return Carbon::parse($task->due_date)->format('Y-m-d') === $currentDate->format('Y-m-d');
                })->values(),
            ];
            $currentDate->addDay();
        }

        return $calendar;
    }

        /**
     * Get dashboard statistics for charts
     */
    // public function getDashboardStats(Request $request)
    // {
    //     $period = $request->input('period', 'month');

    //     // Task status distribution
    //     $statusStats = [
    //         'overdue' => MaintenanceTask::where('due_date', '<', now())
    //             ->where('is_executed', false)
    //             ->count(),
    //         'due_soon' => MaintenanceTask::whereBetween('due_date', [now(), now()->addDays(30)])
    //             ->where('is_executed', false)
    //             ->count(),
    //         'scheduled' => MaintenanceTask::where('due_date', '>', now()->addDays(30))
    //             ->where('is_executed', false)
    //             ->count(),
    //         'executed' => MaintenanceTask::where('is_executed', true)->count(),
    //     ];

    //     // Category distribution
    //     $categoryStats = MaintenanceCategory::withCount(['tasks' => function ($query) {
    //         $query->where('is_executed', false);
    //     }])->get();

    //     // Monthly trend
    //     $monthlyTrend = [];
    //     for ($i = 5; $i >= 0; $i--) {
    //         $month = now()->subMonths($i);
    //         $start = $month->copy()->startOfMonth();
    //         $end = $month->copy()->endOfMonth();

    //         $monthlyTrend[] = [
    //             'month' => $month->format('M Y'),
    //             'created' => MaintenanceTask::whereBetween('created_at', [$start, $end])->count(),
    //             'executed' => MaintenanceTask::whereBetween('due_date', [$start, $end])
    //                 ->where('is_executed', true)
    //                 ->count(),
    //             'overdue' => MaintenanceTask::whereBetween('due_date', [$start, $end])
    //                 ->where('is_executed', false)
    //                 ->where('due_date', '<', now())
    //                 ->count(),
    //         ];
    //     }

    //     // Cost analysis
    //     $costStats = [
    //         'total' => MaintenanceTask::sum('cost'),
    //         'monthly' => MaintenanceTask::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
    //             ->sum('cost'),
    //         'by_category' => MaintenanceCategory::withSum('tasks', 'cost')->get(),
    //     ];

    //     return response()->json([
    //         'status_stats' => $statusStats,
    //         'category_stats' => $categoryStats,
    //         'monthly_trend' => $monthlyTrend,
    //         'cost_stats' => $costStats,
    //     ]);
    // }


        /**
     * Get equipment maintenance history
     */
    public function getEquipmentHistory($equipmentId)
    {
        if (!$equipmentId) {
            return null;
        }

        $tasks = MaintenanceTask::with(['category', 'supplier'])
            ->where('equipment_id', $equipmentId)
            ->orderBy('due_date', 'desc')
            ->limit(50)
            ->get();

        $stats = [
            'total_tasks' => $tasks->count(),
            'executed_tasks' => $tasks->where('is_executed', true)->count(),
            'total_cost' => $tasks->sum('cost'),
            'avg_cost' => $tasks->avg('cost'),
            'last_maintenance' => $tasks->where('is_executed', true)->max('due_date'),
            'next_scheduled' => $tasks->where('is_executed', false)
                ->where('due_date', '>', now())
                ->min('due_date'),
        ];

        return [
            'tasks' => $tasks,
            'stats' => $stats,
        ];

    }

    /**
     * Get dashboard statistics for charts
     */
    public function getDashboardStats(Request $request)
    {
        
        $period = $request->input('period', 'month');
        $range = $request->input('range', '6months');
        
        // Calculate date range based on period
        switch ($range) {
            case '6months':
                $months = 6;
                break;
            case '1year':
                $months = 12;
                break;
            case '2years':
                $months = 24;
                break;
            default:
                $months = 6;
        }

        // Task status distribution
        $statusStats = [
            'overdue' => MaintenanceTask::where('due_date', '<', now())
                ->where('is_executed', false)
                ->count(),
            'due_soon' => MaintenanceTask::whereBetween('due_date', [now(), now()->addDays(30)])
                ->where('is_executed', false)
                ->count(),
            'scheduled' => MaintenanceTask::where('due_date', '>', now()->addDays(30))
                ->where('is_executed', false)
                ->count(),
            'executed' => MaintenanceTask::where('is_executed', true)->count(),
        ];

        // Category distribution
        $categoryStats = MaintenanceCategory::withCount(['tasks' => function ($query) {
            $query->where('is_executed', false);
        }])->get();

        // Monthly trend - dynamic based on range
        $monthlyTrend = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $monthlyTrend[] = [
                'month' => $month->format('M Y'),
                'created' => MaintenanceTask::whereBetween('created_at', [$start, $end])->count(),
                'executed' => MaintenanceTask::whereBetween('due_date', [$start, $end])
                    ->where('is_executed', true)
                    ->count(),
                'overdue' => MaintenanceTask::whereBetween('due_date', [$start, $end])
                    ->where('is_executed', false)
                    ->where('due_date', '<', now())
                    ->count(),
            ];
        }

        // Cost analysis
        $totalCost = MaintenanceTask::sum('cost');
        $monthlyCost = MaintenanceTask::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('cost');
        
        $avgCost = MaintenanceTask::where('cost', '>', 0)->avg('cost') ?? 0;
        $tasksWithCost = MaintenanceTask::where('cost', '>', 0)->count();
        
        $costByCategory = MaintenanceCategory::withSum(['tasks' => function ($query) {
            $query->where('cost', '>', 0);
        }], 'cost')->get();
        
        $highestCostCategory = $costByCategory->sortByDesc('tasks_sum_cost')->first();

        return response()->json([
            'status_stats' => $statusStats,
            'category_stats' => $categoryStats,
            'monthly_trend' => $monthlyTrend,
            'total_cost' => (float) $totalCost,
            'monthly_cost' => (float) $monthlyCost,
            'avg_cost' => (float) $avgCost,
            'tasks_with_cost' => $tasksWithCost,
            'highest_cost_category' => $highestCostCategory, 
            'cost_by_category' => $costByCategory,
        ]);
    }
}
