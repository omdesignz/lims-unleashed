<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        if ($this->requestIsPortal($request)) {
            return Redirect::to(route('portal.login'));
        }

        return Redirect::to(route('login'));
    }

    private function requestIsPortal(Request $request): bool
    {
        return $request->is('portal') || $request->is('portal/*');
    }
}
