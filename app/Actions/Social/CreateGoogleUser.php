<?php

namespace App\Actions\Social;

use App\Actions\Social\Contracts\CreatesUser;
use App\Models\User;

class CreateGoogleUser implements CreatesUser
{
    public function create($user): User
    {
        $account = User::query()->firstOrNew([
            'email' => $user->getEmail(),
        ]);

        $account->fill([
            'name' => $account->name ?: $user->getName(),
            'google_id' => $user->getId(),
            'email_verified_at' => $account->email_verified_at ?? now(),
        ]);

        $account->save();

        return $account;
    }
}
