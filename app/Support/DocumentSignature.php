<?php

namespace App\Support;

use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Log;

class DocumentSignature
{
    public function sign(string $payload): string
    {
        $privateKey = app(GeneralSettings::class)->app_private_key;

        if (blank($privateKey)) {
            return $this->fallback($payload, 'missing private key');
        }

        $opensslKey = openssl_pkey_get_private($privateKey);

        if ($opensslKey === false) {
            return $this->fallback($payload, 'invalid private key');
        }

        $signature = '';
        $signed = openssl_sign($payload, $signature, $opensslKey, OPENSSL_ALGO_SHA1);

        if (PHP_VERSION_ID < 80000 && is_resource($opensslKey)) {
            openssl_free_key($opensslKey);
        }

        if (! $signed) {
            return $this->fallback($payload, 'openssl signing failed');
        }

        return base64_encode($signature);
    }

    private function fallback(string $payload, string $reason): string
    {
        Log::warning('Falling back to digest-based document signature.', [
            'reason' => $reason,
        ]);

        return base64_encode(hash('sha1', $payload, true));
    }
}
