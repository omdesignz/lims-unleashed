<?php

namespace App\Helpers;

use App\Models\LabCode;

class BarcodeHelper
{
    public static function generateUniqueEAN13()
    {
        do {
            // Generate the first 12 digits
            $number = self::generateRandomNumber(12);

            // Calculate the checksum digit
            $checkDigit = self::calculateCheckDigit($number);

            // Combine the number with the checksum digit to get the full EAN-13 barcode
            $ean13 = $number . $checkDigit;

            // Check for uniqueness in the database
            $existingLabCode = LabCode::where('code', $ean13)->first();
        } while ($existingLabCode); // Repeat until a unique code is found

        return $ean13;
    }

    private static function generateRandomNumber($length)
    {
        $number = '';
        for ($i = 0; $i < $length; $i++) {
            $number .= rand(0, 9);
        }

        return $number;
    }

    private static function calculateCheckDigit($number)
    {
        // Split the number into individual digits
        $digits = str_split($number);

        // Multiply digits by 1 or 3 alternately
        $sum = 0;
        foreach ($digits as $i => $digit) {
            $multiplier = ($i % 2 == 0) ? 1 : 3;
            $sum += $digit * $multiplier;
        }

        // Calculate the check digit
        $checkDigit = (10 - ($sum % 10)) % 10;

        return $checkDigit;
    }
}
