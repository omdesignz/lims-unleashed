<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Laravel\Sanctum\NewAccessToken;

class APITokenController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Profile/Show', [
            'tokens' => $request->user()->tokens()->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(['create', 'read', 'update', 'delete'])],
        ]);

        $token = $request->user()->createToken(
            $request->name,
            $request->permissions ?? ['*']
        );

        return back()->with([
            'plainTextToken' => $token->plainTextToken,
            'status' => 'token-created',
        ]);
    }

    public function update(Request $request, $tokenId)
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', Rule::in(['create', 'read', 'update', 'delete'])],
        ]);

        $token = $request->user()->tokens()->where('id', $tokenId)->firstOrFail();

        $token->forceFill([
            'abilities' => $request->permissions,
        ])->save();

        return back();
    }

    public function destroy(Request $request, $tokenId)
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();

        return back();
    }
}