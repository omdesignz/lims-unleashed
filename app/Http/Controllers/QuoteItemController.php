<?php

namespace App\Http\Controllers;

use App\Actions\UpdateQuoteItem;
use App\Http\Requests\QuoteItemRequest;
use Illuminate\Http\RedirectResponse;

class QuoteItemController extends Controller
{
    public function update(QuoteItemRequest $request, int $id, UpdateQuoteItem $updateItem): RedirectResponse
    {
        $updateItem->execute($request->user()->id, (int) $request->attributes->get('proposal_laboratory_id'),
            $id, $request->validated());

        return back()->with('toast', [
            'title' => trans('gestlab.toasts.notification'),
            'message' => trans('gestlab.toasts.record_successfully_updated'),
        ]);
    }
}
