<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserThemeController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', 'in:light,dark'],
        ]);

        $request->user()->update(['theme' => $validated['theme']]);

        return response()->json(['theme' => $validated['theme']]);
    }
}
