<?php

namespace App\Http\Controllers;

use App\Models\BroadcastNotification;
use App\Models\User;
use App\Notifications\GlobalNotification;
use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function __construct(
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly SampleLaboratoryAccess $laboratoryAccess
    ) {}

    /** @return Builder<DatabaseNotification> */
    private function notificationQuery(): Builder
    {
        return $this->ownership->scopeStoredNotifications(DatabaseNotification::query(), auth()->user());
    }

    /** @return Builder<DatabaseNotification> */
    private function administrativeNotificationQuery(): Builder
    {
        $labId = $this->laboratoryAccess->activeLabId();

        return $this->notificationQuery()
            ->where('notifications.notifiable_type', (new User)->getMorphClass())
            ->whereIn('notifications.notifiable_id', DB::table('lab_user')->where('lab_id', $labId)->select('user_id'))
            ->where(function (Builder $query) use ($labId): void {
                $query->where(DB::raw("notifications.data::jsonb ->> 'lab_id'"), (string) $labId)
                    ->orWhere(DB::raw("notifications.data::jsonb -> 'context' ->> 'lab_id'"), (string) $labId);
            });
    }

    /** @return Builder<User> */
    private function laboratoryUsers(): Builder
    {
        return User::query()->whereIn('users.id', DB::table('lab_user')
            ->where('lab_id', $this->laboratoryAccess->activeLabId())->select('user_id'));
    }

    public function index(Request $request)
    {
        $notifications = $this->notificationQuery()
            ->where('notifiable_id', auth()->id())
            ->where('notifiable_type', auth()->user()->getMorphClass())
            ->latest()
            ->paginate(20);

        if ($request->expectsJson()) {
            return response()->json($notifications);
        }

        return inertia('Notifications/Index', [
            'notifications' => $notifications->items(),
            'pagination' => $notifications->toArray(),
        ]);
    }

    public function show($id)
    {
        $notification = $this->notificationQuery()
            ->where('notifiable_id', auth()->id())
            ->where('notifiable_type', auth()->user()->getMorphClass())
            ->whereKey($id)
            ->firstOrFail();

        return inertia('Admin/Notifications/Show', [
            'notification' => $notification,
        ]);
    }

    public function markAsRead(DatabaseNotification $notification)
    {
        $notification = $this->ownedNotification($notification);
        $notification->markAsRead();

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function markAsUnread(DatabaseNotification $notification)
    {
        $notification = $this->ownedNotification($notification);
        $notification->markAsUnread();

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function markAllAsRead()
    {
        $this->ownership->scopeStoredNotifications(auth()->user()->unreadNotifications()->getQuery(), auth()->user())
            ->update(['read_at' => now()]);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function destroy(DatabaseNotification $notification)
    {
        $notification = $this->ownedNotification($notification);
        $notification->delete();

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function clearAll()
    {
        auth()->user()->notifications()->delete();

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function clearRead()
    {
        auth()->user()->readNotifications()->delete();

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function enableSMSNotifications()
    {
        //
        DB::transaction(function (): void {

            auth()->user()->update([
                'is_active_sms' => true,
            ]);

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function disableSMSNotifications()
    {
        //
        DB::transaction(function (): void {

            auth()->user()->update([
                'is_active_sms' => false,
            ]);

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function enableWhatsAppNotifications()
    {
        //
        DB::transaction(function (): void {

            auth()->user()->update([
                'is_active_whatsapp' => true,
            ]);

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function disableWhatsAppNotifications()
    {
        //
        DB::transaction(function (): void {

            auth()->user()->update([
                'is_active_whatsapp' => false,
            ]);

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    /**
     * Admin notification dashboard
     */
    public function adminDashboard(Request $request)
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        $stats = $this->getNotificationStats();
        $recentNotifications = $this->getRecentNotifications();
        $users = $this->laboratoryUsers()->select('id', 'name', 'email', 'created_at')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $user->unread_count = $this->administrativeNotificationQuery()->where('notifiable_id', $user->id)
                    ->where('notifiable_type', $user->getMorphClass())->whereNull('read_at')->count();

                return $user;
            });

        return inertia('Admin/Notifications/Dashboard', [
            'stats' => $stats,
            'recentNotifications' => $recentNotifications,
            'users' => $users,
            'notificationTypes' => $this->getNotificationTypes(),
        ]);
    }

    private function ownedNotification(DatabaseNotification $notification): DatabaseNotification
    {
        abort_unless(
            $notification->notifiable_id === auth()->id()
            && $notification->notifiable_type === auth()->user()->getMorphClass()
            && $this->notificationQuery()->whereKey($notification->getKey())->exists(),
            403
        );

        return $notification;
    }

    /**
     * Admin notifications index with filtering
     */
    public function adminIndex(Request $request)
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        $query = $this->administrativeNotificationQuery()->with('notifiable')
            ->select('notifications.*')
            ->join('users', 'users.id', '=', 'notifications.notifiable_id')
            ->where('notifications.notifiable_type', (new User)->getMorphClass())
            ->addSelect('users.name as user_name', 'users.email as user_email');

        // Apply filters
        if ($request->filled('type')) {
            $query->where('notifications.type', $request->type);
        }

        if ($request->filled('read_status')) {
            if ($request->read_status === 'read') {
                $query->whereNotNull('notifications.read_at');
            } else {
                $query->whereNull('notifications.read_at');
            }
        }

        if ($request->filled('user_id')) {
            $query->where('notifications.notifiable_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('notifications.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('notifications.created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('notifications.data', 'like', "%{$search}%");
            });
        }

        $notifications = $query->orderBy('notifications.created_at', 'desc')
            ->paginate(25)
            ->through(function ($notification) {
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'user_id' => $notification->notifiable_id,
                    'user_name' => $notification->user_name,
                    'user_email' => $notification->user_email,
                    'type' => $notification->type,
                    'title' => $data['title'] ?? 'Sem título',
                    'message' => $data['message'] ?? $data['body'] ?? '',
                    'priority' => $data['priority'] ?? 'normal',
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'created_at_human' => $notification->created_at->diffForHumans(),
                ];
            });

        $users = $this->laboratoryUsers()->select('id', 'name', 'email')->get();

        return inertia('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'users' => $users,
            'filters' => $request->only(['type', 'read_status', 'user_id', 'date_from', 'date_to', 'search']),
            'notificationTypes' => $this->getNotificationTypes(),
        ]);
    }

    /**
     * Create notification page
     */
    public function adminCreate()
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        $users = $this->laboratoryUsers()->select('id', 'name', 'email', 'created_at')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $user->unread_count = $this->administrativeNotificationQuery()->where('notifiable_id', $user->id)
                    ->where('notifiable_type', $user->getMorphClass())->whereNull('read_at')->count();

                return $user;
            });

        $userGroups = $this->getUserGroups();

        return inertia('Admin/Notifications/Create', [
            'users' => $users,
            'userGroups' => $userGroups,
            'notificationTypes' => $this->getNotificationTypes(),
            'defaultTemplates' => $this->getNotificationTemplates(),
        ]);
    }

    /**
     * Store new notification
     */
    public function adminStore(Request $request)
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        $labId = $this->laboratoryAccess->activeLabId();

        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,success,warning,error,alert',
            'priority' => 'required|in:low,normal,high,urgent',
            'recipient_type' => 'required|in:specific,group,all',
            'recipients' => 'required_if:recipient_type,specific|array|min:1',
            'recipients.*' => [Rule::exists('lab_user', 'user_id')->where('lab_id', $labId)],
            'group' => 'required_if:recipient_type,group|in:all,active,new,admins,unverified',
            'schedule_send' => 'nullable|boolean',
            'scheduled_at' => 'prohibited',
            'expires_at' => 'prohibited',
        ]);

        $sender = auth()->user();
        $recipients = $this->getRecipients($request);

        if ($recipients->isEmpty()) {
            return back()->withErrors(['recipients' => 'Nenhum destinatário seleccionado.']);
        }

        if ($request->boolean('schedule_send')) {
            return back()->withErrors([
                'scheduled_at' => 'As notificações agendadas ainda não estão disponíveis. Envie esta notificação imediatamente.',
            ])->withInput();
        }

        $sentCount = $recipients->count();
        DB::transaction(function () use ($request, $sender, $recipients, $labId, $sentCount): void {
            BroadcastNotification::create([
                'lab_id' => $labId,
                'sender_id' => $sender->id,
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'priority' => $request->priority,
                'recipient_type' => $request->recipient_type,
                'recipient_count' => $sentCount,
                'scheduled_at' => null,
                'expires_at' => null,
            ]);

            foreach ($recipients as $user) {
                $user->notify(new GlobalNotification(
                    $request->title,
                    $request->message,
                    $sender,
                    $request->type,
                    $request->priority,
                    labId: $labId
                ));
            }
        });

        return redirect()->route('admin.notifications.index')->with([
            'toast' => [
                'type' => 'success',
                'title' => 'Notificação enviada',
                'message' => "Notificação enviada com sucesso a {$sentCount} utilizadores.",
            ],
        ]);
    }

    /**
     * Show notification details
     */
    public function adminShow($id)
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        $notification = $this->administrativeNotificationQuery()->with(['notifiable'])
            ->findOrFail($id);

        $data = $notification->data;
        $readBy = $notification->read_at ? [
            'user' => $notification->notifiable->name ?? 'Desconhecido',
            'read_at' => $notification->read_at,
            'read_at_human' => Carbon::parse($notification->read_at)->diffForHumans(),
        ] : null;

        return inertia('Admin/Notifications/Show', [
            'notification' => [
                'id' => $notification->id,
                'user_id' => $notification->notifiable_id,
                'user_name' => $notification->notifiable->name ?? 'Desconhecido',
                'user_email' => $notification->notifiable->email ?? 'Desconhecido',
                'type' => $notification->type,
                'title' => $data['title'] ?? 'Sem título',
                'message' => $data['message'] ?? $data['body'] ?? '',
                'priority' => $data['priority'] ?? 'normal',
                'sender_name' => $data['sender_name'] ?? 'Sistema',
                'sender_email' => $data['sender_email'] ?? 'system@example.com',
                'read_at' => $notification->read_at,
                'read_by' => $readBy,
                'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                'created_at_human' => $notification->created_at->diffForHumans(),
                'is_admin_notification' => $data['is_admin_notification'] ?? false,
            ],
        ]);
    }

    /**
     * Get notification analytics
     */
    public function adminAnalytics(Request $request)
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        $period = $request->get('period', 'week');
        $dateRange = $this->getDateRange($period);

        // Fix for delivery_trend query
        $deliveryTrend = [];
        $startDate = Carbon::parse($dateRange[0]);
        $endDate = Carbon::parse($dateRange[1]);

        // Get aggregated data first
        $sentData = $this->administrativeNotificationQuery()->whereBetween('created_at', $dateRange)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as sent')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $readData = $this->administrativeNotificationQuery()->whereBetween('read_at', $dateRange)
            ->selectRaw('DATE(read_at) as date, COUNT(*) as read')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Build trend data
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $deliveryTrend[$dateString] = [
                'sent' => $sentData[$dateString]->sent ?? 0,
                'read' => $readData[$dateString]->read ?? 0,
            ];
            $currentDate->addDay();
        }

        $stats = [
            'total_sent' => $this->administrativeNotificationQuery()->whereBetween('created_at', $dateRange)->count(),
            'total_read' => $this->administrativeNotificationQuery()->whereBetween('read_at', $dateRange)->count(),
            'read_rate' => 0,
            'avg_read_time' => $this->getAverageReadTime($dateRange),
            'top_users' => $this->getTopUsersWithNotifications($dateRange),
            'notification_types' => $this->getNotificationTypeDistribution($dateRange),
            'delivery_trend' => $deliveryTrend,
        ];

        if ($stats['total_sent'] > 0) {
            $stats['read_rate'] = round(($stats['total_read'] / $stats['total_sent']) * 100, 2);
        }

        return inertia('Admin/Notifications/Analytics', [
            'stats' => $stats,
            'period' => $period,
            'dateRange' => $dateRange,
        ]);
    }

    /**
     * Export notifications
     */
    public function adminExport(Request $request)
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        $query = $this->administrativeNotificationQuery()->with('notifiable')
            ->join('users', 'users.id', '=', 'notifications.notifiable_id')
            ->where('notifications.notifiable_type', (new User)->getMorphClass())
            ->select(
                'notifications.id',
                'notifications.type',
                'notifications.data',
                'notifications.read_at',
                'notifications.created_at',
                'users.name as user_name',
                'users.email as user_email'
            );

        if ($request->filled('start_date')) {
            $query->whereDate('notifications.created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('notifications.created_at', '<=', $request->end_date);
        }

        $notifications = $query->orderBy('notifications.created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = ['ID', 'Utilizador', 'Correio electrónico', 'Tipo', 'Título', 'Mensagem', 'Prioridade', 'Estado', 'Lida em', 'Criada em'];

        foreach ($notifications as $notification) {
            $data = $notification->data;
            $csvData[] = [
                $notification->id,
                $notification->user_name,
                $notification->user_email,
                $notification->type,
                $data['title'] ?? 'Sem título',
                $data['message'] ?? $data['body'] ?? '',
                $data['priority'] ?? 'normal',
                $notification->read_at ? 'Lida' : 'Não lida',
                $notification->read_at ? Carbon::parse($notification->read_at)->format('Y-m-d H:i:s') : '',
                $notification->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $filename = 'exportacao_notificacoes_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getNotificationStats()
    {
        $today = Carbon::today();
        $weekAgo = Carbon::now()->subWeek();
        $monthAgo = Carbon::now()->subMonth();

        return [
            'total' => $this->administrativeNotificationQuery()->count(),
            'unread' => $this->administrativeNotificationQuery()->whereNull('read_at')->count(),
            'today' => $this->administrativeNotificationQuery()->whereDate('created_at', $today)->count(),
            'this_week' => $this->administrativeNotificationQuery()->where('created_at', '>=', $weekAgo)->count(),
            'this_month' => $this->administrativeNotificationQuery()->where('created_at', '>=', $monthAgo)->count(),
            'read_rate' => $this->calculateReadRate(),
            'top_senders' => $this->getTopSenders(),
        ];
    }

    private function getRecentNotifications($limit = 10)
    {
        return $this->administrativeNotificationQuery()->with('notifiable')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'user_name' => $notification->notifiable->name ?? 'Desconhecido',
                    'title' => $data['title'] ?? 'Sem título',
                    'type' => $data['type'] ?? 'info',
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            });
    }

    private function getNotificationTypes()
    {
        return [
            'info' => ['label' => 'Informação', 'color' => 'blue', 'icon' => 'information-circle'],
            'success' => ['label' => 'Sucesso', 'color' => 'green', 'icon' => 'check-circle'],
            'warning' => ['label' => 'Aviso', 'color' => 'yellow', 'icon' => 'exclamation-triangle'],
            'error' => ['label' => 'Erro', 'color' => 'red', 'icon' => 'x-circle'],
            'alert' => ['label' => 'Alerta', 'color' => 'orange', 'icon' => 'bell-alert'],
        ];
    }

    private function getNotificationTemplates()
    {
        return [
            'welcome' => [
                'title' => 'Bem-vindo à nossa plataforma',
                'message' => 'Bem-vindo, {name}. Comece por explorar as funcionalidades disponíveis.',
                'type' => 'success',
            ],
            'maintenance' => [
                'title' => 'Manutenção agendada',
                'message' => 'Será realizada uma manutenção agendada em {date}. O sistema poderá ficar temporariamente indisponível.',
                'type' => 'warning',
            ],
            'update' => [
                'title' => 'Actualização do sistema disponível',
                'message' => 'Está disponível uma nova actualização do sistema. Consulte a secção de actualizações.',
                'type' => 'info',
            ],
            'security' => [
                'title' => 'Alerta de segurança',
                'message' => 'É necessária uma actualização de segurança importante. Altere imediatamente a sua palavra-passe.',
                'type' => 'alert',
            ],
        ];
    }

    private function getUserGroups()
    {
        $totalUsers = $this->laboratoryUsers()->count();
        $activeUsers = $this->laboratoryUsers()->where('last_login_at', '>=', Carbon::now()->subMonth())->count();
        $newUsers = $this->laboratoryUsers()->where('created_at', '>=', Carbon::now()->subWeek())->count();

        return [
            ['id' => 'all', 'name' => 'Todos os utilizadores', 'count' => $totalUsers, 'description' => 'Todos os utilizadores registados'],
            ['id' => 'active', 'name' => 'Utilizadores activos', 'count' => $activeUsers, 'description' => 'Utilizadores activos nos últimos 30 dias'],
            ['id' => 'new', 'name' => 'Novos utilizadores', 'count' => $newUsers, 'description' => 'Utilizadores registados nos últimos 7 dias'],
            ['id' => 'admins', 'name' => 'Administradores', 'count' => $this->laboratoryUsers()->role('admin')->count(), 'description' => 'Administradores deste laboratório'],
            ['id' => 'unverified', 'name' => 'Utilizadores não verificados', 'count' => $this->laboratoryUsers()->whereNull('email_verified_at')->count(), 'description' => 'Utilizadores deste laboratório com correio electrónico não verificado'],
        ];
    }

    private function getRecipients(Request $request)
    {
        switch ($request->recipient_type) {
            case 'specific':
                return $this->laboratoryUsers()->whereIn('id', $request->recipients ?? [])->get();
            case 'group':
                return $this->getUsersByGroup($request->group);
            case 'all':
                return $this->laboratoryUsers()->get();
            default:
                return collect();
        }
    }

    private function getUsersByGroup($group)
    {
        switch ($group) {
            case 'active':
                return $this->laboratoryUsers()->where('last_login_at', '>=', Carbon::now()->subMonth())->get();
            case 'new':
                return $this->laboratoryUsers()->where('created_at', '>=', Carbon::now()->subWeek())->get();
            case 'admins':
                return $this->laboratoryUsers()->role('admin')->get();
            case 'unverified':
                return $this->laboratoryUsers()->whereNull('email_verified_at')->get();
            default:
                return $this->laboratoryUsers()->get();
        }
    }

    private function calculateReadRate()
    {
        $total = $this->administrativeNotificationQuery()->count();
        $read = $this->administrativeNotificationQuery()->whereNotNull('read_at')->count();

        return $total > 0 ? round(($read / $total) * 100, 2) : 0;
    }

    private function getTopSenders($limit = 5)
    {
        return $this->administrativeNotificationQuery()->whereNotNull(DB::raw("data::jsonb ->> 'sender_id'"))
            ->selectRaw("data::jsonb ->> 'sender_id' as sender_id, COUNT(*) as count")
            ->groupBy('sender_id')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $user = $this->laboratoryUsers()->find($item->sender_id);

                return [
                    'name' => $user->name ?? 'Desconhecido',
                    'count' => $item->count,
                ];
            });
    }

    private function getDateRange($period)
    {
        $now = Carbon::now();

        return match ($period) {
            'day' => [$now->copy()->subDay(), $now],
            'week' => [$now->copy()->subWeek(), $now],
            'month' => [$now->copy()->subMonth(), $now],
            'quarter' => [$now->copy()->subQuarter(), $now],
            'year' => [$now->copy()->subYear(), $now],
            default => [$now->copy()->subWeek(), $now],
        };
    }

    private function getAverageReadTime($dateRange)
    {
        $notifications = $this->administrativeNotificationQuery()->whereBetween('created_at', $dateRange)
            ->whereNotNull('read_at')
            ->get();

        if ($notifications->isEmpty()) {
            return 'N/D';
        }

        $totalSeconds = 0;
        $count = 0;

        foreach ($notifications as $notification) {
            $createdAt = Carbon::parse($notification->created_at);
            $readAt = Carbon::parse($notification->read_at);
            $totalSeconds += $createdAt->diffInSeconds($readAt);
            $count++;
        }

        $averageSeconds = $totalSeconds / $count;

        if ($averageSeconds < 60) {
            return round($averageSeconds).' segundos';
        } elseif ($averageSeconds < 3600) {
            return round($averageSeconds / 60).' minutos';
        } else {
            return round($averageSeconds / 3600, 1).' horas';
        }
    }

    private function getTopUsersWithNotifications($dateRange)
    {
        return $this->administrativeNotificationQuery()->whereBetween('created_at', $dateRange)
            ->selectRaw('notifiable_id, COUNT(*) as notification_count')
            ->selectRaw('SUM(CASE WHEN read_at IS NOT NULL THEN 1 ELSE 0 END) as read_count')
            ->groupBy('notifiable_id')
            ->orderByDesc('notification_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $user = $this->laboratoryUsers()->find($item->notifiable_id);

                return [
                    'user_id' => $item->notifiable_id,
                    'user_name' => $user->name ?? 'Desconhecido',
                    'user_email' => $user->email ?? 'Desconhecido',
                    'notification_count' => $item->notification_count,
                    'read_count' => $item->read_count,
                    'read_rate' => $item->notification_count > 0
                        ? round(($item->read_count / $item->notification_count) * 100, 2)
                        : 0,
                ];
            });
    }

    private function getNotificationTypeDistribution($dateRange)
    {
        return $this->administrativeNotificationQuery()->whereBetween('created_at', $dateRange)
            ->selectRaw("data::jsonb ->> 'type' as notification_type, COUNT(*) as count")
            ->whereNotNull(DB::raw("data::jsonb ->> 'type'"))
            ->groupBy('notification_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->notification_type ?? 'info' => $item->count];
            })->toArray();
    }

    private function getDeliveryTrend($dateRange)
    {
        $startDate = Carbon::parse($dateRange[0]);
        $endDate = Carbon::parse($dateRange[1]);

        $trendData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $trendData[$dateString] = [
                'sent' => $this->administrativeNotificationQuery()->whereDate('created_at', $dateString)->count(),
                'read' => $this->administrativeNotificationQuery()->whereDate('read_at', $dateString)->count(),
            ];
            $currentDate->addDay();
        }

        return $trendData;
    }
}
