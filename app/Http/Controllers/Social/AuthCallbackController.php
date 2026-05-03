<?php

namespace App\Http\Controllers\Social;

use App\Factories\Social\CreateUserFactory;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthCallbackController extends Controller
{
    public function __invoke(string $service)
    {
        abort_unless(in_array($service, ['google', 'github', 'microsoft'], true), 404);

        try {
            auth()->login(
                $user = app(CreateUserFactory::class)
                    ->forService($service)
                    ->create(Socialite::driver($service)->user())
            );
        } catch (Throwable) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'social' => 'Não foi possível concluir o login social. Verifique a configuração do provedor e tente novamente.',
                ]);
        }

        if ($user->wasRecentlyCreated) {
            event(new Registered($user));
        }

        return to_route('dashboard');
    }
}
