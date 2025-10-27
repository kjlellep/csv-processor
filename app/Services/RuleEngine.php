<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\VendorRule;

class RuleEngine
{
    public function mapToCanonical(array $assocRow, Vendor $vendor): array
    {
        $out = [];
        foreach ((array) $vendor->mappings as $canonical => $sourceHeader) {
            $out[$canonical] = $assocRow[$sourceHeader] ?? null;
        }
        return $out;
    }

    public function apply(array $row, iterable $rules): array
    {
        foreach ($rules as $rule) {
            if (!$rule instanceof VendorRule || !$rule->enabled) continue;
            $type = $rule->type->value ?? $rule->type;
            $target = $rule->target;
            $cfg = $rule->config ?? [];

            switch ($type) {
                case 'MULTIPLY':
                    if ($target !== null && isset($row[$target]) && is_numeric($row[$target])) {
                        $factor = (float)($cfg['factor'] ?? 1);
                        $scale  = (int)($cfg['scale'] ?? 2);
                        $round  = $cfg['round'] ?? 'half_up';
                        $mode = match($round){
                            'half_down'=>PHP_ROUND_HALF_DOWN,
                            'half_even'=>PHP_ROUND_HALF_EVEN,
                            default=>PHP_ROUND_HALF_UP
                        };
                        $row[$target] = number_format(
                            round(((float)$row[$target]) * $factor, $scale, $mode), $scale, '.', ''
                        );
                    }
                    break;

                case 'REGEX':
                    if ($target !== null && isset($row[$target])) {
                        $pattern = $cfg['pattern'] ?? '';
                        $replacement = $cfg['replacement'] ?? '';
                        $flags = $cfg['flags'] ?? '';
                        if ($pattern !== '') {
                            $row[$target] = preg_replace('/'.$pattern.'/'.$flags, $replacement, (string)$row[$target]);
                        }
                    }
                    break;

                case 'REMOVE':
                    if ($target !== null) unset($row[$target]);
                    break;
            }
        }
        return $row;
    }
}
