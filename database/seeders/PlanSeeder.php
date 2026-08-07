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
        $currency = config('bariq.billing.currency');

        $plansData = [
            [
                'name' => 'الرد الأساسي',
                'slug' => 'basic',
                'max_pages' => 1,
                'sort' => 0,
                'prices' => [
                    1 => [
                        PlanPlatformScope::Facebook->value => 30_000,
                        PlanPlatformScope::FacebookInstagram->value => 55_000,
                    ],
                    3 => [
                        PlanPlatformScope::Facebook->value => 85_000,
                        PlanPlatformScope::FacebookInstagram->value => 150_000,
                    ],
                    6 => [
                        PlanPlatformScope::Facebook->value => 160_000,
                        PlanPlatformScope::FacebookInstagram->value => 290_000,
                    ],
                    12 => [
                        PlanPlatformScope::Facebook->value => 300_000,
                        PlanPlatformScope::FacebookInstagram->value => 550_000,
                    ],
                ],
            ],
            [
                'name' => 'الرد المتقدم',
                'slug' => 'advanced',
                'max_pages' => 5,
                'sort' => 1,
                'prices' => [
                    1 => [
                        PlanPlatformScope::Facebook->value => 40_000,
                        PlanPlatformScope::FacebookInstagram->value => 75_000,
                    ],
                    3 => [
                        PlanPlatformScope::Facebook->value => 110_000,
                        PlanPlatformScope::FacebookInstagram->value => 210_000,
                    ],
                    6 => [
                        PlanPlatformScope::Facebook->value => 200_000,
                        PlanPlatformScope::FacebookInstagram->value => 400_000,
                    ],
                    12 => [
                        PlanPlatformScope::Facebook->value => 380_000,
                        PlanPlatformScope::FacebookInstagram->value => 750_000,
                    ],
                ],
            ],
            [
                'name' => 'الرد المفتوح',
                'slug' => 'open',
                'max_pages' => 15,
                'sort' => 2,
                'prices' => [
                    1 => [
                        PlanPlatformScope::Facebook->value => 50_000,
                        PlanPlatformScope::FacebookInstagram->value => 90_000,
                    ],
                    3 => [
                        PlanPlatformScope::Facebook->value => 135_000,
                        PlanPlatformScope::FacebookInstagram->value => 260_000,
                    ],
                    6 => [
                        PlanPlatformScope::Facebook->value => 260_000,
                        PlanPlatformScope::FacebookInstagram->value => 500_000,
                    ],
                    12 => [
                        PlanPlatformScope::Facebook->value => 500_000,
                        PlanPlatformScope::FacebookInstagram->value => 950_000,
                    ],
                ],
            ],
        ];

        foreach ($plansData as $data) {
            $plan = Plan::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'max_pages' => $data['max_pages'],
                    'features' => ['max_pages' => $data['max_pages']],
                    'is_active' => true,
                    'sort' => $data['sort'],
                ],
            );

            foreach ($data['prices'] as $months => $scopePrices) {
                foreach ($scopePrices as $platformScope => $price) {
                    PlanPrice::updateOrCreate(
                        [
                            'plan_id' => $plan->id,
                            'duration_months' => $months,
                            'currency' => $currency,
                            'platform_scope' => $platformScope,
                        ],
                        [
                            'price' => $price,
                            'is_active' => true,
                        ],
                    );
                }
            }
        }
    }
}
