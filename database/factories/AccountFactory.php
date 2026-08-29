<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $type  = $this->faker->randomElement([
            Account::TYPE_ASSET,
            Account::TYPE_LIABILITY,
            Account::TYPE_EQUITY,
            Account::TYPE_INCOME,
            Account::TYPE_EXPENSE,
        ]);

        $range = Account::CODE_RANGES[$type];

        return [
            'company_id'      => Company::factory(),
            'account_name'    => $this->faker->words(3, true),
            'account_code'    => $this->faker->numberBetween($range['min'], $range['max']),
            'account_type'    => $type,
            'balance_type'    => Account::defaultBalanceType($type),
            'nature'          => Account::NATURE_GENERAL,
            'level'           => 1,
            'is_system'       => false,
            'is_active'       => true,
            'opening_balance' => 0,
        ];
    }
}