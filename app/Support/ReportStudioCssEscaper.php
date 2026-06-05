<?php

namespace App\Support;

class ReportStudioCssEscaper
{
    public static function quotedString(string $value): string
    {
        return str_replace(
            ['\\', '"', "\r", "\n", "\f"],
            ['\\\\', '\"', '\A ', '\A ', '\A '],
            $value
        );
    }
}
