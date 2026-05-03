<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class LoginResponse implements LoginResponseContract
{
    /**
     * @param $request
     * @return mixed
     */

     public function toResponse($request)
     {
        $request->session()->forget('fortify.portal_login_attempt');

        //  $home = '/dashboard';

        //  return Redirect::intended($home);

        if($request->is('portal/*')) {
            // $home = '/portal/home';
            return Redirect::intended(route('portal.home'));
        } else {
            // $home = '/dashboard';
            return Redirect::intended(route('dashboard'));
        }
         

        //  return Redirect::intended($home);
     }
}
