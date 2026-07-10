<?php

namespace App\Enums;

enum RuleTargetScope: string
{
    case All = 'all';
    case Specific = 'specific';
}
