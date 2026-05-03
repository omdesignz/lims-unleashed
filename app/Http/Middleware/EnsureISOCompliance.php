<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Activitylog\Facades\CauserResolver;

class EnsureISOCompliance
{
    public function handle(Request $request, Closure $next)
    {
        // Always set the causer for activity logs
        if ($user = $request->user()) {
            CauserResolver::setCauser($user);
        }
        
        // Add ISO compliance headers for audit trail
        $response = $next($request);
        
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            // Check if the request contains ISO-required fields
            if ($request->has('change_reason') && empty($request->input('change_reason'))) {
                return back()->withErrors([
                    'change_reason' => 'Change reason is required for ISO 17025 compliance.'
                ]);
            }
        }
        
        return $response;
    }
}