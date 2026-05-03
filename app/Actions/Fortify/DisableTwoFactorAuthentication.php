<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Actions\DisableTwoFactorAuthentication as BaseDisableTwoFactorAuthentication;

class DisableTwoFactorAuthentication extends BaseDisableTwoFactorAuthentication
{
    /**
     * Disable two factor authentication for the user.
     *
     * @param  mixed  $user
     * @return void
     */
    public function __invoke($user)
    {
        parent::__invoke($user);
        
        // Clear two_factor_confirmed_at when 2FA is disabled
        $user->forceFill([
            'two_factor_confirmed_at' => null,
        ])->save();
    }
}