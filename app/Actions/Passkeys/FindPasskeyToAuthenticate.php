<?php

namespace App\Actions\Passkeys;

use App\Models\User;
use App\Models\Warehouse;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Models\Passkey;

class FindPasskeyToAuthenticate extends FindPasskeyToAuthenticateAction
{
    public function execute(
        string $publicKeyCredentialJson,
        string $passkeyOptionsJson,
    ): ?Passkey {
        $passkey = parent::execute($publicKeyCredentialJson, $passkeyOptionsJson);

        if (! $passkey) {
            return null;
        }

        $expectedAuthenticatable = request()->is('portal') || request()->is('portal/*')
            ? Warehouse::class
            : User::class;

        if ($passkey->authenticatable_type !== $expectedAuthenticatable) {
            return null;
        }

        return $passkey;
    }
}
