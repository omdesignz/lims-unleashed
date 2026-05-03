<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Actions\EnableTwoFactorAuthentication as BaseEnableTwoFactorAuthentication;

class EnableTwoFactorAuthentication extends BaseEnableTwoFactorAuthentication
{
    /**
     * Enable two factor authentication for the user.
     *
     * @param  mixed  $user
     * @param  bool  $force
     * @return void
     */
    public function __invoke($user, $force = false)
    {
        parent::__invoke($user, $force);
    }
}
