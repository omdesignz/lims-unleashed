<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Carbon;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UpdateUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if (! $user) {
            return $response;
        }

        $lastActivityAt = $user->last_activity_at;

        if (is_string($lastActivityAt) && $lastActivityAt !== '') {
            $lastActivityAt = Carbon::parse($lastActivityAt);
        }

        if ($lastActivityAt instanceof DateTimeInterface && ! $lastActivityAt instanceof Carbon) {
            $lastActivityAt = Carbon::instance($lastActivityAt);
        }

        if ($lastActivityAt instanceof Carbon && $lastActivityAt->gt(now()->subMinute())) {
            return $response;
        }

        try {
            $user->newQuery()
                ->whereKey($user->getKey())
                ->update(['last_activity_at' => now()]);
        } catch (Throwable $exception) {
            Log::warning('Unable to update user activity timestamp.', [
                'user_id' => $user->getKey(),
                'reason' => $exception->getMessage(),
            ]);
        }

        return $response;
    }
}
