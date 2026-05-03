<?php

namespace App\Lang;

enum Lang: string
{
    case PT = 'pt';
    case EN = 'en';

    public function label(): string
    {
        return match($this) {
            self::PT => 'Português',
            self::EN => 'English', 
        };
    }
}