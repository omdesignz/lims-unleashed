<?php

namespace App\Http\Controllers;

use App\Actions\CompleteMaintenanceTasks;
use App\Actions\ExportMaintenanceTasks;
use App\Actions\SaveMaintenanceTask;
use App\Actions\UpdateMaintenanceTaskLifecycle;
use App\Http\Requests\BulkMaintenanceTaskRequest;
use App\Http\Requests\ExportMaintenanceTasksRequest;
use App\Http\Requests\MaintenanceDashboardStatsRequest;
use App\Http\Requests\MaintenanceTaskIndexRequest;
use App\Http\Requests\VAPMaintenanceTaskRequest;
use App\Http\Resources\MaintenanceTaskAuthoringResource;
use App\Http\Resources\MaintenanceTaskListResource;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceTask;
use App\Models\User;
use App\Services\SampleLaboratoryAccess;
use App\Support\MaintenanceTaskQuery;
use App\Support\NotificationTemplateService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VAPMaintenanceController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    private function ownedTasks(): Builder
    {
        return MaintenanceTask::forLaboratory($this->laboratoryAccess->activeLabId());
    }

    /** @return array<string, bool> */
    private function taskAbilities(): array
    {
        $canMutate = ! request()->session()->has('impersonate');

        return [
            'create' => $canMutate && request()->user()->can('add_maintenance_tasks'),
            'edit' => $canMutate && request()->user()->can('edit_maintenance_tasks'),
            'delete' => $canMutate && request()->user()->can('delete_maintenance_tasks'),
            'restore' => $canMutate && request()->user()->can('restore_maintenance_tasks'),
            'export' => request()->user()->can('export_maintenance_tasks'),
        ];
    }

    /**
     * Display maintenance dashboard
     */
    public function dashboard(MaintenanceTaskIndexRequest $request, MaintenanceTaskQuery $tasks): Response
    {
        return $this->taskIndex($request, $tasks, 'VAPMaintenance/Dashboard');
    }

    /**
     * Display maintenance tasks
     */
    public function tasks(MaintenanceTaskIndexRequest $request, MaintenanceTaskQuery $tasks): Response
    {
        return $this->taskIndex($request, $tasks, 'VAPMaintenance/Tasks/Index');
    }

    private function taskIndex(MaintenanceTaskIndexRequest $request, MaintenanceTaskQuery $tasks, string $component): Response
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $filters = $request->validated();
        $query = $tasks->query($labId, $filters);

        return Inertia::render($component, [
            'can' => $this->taskAbilities(),
            'today' => today()->toDateString(),
            'stats' => $tasks->summary($query),
            'tasks' => $query->with(['category', 'equipment', 'supplier'])
                ->orderBy($filters['sort_by'] ?? 'due_date', $filters['sort_direction'] ?? 'asc')
                ->orderBy('id')->paginate($filters['per_page'] ?? 20)->withQueryString()
                ->through(fn (MaintenanceTask $task): array => MaintenanceTaskListResource::make($task)->resolve($request)),
            'categories' => MaintenanceCategory::availableToLaboratory($this->laboratoryAccess->activeLabId())->get(['id', 'name', 'code']),
            'equipment' => InventoryItem::forLaboratory($labId)->equipment()->get(),
            'suppliers' => InventoryItemSupplier::all(),
            'filters' => $request->safe()->except(['page', 'per_page']),
        ]);
    }

    /**
     * Show form to create maintenance task
     */
    public function createTask(Request $request)
    {
        return Inertia::render('VAPMaintenance/Tasks/Create', [
            'today' => today()->toDateString(),
            'categories' => MaintenanceCategory::availableToLaboratory($this->laboratoryAccess->activeLabId())->get(['id', 'name', 'code']),
            'equipment' => InventoryItem::forLaboratory($this->laboratoryAccess->activeLabId())->equipment()->get(),
            'suppliers' => InventoryItemSupplier::all(),
        ]);
    }

    public function editTask(MaintenanceTask $task): Response
    {
        abort_unless($this->ownedTasks()->whereKey($task->id)->exists(), 404);

        return Inertia::render('VAPMaintenance/Tasks/Create', [
            'task' => MaintenanceTaskAuthoringResource::make($task)->resolve(),
            'today' => today()->toDateString(),
            'categories' => MaintenanceCategory::availableToLaboratory($this->laboratoryAccess->activeLabId())->withTrashed()
                ->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $task->category_id))->get(['id', 'name', 'code']),
            'equipment' => InventoryItem::forLaboratory($this->laboratoryAccess->activeLabId())->equipment()->get(),
            'suppliers' => InventoryItemSupplier::all(),
        ]);
    }

    /**
     * Store new maintenance task
     */
    public function storeTask(VAPMaintenanceTaskRequest $request, SaveMaintenanceTask $save)
    {
        $save->execute($request->user()->id, $this->laboratoryAccess->activeLabId(), $request->submittedData());

        return redirect()->route('vap-maintenance.tasks')
            ->with('success', 'Tarefa de manutenção criada com sucesso.');
    }

    /**
     * Show maintenance task details
     */
    public function showTask(MaintenanceTask $task)
    {
        abort_unless($this->ownedTasks()->whereKey($task->id)->exists(), 404);
        $task->load(['category', 'equipment', 'supplier']);

        // Get equipment history
        $equipmentHistory = $this->getEquipmentHistory($task->equipment_id);

        return Inertia::render('VAPMaintenance/Tasks/Show', [
            'can' => $this->taskAbilities(),
            'task' => $task,
            'equipmentHistory' => $equipmentHistory,
        ]);
    }

    /**
     * Update maintenance task
     */
    public function updateTask(VAPMaintenanceTaskRequest $request, MaintenanceTask $task, SaveMaintenanceTask $save)
    {
        $task = $save->execute($request->user()->id, $this->laboratoryAccess->activeLabId(), $request->submittedData(), $task->id);

        return redirect()->route('vap-maintenance.tasks.show', $task)
            ->with('success', 'Tarefa de manutenção actualizada com sucesso.');
    }

    /**
     * Delete maintenance task
     */
    public function destroyTask(Request $request, MaintenanceTask $task, UpdateMaintenanceTaskLifecycle $lifecycle)
    {
        $lifecycle->execute($request->user()->id, $this->laboratoryAccess->activeLabId(), [$task->id], 'delete');

        return redirect()->route('vap-maintenance.tasks')
            ->with('success', 'Tarefa de manutenção arquivada com sucesso.');
    }

    public function restoreTask(Request $request, MaintenanceTask $task, UpdateMaintenanceTaskLifecycle $lifecycle)
    {
        $lifecycle->execute($request->user()->id, $this->laboratoryAccess->activeLabId(), [$task->id], 'restore');

        return redirect()->route('vap-maintenance.tasks')->with('success', 'Tarefa de manutenção restaurada com sucesso.');
    }

    /**
     * Generate maintenance report
     */
    public function generateReport(ExportMaintenanceTasksRequest $request, ExportMaintenanceTasks $export): \Symfony\Component\HttpFoundation\Response
    {
        return $this->exportTasks($request, $export);
    }

    /**
     * Bulk update maintenance tasks
     */
    public function bulkUpdate(BulkMaintenanceTaskRequest $request, CompleteMaintenanceTasks $complete, UpdateMaintenanceTaskLifecycle $lifecycle)
    {
        if ($request->validated('action') === 'mark_executed') {
            $complete->execute($request->user()->id, $this->laboratoryAccess->activeLabId(), $request->validated('task_ids'));
        } else {
            $lifecycle->execute(
                $request->user()->id, $this->laboratoryAccess->activeLabId(),
                $request->validated('task_ids'), $request->validated('action'), $request->validated('new_date'),
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'A acção em massa foi concluída com sucesso.',
        ]);
    }

    /**
     * Send maintenance notifications
     */
    public function sendNotifications(Request $request, NotificationTemplateService $templates)
    {
        abort_if($request->session()->has('impersonate'), 403);
        $labId = $this->laboratoryAccess->activeLabId();
        $request->validate([
            'days_threshold' => 'required|integer|min:1|max:365',
            'notification_type' => 'required|in:upcoming,overdue,both',
        ]);

        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['maintenance_manager', 'lab_manager', 'admin']);
        })->whereIn('id', DB::table('lab_user')->where('lab_id', $labId)->select('user_id'))
            ->where('is_active', true)->whereNotNull('email_verified_at')->get()
            ->filter(fn (User $user): bool => $user->can('view_maintenance_tasks'))->values();

        $tasks = $this->ownedTasks()->with(['equipment', 'category'])
            ->where('is_executed', false);

        if ($request->notification_type === 'upcoming' || $request->notification_type === 'both') {
            $upcomingTasks = clone $tasks;
            $upcomingTasks->whereBetween('due_date', [
                now(),
                now()->addDays($request->days_threshold),
            ]);

            $upcomingCount = $upcomingTasks->count();

            if ($upcomingCount > 0) {
                foreach ($upcomingTasks->get() as $task) {
                    $templates->notify($users, 'maintenance.reminder', $this->maintenanceNotificationContext($task));
                }
            }
        }

        if ($request->notification_type === 'overdue' || $request->notification_type === 'both') {
            $overdueTasks = clone $tasks;
            $overdueTasks->where('due_date', '<', now());

            $overdueCount = $overdueTasks->count();

            if ($overdueCount > 0) {
                foreach ($overdueTasks->get() as $task) {
                    $templates->notify($users, 'maintenance.overdue', $this->maintenanceNotificationContext($task));
                }
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
    public function notifyCompletion(MaintenanceTask $task, NotificationTemplateService $templates)
    {
        abort_if(request()->session()->has('impersonate'), 403);
        abort_unless($this->ownedTasks()->whereKey($task->id)->exists(), 404);
        if (! $task->is_executed || blank($task->result)) {
            throw ValidationException::withMessages(['task' => 'Conclua a tarefa com um resultado registado antes de notificar.']);
        }
        $labId = $this->laboratoryAccess->activeLabId();
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['maintenance_manager', 'lab_manager', 'admin']);
        })->whereIn('id', DB::table('lab_user')->where('lab_id', $labId)->select('user_id'))
            ->where('is_active', true)->whereNotNull('email_verified_at')->get()
            ->filter(fn (User $user): bool => $user->can('view_maintenance_tasks'))->values();

        $templates->notify($users, 'maintenance.completed', $this->maintenanceNotificationContext($task));

        return response()->json([
            'success' => true,
            'message' => 'Notificação de conclusão enviada com sucesso.',
        ]);
    }

    /** @return array<string, scalar|null> */
    private function maintenanceNotificationContext(MaintenanceTask $task): array
    {
        $task->loadMissing('equipment');

        return [
            'lab_id' => $task->equipment?->lab_id,
            'equipment_name' => $task->equipment?->name ?? $task->name,
            'due_date' => $task->due_date?->format('d/m/Y') ?? 'sem data definida',
            'document_url' => route('vap-maintenance.tasks.show', $task),
        ];
    }

    /**
     * Export maintenance tasks to Excel
     */
    public function exportTasks(ExportMaintenanceTasksRequest $request, ExportMaintenanceTasks $export): \Symfony\Component\HttpFoundation\Response
    {
        return $export->execute(
            $this->laboratoryAccess->activeLabId(), $request->validated('type'),
            $request->validated('format'), $request->filters(),
        );
    }

    /**
     * Get equipment maintenance history
     */
    public function getEquipmentHistory($equipmentId)
    {
        if (! $equipmentId) {
            return null;
        }

        InventoryItem::forLaboratory($this->laboratoryAccess->activeLabId())->findOrFail($equipmentId);
        $history = $this->ownedTasks()->where('equipment_id', $equipmentId);
        $tasks = (clone $history)->with(['category', 'supplier'])->orderByDesc('due_date')->limit(50)->get();
        $totals = (clone $history)->selectRaw(
            'COUNT(*) AS total_tasks,
            COALESCE(SUM(CASE WHEN is_executed THEN 1 ELSE 0 END), 0) AS executed_tasks,
            COALESCE(SUM(cost), 0) AS total_cost,
            AVG(cost) AS avg_cost,
            MAX(CASE WHEN is_executed THEN previous_date END) AS last_maintenance,
            MIN(CASE WHEN NOT is_executed AND due_date >= ? THEN due_date END) AS next_scheduled',
            [today()->toDateString()],
        )->first();
        $stats = [
            'total_tasks' => (int) $totals->total_tasks,
            'executed_tasks' => (int) $totals->executed_tasks,
            'total_cost' => (float) $totals->total_cost,
            'avg_cost' => $totals->avg_cost === null ? null : (float) $totals->avg_cost,
            'last_maintenance' => $totals->last_maintenance,
            'next_scheduled' => $totals->next_scheduled,
        ];

        return [
            'tasks' => $tasks,
            'stats' => $stats,
        ];

    }

    /**
     * Get dashboard statistics for charts
     */
    public function getDashboardStats(MaintenanceDashboardStatsRequest $request, MaintenanceTaskQuery $tasks): JsonResponse
    {
        $months = match ($request->validated('period', 'month')) {
            'quarter' => 3,
            'year' => 12,
            default => 1,
        };
        $start = today()->startOfMonth()->subMonthsNoOverflow($months - 1);
        $query = $tasks->query($this->laboratoryAccess->activeLabId(), $request->validated())
            ->whereBetween('created_at', [$start, now()]);
        $summary = $tasks->summary($query);
        $costs = (clone $query)->selectRaw('COALESCE(AVG(cost), 0) AS average, COUNT(*) FILTER (WHERE cost > 0) AS with_cost,
            COALESCE(SUM(cost) FILTER (WHERE created_at >= ?), 0) AS monthly', [today()->startOfMonth()])->first();
        $categories = (clone $query)->selectRaw('category_id, COALESCE(SUM(cost), 0) AS cost')->groupBy('category_id')->get();
        $names = MaintenanceCategory::withTrashed()->whereKey($categories->pluck('category_id'))->pluck('name', 'id');
        $categoryCosts = $categories->map(fn ($row): array => [
            'id' => $row->category_id, 'name' => $names[$row->category_id] ?? 'Categoria indisponível', 'cost' => (float) $row->cost,
        ])->sortByDesc('cost')->values();

        return response()->json([
            'period_start' => $start->toDateString(),
            'period_end' => today()->toDateString(),
            'status_stats' => [
                'overdue' => $summary['overdue'], 'due_soon' => $summary['due_soon'],
                'executed' => $summary['executed'],
                'scheduled' => $summary['total_tasks'] - $summary['overdue'] - $summary['due_soon'] - $summary['executed'],
            ],
            'total_cost' => $summary['total_cost'],
            'monthly_cost' => (float) $costs->monthly,
            'avg_cost' => (float) $costs->average,
            'tasks_with_cost' => (int) $costs->with_cost,
            'highest_cost_category' => $categoryCosts->first(),
            'cost_by_category' => $categoryCosts,
        ]);
    }
}
