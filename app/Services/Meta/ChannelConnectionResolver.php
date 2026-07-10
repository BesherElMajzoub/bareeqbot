<?php

namespace App\Services\Meta;

use App\Models\ChannelConnection;

/**
 * Resolves the ChannelConnection (and therefore the tenant) that owns a Meta
 * asset id from a webhook. Runs without the tenant scope because webhook jobs
 * have no ambient tenant — that is exactly what we are resolving here.
 */
class ChannelConnectionResolver
{
    public function forAsset(string $assetId): ?ChannelConnection
    {
        return ChannelConnection::withoutTenantScope()
            ->where(function ($query) use ($assetId) {
                $query->where('provider_account_id', $assetId)
                    ->orWhere('linked_page_id', $assetId);
            })
            ->first();
    }
}
