<?php

namespace App\Http\Controllers;

use App\Models\Passkey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys as HasPasskeysContract;

class PasskeyController extends Controller
{
    public function registrationOptions(Request $request, GeneratePasskeyRegisterOptionsAction $generatePasskeyRegisterOptionsAction): JsonResponse
    {
        $authenticatable = $this->authenticatable($request);

        abort_unless($authenticatable instanceof HasPasskeysContract, 403);

        $options = $generatePasskeyRegisterOptionsAction->execute($authenticatable);

        $request->session()->put('passkeys.registration_options', $options);

        return response()->json(json_decode($options, true, 512, JSON_THROW_ON_ERROR));
    }

    public function store(Request $request, StorePasskeyAction $storePasskeyAction): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'passkey' => ['required', 'string'],
        ]);

        $passkeyOptionsJson = $request->session()->pull('passkeys.registration_options');

        if (! is_string($passkeyOptionsJson) || $passkeyOptionsJson === '') {
            return response()->json([
                'message' => 'A sessão de registo da passkey expirou. Inicie novamente.',
            ], 422);
        }

        $authenticatable = $this->authenticatable($request);

        abort_unless($authenticatable instanceof HasPasskeysContract, 403);

        $storePasskeyAction->execute(
            $authenticatable,
            $validated['passkey'],
            $passkeyOptionsJson,
            $request->getHost(),
            ['name' => $validated['name']]
        );

        return response()->json([
            'message' => 'Passkey registada com sucesso.',
        ]);
    }

    public function destroy(Request $request, Passkey $passkey): JsonResponse
    {
        $authenticatable = $this->authenticatable($request);

        abort_unless($authenticatable instanceof HasPasskeysContract, 403);
        abort_unless($passkey->authenticatable_type === $authenticatable::class, 404);
        abort_unless((int) $passkey->authenticatable_id === (int) $authenticatable->getKey(), 404);

        $passkey->delete();

        return response()->json([
            'message' => 'Passkey removida com sucesso.',
        ]);
    }

    private function authenticatable(Request $request)
    {
        if ($request->routeIs('portal.*')) {
            return $request->user('portal');
        }

        return $request->user();
    }
}
