<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $request->session()->forget('fortify.portal_login_attempt');

        if ($this->requestIsPortal($request)) {
            $request->session()->forget('url.intended');

            return Redirect::to(route('portal.home'));
        }

        return Redirect::intended(route('dashboard'));
    }

    private function requestIsPortal(Request $request): bool
    {
        return $request->is('portal') || $request->is('portal/*');
    }
}
