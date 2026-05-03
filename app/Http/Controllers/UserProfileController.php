<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Laravel\Fortify\Features;
use Spatie\LaravelPasskeys\Models\Passkey;

class UserProfileController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Profile/Show', [
            'sessions' => $this->getSessions($request),
            'tokens' => $request->user()->tokens()->orderBy('last_used_at', 'desc')->get(),
            'passkeys' => $request->user()->passkeys()
                ->latest()
                ->get(['id', 'name', 'last_used_at', 'created_at'])
                ->map(fn (Passkey $passkey) => [
                    'id' => $passkey->id,
                    'name' => $passkey->name,
                    'last_used_at' => optional($passkey->last_used_at)?->toIso8601String(),
                    'created_at' => optional($passkey->created_at)?->toIso8601String(),
                ]),
            // 'availablePermissions' => ['create', 'read', 'update', 'delete'], // Customize as needed
            // 'defaultPermissions' => ['read'],
            'mustVerifyEmail' => Features::enabled(Features::emailVerification()) && !$request->user()->hasVerifiedEmail(),
            'status' => session('status'),
            'confirmsTwoFactorAuthentication' => Features::enabled(Features::twoFactorAuthentication()) && $request->user()->two_factor_secret && $request->user()->two_factor_confirmed_at,
        ]);
    }

    public function updateProfileInformation(Request $request, UpdatesUserProfileInformation $updater)
    {
        $updater->update($request->user(), $request->validated());

        return back()->with('status', 'profile-information-updated');
    }

    public function updatePassword(Request $request, UpdatesUserPasswords $updater)
    {
        $updater->update($request->user(), $request->validated());

        return back()->with('status', 'password-updated');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'account-deleted');
    }

    private function getSessions(Request $request): array
    {
        if (!Features::enabled(Features::updateProfileInformation())) {
            return [];
        }

        return collect(
            DB::table('sessions')
                ->where('user_id', $request->user()->getAuthIdentifier())
                ->orderBy('last_activity', 'desc')
                ->get()
        )->map(function ($session) use ($request) {
            $agent = new \Jenssegers\Agent\Agent();
            $agent->setUserAgent($session->user_agent);

            return (object) [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'is_current_device' => $session->id === $request->session()->getId(),
                'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                'agent' => (object) [
                    'is_desktop' => $agent->isDesktop(),
                    'platform' => $agent->platform(),
                    'browser' => $agent->browser(),
                ],
            ];
        })->toArray();
    }
}
