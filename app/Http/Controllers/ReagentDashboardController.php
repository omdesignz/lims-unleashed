<?php

namespace App\Http\Controllers;

class ReagentDashboardController extends Controller
{
    public function index(): never
    {
        abort(410, 'Use the laboratory-scoped inventory analytics dashboard.');
    }
}
