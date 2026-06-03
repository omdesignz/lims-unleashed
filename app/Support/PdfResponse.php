<?php

namespace App\Support;

use Illuminate\Http\Response;

final class PdfResponse
{
    public static function inline(object $pdf, string $filename): Response
    {
        return self::make($pdf, $filename, 'inline');
    }

    public static function download(object $pdf, string $filename): Response
    {
        return self::make($pdf, $filename, 'attachment');
    }

    private static function make(object $pdf, string $filename, string $disposition): Response
    {
        $safeFilename = str_replace('"', '', $filename);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$safeFilename.'"',
        ]);
    }
}
