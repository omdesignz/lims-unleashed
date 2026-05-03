<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use App\Models\Notification;
use App\Notifications\GlobalNotification;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = auth()->user()->notifications()
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
        $notification = auth()->user()
            ->notifications()
            ->whereKey($id)
            ->firstOrFail();

        return inertia('Admin/Notifications/Show', [
            'notification' => $notification,
        ]);
    }

    public function store(NotificationRequest $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $title = $request->input('title');
        $message = $request->input('message');
        $sender = auth()->user();

        if ($request->filled('user_id')) {
            // Send to a specific user
            $user = User::find($request->user_id);
            $user->notify(new GlobalNotification($title, $message, $sender));
        } else {
            // Send app-wide notification
            $users = User::all();
            foreach ($users as $user) {
                $user->notify(new GlobalNotification($title, $message, $sender));
            }
        }

        return redirect()->back()->with('success', 'Notification sent successfully.');
    }

    public function markAsRead(DatabaseNotification $notification)
    {
        $notification = $this->ownedNotification($notification);
        $notification->markAsRead();
        
        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
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
            ]
        ]);
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        
        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
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
            ]
        ]);
    }
    
    public function clearAll()
    {
        auth()->user()->notifications()->delete();
        
        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }
    
    public function clearRead()
    {
        auth()->user()->readNotifications()->delete();
        
        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    public function enableSMSNotifications()
    {
        #
        DB::transaction(function (): void {

            auth()->user()->update([
                'is_active_sms' => true
            ]);

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    public function disableSMSNotifications()
    {
        #
        DB::transaction(function (): void {

            auth()->user()->update([
                'is_active_sms' => false
            ]);

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    public function enableWhatsAppNotifications()
    {
        #
        DB::transaction(function (): void {

            auth()->user()->update([
                'is_active_whatsapp' => true
            ]);

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    public function disableWhatsAppNotifications()
    {
        #
        DB::transaction(function (): void {

            auth()->user()->update([
                'is_active_whatsapp' => false
            ]);

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    /**
     * Admin notification dashboard
     */
    public function adminDashboard(Request $request)
    {
        abort_if(!auth()->user()->hasRole('admin'), 403);
        
        $stats = $this->getNotificationStats();
        $recentNotifications = $this->getRecentNotifications();
        $users = User::select('id', 'name', 'email', 'created_at')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $user->unread_count = $user->unreadNotifications()->count();
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
            && $notification->notifiable_type === auth()->user()::class,
            403
        );

        return $notification;
    }


    /**
     * Admin notifications index with filtering
     */
    public function adminIndex(Request $request)
    {
        abort_if(!auth()->user()->hasRole('admin'), 403);
        
        $query = DatabaseNotification::with('notifiable')
            ->select('notifications.*')
            ->join('users', 'users.id', '=', 'notifications.notifiable_id')
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
                    'title' => $data['title'] ?? 'No Title',
                    'message' => $data['message'] ?? $data['body'] ?? '',
                    'priority' => $data['priority'] ?? 'normal',
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'created_at_human' => $notification->created_at->diffForHumans(),
                ];
            });

        $users = User::select('id', 'name', 'email')->get();

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
        abort_if(!auth()->user()->hasRole('admin'), 403);
        
        $users = User::select('id', 'name', 'email', 'created_at')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $user->unread_count = $user->unreadNotifications()->count();
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
        abort_if(!auth()->user()->hasRole('admin'), 403);
        
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,success,warning,error,alert',
            'priority' => 'required|in:low,normal,high,urgent',
            'recipient_type' => 'required|in:specific,group,all',
            'recipients' => 'nullable|array',
            'recipients.*' => 'exists:users,id',
            'schedule_send' => 'nullable|boolean',
            'scheduled_at' => 'nullable|date|after:now',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $sender = auth()->user();
        $data = [
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type,
            'priority' => $request->priority,
            'sender_id' => $sender->id,
            'sender_name' => $sender->name,
            'sender_email' => $sender->email,
            'is_admin_notification' => true,
        ];

        if ($request->filled('expires_at')) {
            $data['expires_at'] = $request->expires_at;
        }

        $recipients = $this->getRecipients($request);
        
        if ($recipients->isEmpty()) {
            return back()->withErrors(['recipients' => 'No recipients selected.']);
        }

        // If scheduled, store for later sending
        if ($request->schedule_send && $request->filled('scheduled_at')) {
            return back()->withErrors([
                'scheduled_at' => 'Scheduled notifications are not available yet. Please send this notification immediately.',
            ])->withInput();
        }

        // Send immediately
        $sentCount = 0;
        foreach ($recipients as $user) {
            try {
                $user->notify(new GlobalNotification(
                    $request->title,
                    $request->message,
                    $sender
                ));
                $sentCount++;
            } catch (\Exception $e) {
                // Log error but continue with other users
                \Log::error('Failed to send notification to user ' . $user->id . ': ' . $e->getMessage());
            }
        }

        // Create a record of this broadcast
        \App\Models\BroadcastNotification::create([
            'sender_id' => $sender->id,
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type,
            'priority' => $request->priority,
            'recipient_type' => $request->recipient_type,
            'recipient_count' => $sentCount,
            'scheduled_at' => $request->scheduled_at,
            'expires_at' => $request->expires_at,
        ]);

        return redirect()->route('admin.notifications.index')->with([
            'toast' => [
                'type' => 'success',
                'title' => 'Notification Sent',
                'message' => "Notification sent to {$sentCount} users successfully.",
            ]
        ]);
    }


    /**
     * Show notification details
     */
    public function adminShow($id)
    {
        abort_if(!auth()->user()->hasRole('admin'), 403);
        
        $notification = DatabaseNotification::with(['notifiable'])
            ->findOrFail($id);
            
        $data = $notification->data;
        $readBy = $notification->read_at ? [
            'user' => $notification->notifiable->name ?? 'Unknown',
            'read_at' => $notification->read_at,
            'read_at_human' => Carbon::parse($notification->read_at)->diffForHumans(),
        ] : null;

        return inertia('Admin/Notifications/Show', [
            'notification' => [
                'id' => $notification->id,
                'user_id' => $notification->notifiable_id,
                'user_name' => $notification->notifiable->name ?? 'Unknown',
                'user_email' => $notification->notifiable->email ?? 'Unknown',
                'type' => $notification->type,
                'title' => $data['title'] ?? 'No Title',
                'message' => $data['message'] ?? $data['body'] ?? '',
                'priority' => $data['priority'] ?? 'normal',
                'sender_name' => $data['sender_name'] ?? 'System',
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
        abort_if(!auth()->user()->hasRole('admin'), 403);
        
        $period = $request->get('period', 'week');
        $dateRange = $this->getDateRange($period);
        
        // Fix for delivery_trend query
        $deliveryTrend = [];
        $startDate = \Carbon\Carbon::parse($dateRange[0]);
        $endDate = \Carbon\Carbon::parse($dateRange[1]);
        
        // Get aggregated data first
        $sentData = DatabaseNotification::whereBetween('created_at', $dateRange)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as sent')
            ->groupBy('date')
            ->get()
            ->keyBy('date');
            
        $readData = DatabaseNotification::whereBetween('read_at', $dateRange)
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
            'total_sent' => DatabaseNotification::whereBetween('created_at', $dateRange)->count(),
            'total_read' => DatabaseNotification::whereBetween('read_at', $dateRange)->count(),
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
        abort_if(!auth()->user()->hasRole('admin'), 403);
        
        $query = DatabaseNotification::with('notifiable')
            ->join('users', 'users.id', '=', 'notifications.notifiable_id')
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
        $csvData[] = ['ID', 'User', 'Email', 'Type', 'Title', 'Message', 'Priority', 'Status', 'Read At', 'Created At'];

        foreach ($notifications as $notification) {
            $data = $notification->data;
            $csvData[] = [
                $notification->id,
                $notification->user_name,
                $notification->user_email,
                $notification->type,
                $data['title'] ?? 'No Title',
                $data['message'] ?? $data['body'] ?? '',
                $data['priority'] ?? 'normal',
                $notification->read_at ? 'Read' : 'Unread',
                $notification->read_at ? Carbon::parse($notification->read_at)->format('Y-m-d H:i:s') : '',
                $notification->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $filename = 'notifications_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($csvData) {
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
            'total' => DatabaseNotification::count(),
            'unread' => DatabaseNotification::whereNull('read_at')->count(),
            'today' => DatabaseNotification::whereDate('created_at', $today)->count(),
            'this_week' => DatabaseNotification::where('created_at', '>=', $weekAgo)->count(),
            'this_month' => DatabaseNotification::where('created_at', '>=', $monthAgo)->count(),
            'read_rate' => $this->calculateReadRate(),
            'top_senders' => $this->getTopSenders(),
        ];
    }


    private function getRecentNotifications($limit = 10)
    {
        return DatabaseNotification::with('notifiable')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;
                return [
                    'id' => $notification->id,
                    'user_name' => $notification->notifiable->name ?? 'Unknown',
                    'title' => $data['title'] ?? 'No Title',
                    'type' => $data['type'] ?? 'info',
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            });
    }


    private function getNotificationTypes()
    {
        return [
            'info' => ['label' => 'Information', 'color' => 'blue', 'icon' => 'information-circle'],
            'success' => ['label' => 'Success', 'color' => 'green', 'icon' => 'check-circle'],
            'warning' => ['label' => 'Warning', 'color' => 'yellow', 'icon' => 'exclamation-triangle'],
            'error' => ['label' => 'Error', 'color' => 'red', 'icon' => 'x-circle'],
            'alert' => ['label' => 'Alert', 'color' => 'orange', 'icon' => 'bell-alert'],
        ];
    }


    private function getNotificationTemplates()
    {
        return [
            'welcome' => [
                'title' => 'Welcome to Our Platform!',
                'message' => 'Welcome {name}! We are excited to have you on board. Get started by exploring our features.',
                'type' => 'success',
            ],
            'maintenance' => [
                'title' => 'Scheduled Maintenance',
                'message' => 'We will be performing scheduled maintenance on {date}. The system may be temporarily unavailable.',
                'type' => 'warning',
            ],
            'update' => [
                'title' => 'System Update Available',
                'message' => 'A new update is available for the system. Please check the updates section.',
                'type' => 'info',
            ],
            'security' => [
                'title' => 'Security Alert',
                'message' => 'Important security update required. Please update your password immediately.',
                'type' => 'alert',
            ],
        ];
    }


    private function getUserGroups()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('last_login_at', '>=', Carbon::now()->subMonth())->count();
        $newUsers = User::where('created_at', '>=', Carbon::now()->subWeek())->count();

        return [
            ['id' => 'all', 'name' => 'All Users', 'count' => $totalUsers, 'description' => 'All registered users'],
            ['id' => 'active', 'name' => 'Active Users', 'count' => $activeUsers, 'description' => 'Users active in the last 30 days'],
            ['id' => 'new', 'name' => 'New Users', 'count' => $newUsers, 'description' => 'Users registered in the last 7 days'],
            ['id' => 'admins', 'name' => 'Administrators', 'count' => User::role('admin')->count(), 'description' => 'All admin users'],
            ['id' => 'unverified', 'name' => 'Unverified Users', 'count' => User::whereNull('email_verified_at')->count(), 'description' => 'Users with unverified email'],
        ];
    }


    private function getRecipients(Request $request)
    {
        switch ($request->recipient_type) {
            case 'specific':
                return User::whereIn('id', $request->recipients ?? [])->get();
            case 'group':
                return $this->getUsersByGroup($request->group);
            case 'all':
                return User::all();
            default:
                return collect();
        }
    }


    private function getUsersByGroup($group)
    {
        switch ($group) {
            case 'active':
                return User::where('last_login_at', '>=', Carbon::now()->subMonth())->get();
            case 'new':
                return User::where('created_at', '>=', Carbon::now()->subWeek())->get();
            case 'admins':
                return User::role('admin')->get();
            case 'unverified':
                return User::whereNull('email_verified_at')->get();
            default:
                return User::all();
        }
    }


    private function calculateReadRate()
    {
        $total = DatabaseNotification::count();
        $read = DatabaseNotification::whereNotNull('read_at')->count();
        
        return $total > 0 ? round(($read / $total) * 100, 2) : 0;
    }


    private function getTopSenders($limit = 5)
    {
        return DatabaseNotification::whereNotNull('data->sender_id')
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.sender_id')) as sender_id, COUNT(*) as count")
            ->groupBy('sender_id')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $user = User::find($item->sender_id);
                return [
                    'name' => $user->name ?? 'Unknown',
                    'count' => $item->count,
                ];
            });
    }


    private function getDateRange($period)
    {
        $now = Carbon::now();
        
        return match($period) {
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
        $notifications = DatabaseNotification::whereBetween('created_at', $dateRange)
            ->whereNotNull('read_at')
            ->get();

        if ($notifications->isEmpty()) {
            return 'N/A';
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
            return round($averageSeconds) . ' seconds';
        } elseif ($averageSeconds < 3600) {
            return round($averageSeconds / 60) . ' minutes';
        } else {
            return round($averageSeconds / 3600, 1) . ' hours';
        }
    }


    private function getTopUsersWithNotifications($dateRange)
    {
        return DatabaseNotification::whereBetween('created_at', $dateRange)
            ->selectRaw('notifiable_id, COUNT(*) as notification_count')
            ->selectRaw('SUM(CASE WHEN read_at IS NOT NULL THEN 1 ELSE 0 END) as read_count')
            ->groupBy('notifiable_id')
            ->orderByDesc('notification_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $user = User::find($item->notifiable_id);
                return [
                    'user_id' => $item->notifiable_id,
                    'user_name' => $user->name ?? 'Unknown',
                    'user_email' => $user->email ?? 'Unknown',
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
        return DatabaseNotification::whereBetween('created_at', $dateRange)
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) as notification_type, COUNT(*) as count")
            ->whereNotNull('data->type')
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
                'sent' => DatabaseNotification::whereDate('created_at', $dateString)->count(),
                'read' => DatabaseNotification::whereDate('read_at', $dateString)->count(),
            ];
            $currentDate->addDay();
        }
        
        return $trendData;
    }
}
