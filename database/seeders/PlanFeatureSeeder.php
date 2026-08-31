<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Seeds the Free/Plus feature matrix onto the plans created by PlanSeeder.
 *
 * Run:
 * php artisan db:seed --class=PlanFeatureSeeder
 *
 * Idempotent — safe to run multiple times (matches on plan_id + feature_key).
 * Must run after PlanSeeder.
 *
 * Limit semantics (see also PlanLimitService):
 *   limit_value = null  -> feature has no numeric limit (pure on/off feature)
 *   limit_value = -1    -> unlimited quantity
 *   limit_value = N     -> maximum allowed quantity
 */
class PlanFeatureSeeder extends Seeder
{
    /**
     * plan key => [feature_key => [limit_value, is_enabled]]
     *
     * @var array<string, array<string, array{0: int|null, 1: bool}>>
     */
    private const MATRIX = [
        'free' => [
            'companies' => [1, true],
            'users' => [1, true],
            'financial_years' => [1, true],
            'accounts' => [30, true],
            'customers' => [20, true],
            'vendors' => [20, true],
            'products' => [20, true],
            'invoices_monthly' => [30, true],
            'expenses_monthly' => [30, true],
            'sales_orders_monthly' => [20, true],
            'purchase_orders_monthly' => [20, true],
            'journal_vouchers_monthly' => [20, true],
            'basic_reports' => [null, true],
            'advanced_reports' => [null, false],
            'pdf_export' => [null, true],
            'excel_export' => [null, false],
            'backup' => [null, false],
            'inventory' => [null, true],
            'banking' => [null, true],
            'media' => [null, false],
            'multi_user_permissions' => [null, false],
            'priority_support' => [null, false],
        ],
        'plus' => [
            'companies' => [-1, true],
            'users' => [-1, true],
            'financial_years' => [-1, true],
            'accounts' => [-1, true],
            'customers' => [-1, true],
            'vendors' => [-1, true],
            'products' => [-1, true],
            'invoices_monthly' => [-1, true],
            'expenses_monthly' => [-1, true],
            'sales_orders_monthly' => [-1, true],
            'purchase_orders_monthly' => [-1, true],
            'journal_vouchers_monthly' => [-1, true],
            'basic_reports' => [null, true],
            'advanced_reports' => [null, true],
            'pdf_export' => [null, true],
            'excel_export' => [null, true],
            'backup' => [null, true],
            'inventory' => [null, true],
            'banking' => [null, true],
            'media' => [null, true],
            'multi_user_permissions' => [null, true],
            'priority_support' => [null, true],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::MATRIX as $planKey => $features) {
            $plan = Plan::query()->where('key', $planKey)->first();

            if (! $plan) {
                // PlanSeeder must run first; fail loudly instead of silently
                // seeding orphaned/incomplete feature data.
                throw new RuntimeException(
                    "Plan '{$planKey}' not found. Run PlanSeeder before PlanFeatureSeeder."
                );
            }

            foreach ($features as $featureKey => [$limitValue, $isEnabled]) {
                PlanFeature::query()->updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'feature_key' => $featureKey,
                    ],
                    [
                        'limit_value' => $limitValue,
                        'is_enabled' => $isEnabled,
                    ]
                );
            }
        }
    }
}