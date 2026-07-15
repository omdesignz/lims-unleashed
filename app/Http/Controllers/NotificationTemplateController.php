<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNotificationTemplateRequest;
use App\Models\NotificationTemplate;
use App\Support\NotificationTemplateCatalog;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationTemplateController extends Controller
{
    public function index(NotificationTemplateCatalog $catalog): Response
    {
        abort_unless(auth()->user()->can('view_settings'), 403);
        $catalog->synchronize();

        return Inertia::render('Admin/Notifications/Templates', [
            'templates' => NotificationTemplate::query()->orderBy('category')->orderBy('name')->get(),
            'categories' => NotificationTemplate::query()->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function update(UpdateNotificationTemplateRequest $request, NotificationTemplate $notificationTemplate): RedirectResponse
    {
        $notificationTemplate->update($request->validated());

        return back()->with('toast', [
            'variant' => 'success',
            'title' => 'Modelo actualizado',
            'message' => 'A nova mensagem será aplicada às próximas notificações.',
        ]);
    }
}
