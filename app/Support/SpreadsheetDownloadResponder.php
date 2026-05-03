<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class SpreadsheetDownloadResponder
{
    public static function download(object $export, string $filename): BinaryFileResponse
    {
        $temporaryPath = 'exports-temp/' . Str::uuid() . '-' . ltrim($filename, '/');

        Excel::store($export, $temporaryPath, 'local');

        return response()->download(
            Storage::disk('local')->path($temporaryPath),
            $filename,
            disposition: ResponseHeaderBag::DISPOSITION_ATTACHMENT
        )->deleteFileAfterSend(true);
    }
}
