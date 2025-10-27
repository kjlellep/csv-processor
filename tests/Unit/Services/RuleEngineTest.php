<?php

namespace Tests\Unit\Services;

use App\Models\Vendor;
use App\Models\VendorRule;
use App\Services\RuleEngine;
use PHPUnit\Framework\TestCase;

class RuleEngineTest extends TestCase
{
    private RuleEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new RuleEngine();
    }

    public function testMapToCanonicalMapsValuesAndFillsMissingWithNull(): void
    {
        $vendor = new Vendor();
        $vendor->mappings = [
            'SKU'         => 'sku_col',
            'Price'       => 'price_col',
            'ProductName' => 'name_col'
        ];

        $assoc = [
            'sku_col'   => 'ABC-123',
            'price_col' => '12.50'
        ];

        $mapped = $this->engine->mapToCanonical($assoc, $vendor);

        $this->assertSame([
            'SKU'          => 'ABC-123',
            'Price'        => '12.50',
            'ProductName'  => null
        ], $mapped);
    }

    public function testApplyMultiplyWithDefaultHalfUpRounding(): void
    {
        $row = ['Price' => '10'];
        $rule = new VendorRule();
        $rule->enabled = true;
        $rule->type = 'MULTIPLY';
        $rule->target = 'Price';
        $rule->config = ['factor' => 1.2455, 'scale' => 2, 'round' => 'half_up'];

        $rounded = $this->engine->apply($row, [$rule]);

        $this->assertSame('12.46', $rounded['Price']);
    }

    public function testApplyMultiplyHalfDownAndScale3(): void
    {
        $row = ['Price' => '1.005'];
        $rule = new VendorRule();
        $rule->enabled = true;
        $rule->type = 'MULTIPLY';
        $rule->target = 'Price';
        $rule->config = ['factor' => 1, 'scale' => 2, 'round' => 'half_down'];

        $rounded = $this->engine->apply($row, [$rule]);

        $this->assertSame('1.00', $rounded['Price']);
    }

    public function testApplyMultiplyIgnoresNonNumericValues(): void
    {
        $row = ['Price' => 'n/a'];
        $rule = new VendorRule();
        $rule->enabled = true;
        $rule->type = 'MULTIPLY';
        $rule->target = 'Price';
        $rule->config = ['factor' => 2, 'scale' => 2];

        $out = $this->engine->apply($row, [$rule]);

        $this->assertSame('n/a', $out['Price']);
    }

    public function testApplyRegexWithFlagsAndReplacement(): void
    {
        $row = ['SKU' => 'Apple-123'];
        $rule = new VendorRule();
        $rule->enabled = true;
        $rule->type = 'REGEX';
        $rule->target = 'SKU';
        $rule->config = [
            'pattern' => '^[^0-9]+',
            'replacement' => ''
        ];

        $out = $this->engine->apply($row, [$rule]);

        $this->assertSame('123', $out['SKU']);
    }

    public function testApplyRemoveUnsetsTargetKey(): void
    {
        $row = ['ProductName' => 'Widget', 'Price' => '10.00'];
        $rule = new VendorRule();
        $rule->enabled = true;
        $rule->type = 'REMOVE';
        $rule->target = 'Price';
        $rule->config = [];

        $out = $this->engine->apply($row, [$rule]);

        $this->assertArrayNotHasKey('Price', $out);
        $this->assertSame('Widget', $out['ProductName']);
    }

    public function testApplySkipsDisabledAndNonVendorruleEntries(): void
    {
        $row = ['Price' => '10'];

        $enabledMultiply = new VendorRule();
        $enabledMultiply->enabled = true;
        $enabledMultiply->type = 'MULTIPLY';
        $enabledMultiply->target = 'Price';
        $enabledMultiply->config = ['factor' => 2, 'scale' => 2];

        $disabledRule = new VendorRule();
        $disabledRule->enabled = false;
        $disabledRule->type = 'REMOVE';
        $disabledRule->target = 'Price';

        $out = $this->engine->apply($row, [$enabledMultiply, $disabledRule]);

        $this->assertSame('20.00', $out['Price']);
    }
}
