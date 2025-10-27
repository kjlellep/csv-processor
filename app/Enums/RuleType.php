<?php

namespace App\Enums;

enum RuleType: string
{
    case MULTIPLY = 'MULTIPLY';
    case REMOVE   = 'REMOVE';
    case REGEX    = 'REGEX';
}
