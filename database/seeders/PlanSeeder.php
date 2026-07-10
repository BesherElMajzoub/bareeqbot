<?php

namespace Database\Seeders;

use App\Enums\PlanPlatformScope;
use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Placeholder SYP pricing — replace `monthly` per plan with real figures
 * before going live. The structure (duration discount + FB-only vs
 * FB+Instagram surcharge) is the durable part; the numbers are not.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Starter', 'max_pages' => 1, 'monthly' => 300_000],
            ['name' => 'Growth', 'max_pages' => 5, 'monthly' => 400_000],
            ['name' => 'Business', 'max_pages' => 15, 'monthly' => 500_000],
            ['name' => 'Agency', 'max_pages' => 50, 'monthly' => 900_000],
        ];

        // Longer durations get a discount off the monthly rate.
        $durations = [1 => 1.00, 3 => 0.95, 6 => 0.90, 12 => 0.80];

        // Connecting Instagram alongside Facebook costs more than Facebook alone.
        $platformMultipliers = [
            PlanPlatformScope::Facebook->value => 1.00,
            PlanPlatformScope::FacebookInstagram->value => 1.80,
        ];

        $currency = config('bariq.billing.currency');

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

            foreach ($durations as $months => $durationMultiplier) {
                foreach ($platformMultipliers as $platformScope => $platformMultiplier) {
                    PlanPrice::updateOrCreate(
                        [
                            'plan_id' => $plan->id,
                            'duration_months' => $months,
                            'currency' => $currency,
                            'platform_scope' => $platformScope,
                        ],
                        [
                            'price' => round($data['monthly'] * $months * $durationMultiplier * $platformMultiplier, 2),
                            'is_active' => true,
                        ],
                    );
                }
            }
        }
    }
}
