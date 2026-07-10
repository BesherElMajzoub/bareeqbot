<?php

use App\Enums\PlanPlatformScope;
use App\Models\Plan;
use App\Models\PlanPrice;
use Database\Seeders\PlanSeeder;

test('it seeds four plans and thirty-two prices', function () {
    $this->seed(PlanSeeder::class);

    expect(Plan::count())->toBe(4)
        ->and(PlanPrice::count())->toBe(32)
        ->and(Plan::where('slug', 'starter')->value('max_pages'))->toBe(1)
        ->and(Plan::where('slug', 'growth')->value('max_pages'))->toBe(5)
        ->and(Plan::where('slug', 'business')->value('max_pages'))->toBe(15)
        ->and(Plan::where('slug', 'agency')->value('max_pages'))->toBe(50);
});

test('each plan has one price per duration and platform scope', function () {
    $this->seed(PlanSeeder::class);

    $growthId = Plan::where('slug', 'growth')->value('id');

    $durations = PlanPrice::where('plan_id', $growthId)
        ->where('platform_scope', PlanPlatformScope::Facebook)
        ->pluck('duration_months')
        ->sort()
        ->values()
        ->all();

    expect($durations)->toBe([1, 3, 6, 12])
        ->and(PlanPrice::where('plan_id', $growthId)->count())->toBe(8);
});

test('facebook+instagram pricing is higher than facebook-only for the same duration', function () {
    $this->seed(PlanSeeder::class);

    $growthId = Plan::where('slug', 'growth')->value('id');

    $fbOnly = PlanPrice::where('plan_id', $growthId)
        ->where('duration_months', 1)
        ->where('platform_scope', PlanPlatformScope::Facebook)
        ->value('price');

    $fbAndIg = PlanPrice::where('plan_id', $growthId)
        ->where('duration_months', 1)
        ->where('platform_scope', PlanPlatformScope::FacebookInstagram)
        ->value('price');

    expect((float) $fbAndIg)->toBeGreaterThan((float) $fbOnly);
});

test('the plan seeder is idempotent', function () {
    $this->seed(PlanSeeder::class);
    $this->seed(PlanSeeder::class);

    expect(Plan::count())->toBe(4)
        ->and(PlanPrice::count())->toBe(32);
});
