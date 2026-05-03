<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * @param $request
     * @return mixed
     */

     public function toResponse($request)
     {  
        
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