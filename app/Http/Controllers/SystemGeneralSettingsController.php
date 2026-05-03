<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeneralSettingsRequest;
use App\Settings\GeneralSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class SystemGeneralSettingsController extends Controller
{
    public function index(GeneralSettings $settings): Response
    {
        abort_if(! auth()->user()->can('view_settings'), 403, '');

        return Inertia::render('SystemSettings/Index', [
            'settings' => $settings,
            'model' => GeneralSettings::MENU_NAME,
            'abilities' => method_exists(GeneralSettings::class, 'getAbilities') ? collect(GeneralSettings::ABILITIES)->map(function ($item) {
                return $item . '_' . GeneralSettings::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item . '_' . GeneralSettings::MENU_NAME;
            }),
            'securitySummary' => [
                'private_key_configured' => filled($settings->app_private_key),
                'public_key_configured' => filled($settings->app_public_key),
                'validation_number_configured' => filled($settings->app_agt_validation_number),
                'two_factor_supported' => Features::canManageTwoFactorAuthentication(),
            ],
        ]);
    }

    public function update(GeneralSettingsRequest $request, GeneralSettings $settings): RedirectResponse
    {
        abort_if(! auth()->user()->can('edit_settings'), 403, '');

        $settings->fill($request->validated());
        $settings->save();

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }
}
