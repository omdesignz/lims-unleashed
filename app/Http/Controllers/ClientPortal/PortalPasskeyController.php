<?php

namespace App\Http\Controllers\ClientPortal;

use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction;

class PortalPasskeyController
{
    public function authenticationOptions(GeneratePasskeyAuthenticationOptionsAction $generateOptions): JsonResponse
    {
        return response()->json(json_decode($generateOptions->execute(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function login(Request $request, FindPasskeyToAuthenticateAction $findPasskey): RedirectResponse
    {
        $request->validate([
            'start_authentication_response' => ['required', 'string'],
        ]);

        $passkey = $findPasskey->execute(
            $request->string('start_authentication_response')->toString(),
            Session::get('passkey-authentication-options'),
        );

        if (! $passkey || $passkey->authenticatable_type !== Warehouse::class) {
            return back()->withErrors([
                'email' => 'A passkey não pertence a uma conta do portal do cliente.',
            ]);
        }

        $warehouse = $passkey->authenticatable;

        if (! $warehouse) {
            return back()->withErrors([
                'email' => 'Não foi possível encontrar a conta associada à passkey.',
            ]);
        }

        auth()->guard('portal')->login($warehouse, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('portal.home');
    }
}
