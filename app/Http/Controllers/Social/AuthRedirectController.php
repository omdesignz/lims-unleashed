<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;

class AuthRedirectController extends Controller
{
    public function __invoke(string $service)
    {
        abort_unless(in_array($service, ['google', 'github', 'microsoft'], true), 404);

        return Socialite::driver($service)->redirect();
    }
}
