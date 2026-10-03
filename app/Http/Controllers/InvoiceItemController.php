<?php

namespace App\Http\Controllers;

use App\Actions\UpdateFinancialDocumentObservation;
use App\Http\Requests\FinancialDocumentObservationRequest;
use App\Models\InvoiceItem;
use Illuminate\Http\RedirectResponse;

class InvoiceItemController extends Controller
{
    public function update(FinancialDocumentObservationRequest $request, int $id, UpdateFinancialDocumentObservation $correctObservation): RedirectResponse
    {
        $correctObservation->execute($request->user()->id, (int) $request->attributes->get('proposal_laboratory_id'), InvoiceItem::class, $id, $request->validated('obs'));

        return back()->with('toast', [
            'title' => trans('gestlab.toasts.notification'),
            'message' => trans('gestlab.toasts.record_successfully_updated'),
        ]);
    }
}
