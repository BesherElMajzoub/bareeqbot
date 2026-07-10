<?php

namespace App\Enums;

enum RuleMatchType: string
{
    case Any = 'any';
    case Exact = 'exact';
    case Contains = 'contains';
    case Regex = 'regex';
}
