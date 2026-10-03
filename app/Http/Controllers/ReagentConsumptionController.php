<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class ReagentConsumptionController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('vap-inventory.reagents.consumption.index');
    }

    public function store(): never
    {
        abort(410, 'Use the controlled reagent consumption workflow.');
    }

    public function storeBatch(): never
    {
        abort(410, 'Use the controlled reagent consumption workflow.');
    }

    public function consumptionLogs(int $reagentId): never
    {
        abort(410, 'Use the controlled reagent consumption register.');
    }
}
