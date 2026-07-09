<?php

namespace Tests\Fixtures;

use App\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Minimal tenant-owned model backed by the `tenancy_test_fixtures` table,
 * used to exercise the BelongsToTenant trait in isolation.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 */
class TenancyTestFixture extends Model
{
    use BelongsToTenant;

    protected $table = 'tenancy_test_fixtures';

    protected $guarded = [];
}
