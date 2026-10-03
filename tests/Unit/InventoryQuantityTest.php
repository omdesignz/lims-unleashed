<?php

namespace Tests\Unit;

use App\Support\InventoryQuantity;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InventoryQuantityTest extends TestCase
{
    public function test_four_decimal_arithmetic_is_exact(): void
    {
        $this->assertSame(1, InventoryQuantity::toScaled('0.0001'));
        $this->assertSame(1, InventoryQuantity::toScaled('0000000000000000000.0001'));
        $this->assertSame('0.3000', InventoryQuantity::add('0.1000', '0.2000'));
        $this->assertSame('0.0999', InventoryQuantity::subtract('0.1000', '0.0001'));
        $this->assertSame('-0.1250', InventoryQuantity::fromScaled(-1250));
        $this->assertSame(1, InventoryQuantity::compare('1.0001', '1.0000'));
    }

    public function test_more_than_four_decimal_places_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        InventoryQuantity::toScaled('0.00001');
    }

    public function test_quantities_outside_the_database_precision_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        InventoryQuantity::toScaled('100000000000000.0000');
    }
}
