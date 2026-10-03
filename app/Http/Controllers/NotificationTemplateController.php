<?php

namespace App\Http\Controllers;

use App\Actions\ResetNotificationTemplate;
use App\Actions\SaveNotificationTemplate;
use App\Http\Requests\ResetNotificationTemplateRequest;
use App\Http\Requests\UpdateNotificationTemplateRequest;
use App\Http\Resources\NotificationTemplateResource;
use App\Models\NotificationTemplate;
use App\Models\VAPLab;
use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use App\Support\NotificationTemplateCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationTemplateController extends Controller
{
    public function index(Request $request, NotificationTemplateCatalog $catalog, SampleLaboratoryAccess $access, LaboratoryWorkflowOwnership $ownership): Response
    {
        $labId = $access->activeLabId();
        $operator = $ownership->eligibleUsers($labId)->find($request->user()->id);
        abort_unless($operator?->can('view_settings'), 403);
        $overrides = NotificationTemplate::query()->where('lab_id', $labId)->get()->keyBy('key');
        $templates = collect($catalog->definitions())->map(function (array $definition, string $key) use ($overrides): array {
            $override = $overrides->get($key);

            return [
                ...$definition,
                ...($override?->only(NotificationTemplate::EDITABLE_FIELDS) ?? []),
                'key' => $key, 'is_overridden' => $override !== null,
                'variables' => array_values(array_unique([...$definition['variables'], 'lab_name', 'actor_name'])),
            ];
        })->sortBy(fn (array $template): string => $template['category'].' '.$template['name'])->values();

        return Inertia::render('Admin/Notifications/Templates', [
            'templates' => NotificationTemplateResource::collection($templates)->resolve($request),
            'categories' => $templates->pluck('category')->unique()->sort()->values(),
            'laboratory' => VAPLab::query()->findOrFail($labId)->only(['id', 'name']),
            'canEdit' => $operator->can('edit_settings'),
        ]);
    }

    public function update(UpdateNotificationTemplateRequest $request, string $key, SampleLaboratoryAccess $access, SaveNotificationTemplate $save): RedirectResponse
    {
        $save->handle($request->user()->id, $access->activeLabId(), $key, $request->validated());

        return to_route('admin.notification-templates.index')->with('toast', [
            'variant' => 'success', 'title' => 'Modelo actualizado',
            'message' => 'Personalização guardada apenas para este laboratório.',
        ]);
    }

    public function destroy(ResetNotificationTemplateRequest $request, string $key, SampleLaboratoryAccess $access, ResetNotificationTemplate $reset): RedirectResponse
    {
        $reset->handle($request->user()->id, $access->activeLabId(), $key);

        return to_route('admin.notification-templates.index')->with('toast', [
            'variant' => 'success', 'title' => 'Modelo partilhado restaurado',
            'message' => 'Este laboratório voltou a usar o modelo de origem.',
        ]);
    }
}
