<?php

namespace App\Policies;

use App\Models\AutomationRule;
use App\Models\User;
use App\Support\TenantContext;

/**
 * Authorization for automation rules.
 *
 * Route-binding caveat: SubstituteBindings runs before SetCurrentTenant, so the
 * {automationRule} binding is NOT tenant-scoped. update()/delete() therefore
 * verify the rule's tenant_id against the active tenant explicitly.
 */
class AutomationRulePolicy
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage-rules');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage-rules');
    }

    public function update(User $user, AutomationRule $rule): bool
    {
        return $this->ownsRule($user, $rule);
    }

    public function delete(User $user, AutomationRule $rule): bool
    {
        return $this->ownsRule($user, $rule);
    }

    private function ownsRule(User $user, AutomationRule $rule): bool
    {
        if (! $user->hasPermissionTo('manage-rules')) {
            return false;
        }

        return $rule->tenant_id === $this->tenantContext->id();
    }
}
