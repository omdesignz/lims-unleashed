<?php

namespace App\Actions\Social;

use App\Actions\Social\Contracts\CreatesUser;
use App\Models\User;

class CreateAzureUser implements CreatesUser
{
    public function create($user): User
    {
        $data = [
            'id' => $user->id,
            'avatar' => $user->avatar,
            'display_name' => $user->user['displayName'] ?? null,
            'job_title' => $user->user['jobTitle'] ?? null,
            'department' => $user->user['department'] ?? null,
            'email' => $user->user['mail'] ?? null,
            'user_principal_name' => $user->user['userPrincipalName'] ?? null,
            'office_location' => $user->user['officeLocation'] ?? null,
        ];

        $account = User::query()->firstOrNew([
            'email' => $user->getEmail(),
        ]);

        $account->fill([
            'name' => $account->name ?: $user->getName(),
            'microsoft_id' => $user->getId(),
            'email_verified_at' => $account->email_verified_at ?? now(),
            'microsoft_data' => $data,
        ]);

        $account->save();

        return $account;
    }
}
