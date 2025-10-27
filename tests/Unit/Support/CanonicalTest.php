<?php

namespace Tests\Unit\Support;

use App\Support\Canonical;
use PHPUnit\Framework\TestCase;

class CanonicalTest extends TestCase
{
    public function testIsCanonicalReturnsTrueForValidFields(): void
    {
        foreach (Canonical::FIELDS as $field) {
            $this->assertTrue(Canonical::isCanonical($field), "Expected {$field} to be canonical");
        }
    }

    public function testIsCanonicalReturnsFalseForInvalidFields(): void
    {
        $this->assertFalse(Canonical::isCanonical('UnknownField'));
        $this->assertFalse(Canonical::isCanonical('productname'));
        $this->assertFalse(Canonical::isCanonical('PRICE'));
    }

    public function testFieldsConstantContainsExpectedValues(): void
    {
        $expected = ['ProductName', 'Quantity', 'Price', 'SKU'];
        $this->assertSame($expected, Canonical::FIELDS);
    }
}
