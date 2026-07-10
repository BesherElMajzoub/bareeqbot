<?php

namespace App\Http\Requests\Rules;

class UpdateRuleRequest extends RuleFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('automationRule')) ?? false;
    }
}
