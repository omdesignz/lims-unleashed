<?php

namespace App\Http\Controllers;

use App\Actions\QueueSharedDocumentDelivery;
use App\Http\Requests\ShareDocumentRequest;
use Illuminate\Http\RedirectResponse;

class DocumentShareController extends Controller
{
    public function store(ShareDocumentRequest $request, QueueSharedDocumentDelivery $action): RedirectResponse
    {
        $action->execute($request->user()->id, (int) $request->attributes->get('proposal_laboratory_id'), $request->validated());

        return back()->with('toast', [
            'variant' => 'info',
            'title' => 'Envio em processamento',
            'message' => 'O PDF será gerado e enviado em segundo plano. Receberá uma confirmação quando terminar.',
            'duration' => 8000,
        ]);
    }
}
