<?php

namespace App\Support;

use App\Models\Tenant;

/**
 * Holds the tenant the current execution is acting for.
 *
 * Bound as a singleton. In web/API requests it is set by the
 * SetCurrentTenant middleware from the authenticated user. In queued
 * webhook jobs there is NO authenticated user — the tenant must be set
 * manually (resolved from the Meta asset id) before touching any
 * tenant-scoped model.
 */
class TenantContext
{
    protected ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function current(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }

    public function forget(): void
    {
        $this->tenant = null;
    }

    /**
     * Run a callback within a given tenant context, restoring the previous one after.
     *
     * @template TReturn
     *
     * @param  callable():TReturn  $callback
     * @return TReturn
     */
    public function run(Tenant $tenant, callable $callback): mixed
    {
        $previous = $this->tenant;
        $this->tenant = $tenant;

        try {
            return $callback();
        } finally {
            $this->tenant = $previous;
        }
    }
}
