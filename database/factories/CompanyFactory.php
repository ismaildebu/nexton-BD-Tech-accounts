<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'owner_id' => null,
            'company_name' => fake()->unique()->company(),
            'business_type' => 'Trading',
            'owner_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'logo' => null,
            'address' => fake()->address(),
            'city' => fake()->city(),
            'country' => 'Bangladesh',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'financial_year' => null,
            'status' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => false,
        ]);
    }

    public function media(): static
    {
        return $this->state(fn (): array => [
            'business_type' => 'Media',
        ]);
    }

    public function trading(): static
    {
        return $this->state(fn (): array => [
            'business_type' => 'Trading',
        ]);
    }
}