<?php

namespace App\Http\Controllers;

use App\Actions\SaveDocumentLogo;
use App\Actions\SaveGeneralSettings;
use App\Http\Requests\GeneralSettingsRequest;
use App\Http\Resources\GeneralSettingsResource;
use App\Settings\GeneralSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class SystemGeneralSettingsController extends Controller
{
    public function index(GeneralSettings $settings): Response
    {
        abort_if(! auth()->user()->can('view_settings'), 403, '');

        return Inertia::render('SystemSettings/Index', [
            'settings' => GeneralSettingsResource::make($settings)->resolve(),
            'settingsRevision' => $settings->revision(),
            'canEdit' => auth()->user()->can('edit_settings'),
            'documentLogoUrl' => filled($settings->app_document_logo) && Storage::disk(SaveDocumentLogo::DISK)->exists($settings->app_document_logo)
                ? Storage::disk(SaveDocumentLogo::DISK)->url($settings->app_document_logo)
                : null,
            'model' => GeneralSettings::MENU_NAME,
            'abilities' => method_exists(GeneralSettings::class, 'getAbilities') ? collect(GeneralSettings::ABILITIES)->map(function ($item) {
                return $item.'_'.GeneralSettings::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.GeneralSettings::MENU_NAME;
            }),
            'securitySummary' => [
                'private_key_configured' => filled($settings->app_private_key),
                'public_key_configured' => filled($settings->app_public_key),
                'validation_number_configured' => filled($settings->app_agt_validation_number),
                'two_factor_supported' => Features::canManageTwoFactorAuthentication(),
            ],
        ]);
    }

    public function update(GeneralSettingsRequest $request, SaveGeneralSettings $save): RedirectResponse
    {
        $save->handle((int) $request->user()->id, $request->validated());

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    /**
     * The logo printed on generated documents: PNG or JPEG, small enough to
     * embed in every PDF.
     */
    public function updateDocumentLogo(Request $request, SaveDocumentLogo $save): RedirectResponse
    {
        abort_if(! $request->user()->can('edit_settings'), 403, '');
        $validated = $request->validate([
            'logo' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:1024', 'dimensions:min_width=32,min_height=32,max_width=4000,max_height=4000'],
        ], [], ['logo' => 'logótipo']);

        $save->store((int) $request->user()->id, $validated['logo']);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => 'O logótipo dos documentos foi actualizado.',
            ],
        ]);
    }

    public function destroyDocumentLogo(Request $request, SaveDocumentLogo $save): RedirectResponse
    {
        abort_if(! $request->user()->can('edit_settings'), 403, '');
        $save->remove((int) $request->user()->id);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => 'O logótipo dos documentos foi removido.',
            ],
        ]);
    }
}
