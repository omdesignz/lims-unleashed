<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;
use Spatie\LaravelPasskeys\Models\Passkey;

class PasskeyController extends Controller
{
    public function registrationOptions(Request $request, GeneratePasskeyRegisterOptionsAction $generatePasskeyRegisterOptionsAction): JsonResponse
    {
        $options = $generatePasskeyRegisterOptionsAction->execute($request->user());

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

        $storePasskeyAction->execute(
            $request->user(),
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
        abort_unless((int) $passkey->authenticatable_id === (int) $request->user()->getKey(), 404);

        $passkey->delete();

        return response()->json([
            'message' => 'Passkey removida com sucesso.',
        ]);
    }
}
