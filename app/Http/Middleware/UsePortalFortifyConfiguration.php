<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UsePortalFortifyConfiguration
{
    /**
     * Apply the customer portal Fortify guard and password broker for this request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        config()->set('fortify.guard', 'portal');
        config()->set('fortify.home', 'portal/home');
        config()->set('fortify.passwords', 'warehouses');

        return $next($request);
    }
}
