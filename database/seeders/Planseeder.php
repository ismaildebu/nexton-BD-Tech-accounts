<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Seeds the two subscription plans: Free and Plus.
 *
 * Run:
 * php artisan db:seed --class=PlanSeeder
 *
 * Idempotent — safe to run multiple times (matches on the unique 'key').
 * Must run before PlanFeatureSeeder.
 */
class PlanSeeder extends Seeder
{
    /**
     * key => [name, description, price, billing_cycle, is_default, sort_order]
     *
     * @var array<string, array<string, mixed>>
     */
    private const PLANS = [
        'free' => [
            'name' => 'Free',
            'description' => 'Free tier for a single company with basic accounting features.',
            'price' => 0,
            'billing_cycle' => 'monthly',
            'is_default' => true,
            'sort_order' => 1,
        ],
        'plus' => [
            'name' => 'Plus',
            'description' => 'Unlimited usage with advanced reporting, exports, backup, and priority support.',
            'price' => 0,
            'billing_cycle' => 'monthly',
            'is_default' => false,
            'sort_order' => 2,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PLANS as $key => $attributes) {
            Plan::query()->updateOrCreate(
                ['key' => $key],
                [
                    ...$attributes,
                    'is_active' => true,
                ]
            );
        }
    }
}