<?php

namespace App\Support;

use InvalidArgumentException;

final class InventoryQuantity
{
    public const MAX_SCALED = 999999999999999999;

    public static function toScaled(string|int|float $quantity): int
    {
        $value = (string) $quantity;
        if (! preg_match('/^-?\d+(?:\.\d{1,4})?$/', $value)) {
            throw new InvalidArgumentException('Inventory quantity must have at most four decimal places.');
        }

        $negative = str_starts_with($value, '-');
        [$integer, $fraction] = array_pad(explode('.', ltrim($value, '-'), 2), 2, '');
        $integer = ltrim($integer, '0') ?: '0';
        if (strlen($integer) > 14) {
            throw new InvalidArgumentException('Inventory quantity exceeds the database precision.');
        }
        $scaled = ((int) $integer * 10000) + (int) str_pad($fraction, 4, '0');

        return $negative ? -$scaled : $scaled;
    }

    public static function fromScaled(int $quantity): string
    {
        $absolute = abs($quantity);

        return ($quantity < 0 ? '-' : '').intdiv($absolute, 10000).'.'.str_pad((string) ($absolute % 10000), 4, '0', STR_PAD_LEFT);
    }

    public static function compare(string|int|float $left, string|int|float $right): int
    {
        return self::toScaled($left) <=> self::toScaled($right);
    }

    public static function add(string|int|float $left, string|int|float $right): string
    {
        return self::fromScaled(self::toScaled($left) + self::toScaled($right));
    }

    public static function subtract(string|int|float $left, string|int|float $right): string
    {
        return self::fromScaled(self::toScaled($left) - self::toScaled($right));
    }
}
