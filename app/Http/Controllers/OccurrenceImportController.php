<?php

namespace App\Http\Controllers;

use App\Actions\ImportOccurrences;
use App\Http\Requests\ImportOccurrencesRequest;
use App\Services\SampleLaboratoryAccess;
use App\Support\OccurrenceCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OccurrenceImportController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    public function upload(ImportOccurrencesRequest $request, ImportOccurrences $import): RedirectResponse
    {
        $count = $import->execute($this->laboratoryAccess->activeLabId(), $request->user()->id, $request->file('file')->getRealPath());

        return redirect()->route('occurrences.index')->with('toast', [
            'title' => trans('gestlab.toasts.notification'),
            'message' => $count.' ocorrência(s) importada(s) com sucesso.',
        ]);
    }

    public function template(Request $request, OccurrenceCsv $csv): StreamedResponse
    {
        abort_unless($request->user()->can('add_occurrences'), 403);
        $this->laboratoryAccess->activeLabId();

        return response()->streamDownload(function () use ($csv): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, $csv->columns(), ';', '"', '');
            fclose($stream);
        }, 'modelo-ocorrencias.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
