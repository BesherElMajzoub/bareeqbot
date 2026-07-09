<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Starter', 'max_pages' => 1, 'monthly' => 29],
            ['name' => 'Growth', 'max_pages' => 5, 'monthly' => 99],
            ['name' => 'Business', 'max_pages' => 15, 'monthly' => 249],
            ['name' => 'Agency', 'max_pages' => 50, 'monthly' => 699],
        ];

        // Longer durations get a discount off the monthly rate.
        $durations = [1 => 1.00, 3 => 0.95, 6 => 0.90, 12 => 0.80];

        foreach ($plans as $sort => $data) {
            $plan = Plan::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'max_pages' => $data['max_pages'],
                    'features' => ['max_pages' => $data['max_pages']],
                    'is_active' => true,
                    'sort' => $sort,
                ],
            );

            foreach ($durations as $months => $multiplier) {
                PlanPrice::updateOrCreate(
                    ['plan_id' => $plan->id, 'duration_months' => $months, 'currency' => 'SAR'],
                    ['price' => round($data['monthly'] * $months * $multiplier, 2), 'is_active' => true],
                );
            }
        }
    }
}
