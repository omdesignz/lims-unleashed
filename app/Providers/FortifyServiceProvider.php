<?php

namespace App\Providers;

use Inertia\Inertia;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\DisableTwoFactorAuthentication;
use App\Actions\Fortify\EnableTwoFactorAuthentication;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication as BaseEnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication as BaseDisableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        if(request()->is('portal/*')) {
            config()->set('fortify.guard', 'portal');
            config()->set('fortify.home', 'portal/home');
        }

        // Bind custom EnableTwoFactorAuthentication action
        $this->app->singleton(
            BaseEnableTwoFactorAuthentication::class,
            EnableTwoFactorAuthentication::class
        );
        
        // Bind custom DisableTwoFactorAuthentication action
        $this->app->singleton(
            BaseDisableTwoFactorAuthentication::class,
            DisableTwoFactorAuthentication::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing(function (Request $request) {
            $username = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'nif';

            if(request()->is('portal/*')) {
                $request->session()->put('fortify.portal_login_attempt', true);

                $user = Warehouse::where($username, $request->email)->first();

            } else {
                $request->session()->forget('fortify.portal_login_attempt');

                $user = User::where($username, $request->email)->first();
            }

            if ($user &&
                    Hash::check($request->password, $user->password)) {
                    return $user;
                }
        });

        Fortify::loginView(function () {
            if(request()->is('portal/*')) {
                return Inertia::render('PortalAuth/Login');
            }

            return Inertia::render('Auth/Login');
        });

        Fortify::registerView(function () {
            return Inertia::render('Auth/Register');
        });

        Fortify::requestPasswordResetLinkView(function () {
            return Inertia::render('Auth/ForgotPassword');
        });

        Fortify::resetPasswordView(function () {
            return Inertia::render('Auth/ResetPassword', [
                'token' => request()->token,
                'email' => request()->email
            ]);
        });

        Fortify::confirmPasswordView(function () {
           return Inertia::render('Auth/ConfirmPassword');
        });

        Fortify::verifyEmailView(function () {
            return Inertia::render('Auth/VerifyEmail');
        });

        Fortify::twoFactorChallengeView(function () {
            if (session('fortify.portal_login_attempt')) {
                return Inertia::render('PortalAuth/TwoFactorChallenge');
            }

            return Inertia::render('Auth/TwoFactorChallenge');
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }
}
