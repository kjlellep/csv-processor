<?php

namespace App\Support;

final class Canonical
{
    public const FIELDS = ['ProductName','Quantity','Price','SKU'];

    public static function isCanonical(string $f): bool
    {
        return in_array($f, self::FIELDS, true);
    }
}
