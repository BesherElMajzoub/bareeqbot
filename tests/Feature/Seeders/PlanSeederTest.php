<?php

use App\Models\Plan;
use App\Models\PlanPrice;
use Database\Seeders\PlanSeeder;

test('it seeds four plans and sixteen prices', function () {
    $this->seed(PlanSeeder::class);

    expect(Plan::count())->toBe(4)
        ->and(PlanPrice::count())->toBe(16)
        ->and(Plan::where('slug', 'starter')->value('max_pages'))->toBe(1)
        ->and(Plan::where('slug', 'growth')->value('max_pages'))->toBe(5)
        ->and(Plan::where('slug', 'business')->value('max_pages'))->toBe(15)
        ->and(Plan::where('slug', 'agency')->value('max_pages'))->toBe(50);
});

test('each plan has one price per supported duration', function () {
    $this->seed(PlanSeeder::class);

    $durations = PlanPrice::where('plan_id', Plan::where('slug', 'growth')->value('id'))
        ->pluck('duration_months')
        ->sort()
        ->values()
        ->all();

    expect($durations)->toBe([1, 3, 6, 12]);
});

test('the plan seeder is idempotent', function () {
    $this->seed(PlanSeeder::class);
    $this->seed(PlanSeeder::class);

    expect(Plan::count())->toBe(4)
        ->and(PlanPrice::count())->toBe(16);
});
