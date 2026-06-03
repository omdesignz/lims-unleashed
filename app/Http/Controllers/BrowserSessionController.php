<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BrowserSessionController extends Controller
{
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current-password'],
        ]);

        Auth::logoutOtherDevices($request->password);

        $this->deleteOtherSessionRecords($request);

        return back()->with('status', 'other-browser-sessions-terminated');
    }

    public function destroyPortal(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password:portal'],
        ]);

        Auth::guard('portal')->logoutOtherDevices($request->password);

        $this->deleteOtherSessionRecords($request);

        return back()->with('status', 'portal-other-browser-sessions-terminated');
    }

    protected function deleteOtherSessionRecords(Request $request)
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
