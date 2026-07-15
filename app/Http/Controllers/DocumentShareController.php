<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShareDocumentRequest;
use App\Jobs\SendSharedDocumentEmail;
use App\Models\DocumentDelivery;
use Illuminate\Http\RedirectResponse;

class DocumentShareController extends Controller
{
    public function store(ShareDocumentRequest $request): RedirectResponse
    {
        $delivery = DocumentDelivery::query()->create([
            ...$request->validated(),
            'sender_id' => $request->user()->id,
            'status' => 'queued',
        ]);

        SendSharedDocumentEmail::dispatch($delivery);

        activity()
            ->causedBy($request->user())
            ->withProperties([
                'document_type' => $delivery->document_type,
                'document_id' => $delivery->document_id,
                'delivery_id' => $delivery->id,
                'recipient_count' => count($delivery->recipients),
            ])
            ->log('agendou o envio de um documento por correio electrónico');

        return back()->with('toast', [
            'variant' => 'info',
            'title' => 'Envio em processamento',
            'message' => 'O PDF será gerado e enviado em segundo plano. Receberá uma confirmação quando terminar.',
            'duration' => 8000,
        ]);
    }
}
