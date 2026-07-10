<?php

namespace App\Http\Requests\Rules;

use App\Models\AutomationRule;

class StoreRuleRequest extends RuleFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AutomationRule::class) ?? false;
    }
}
