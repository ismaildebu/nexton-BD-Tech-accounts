<?php

declare(strict_types=1);

use App\Support\Money;

it('adds and subtracts without float error', function () {
    expect(Money::add('0.1', '0.2'))->toBe('0.300000')
        ->and(Money::add(0.1, 0.2))->toBe('0.300000')
        ->and(Money::sub('1.10', '1.00'))->toBe('0.100000')
        ->and(Money::cmp(Money::add('0.1', '0.2'), '0.3'))->toBe(0);
});

it('multiplies exactly and rounds half away from zero to 2 places', function () {
    // 3 x 10.555 = 31.665 -> 31.67 (a float product can land on 31.66)
    expect(Money::round(Money::mul('3', '10.555')))->toBe('31.67')
        ->and(Money::round('1.005'))->toBe('1.01')
        ->and(Money::round('2.675'))->toBe('2.68')
        ->and(Money::round('1.0049'))->toBe('1.00')
        ->and(Money::round('-1.005'))->toBe('-1.01')
        ->and(Money::round('0'))->toBe('0.00');
});

it('sums a list of decimal strings exactly', function () {
    expect(Money::cmp(Money::sum(['0.1', '0.2', '0.3']), '0.6'))->toBe(0)
        ->and(Money::sum([]))->toBe('0');
});

it('compares at the 4 decimals the ledger stores', function () {
    expect(Money::cmp('1000.0000', '1000'))->toBe(0)
        ->and(Money::cmp('1000.0001', '1000.0000'))->toBe(1)
        ->and(Money::cmp('999.9999', '1000'))->toBe(-1)
        ->and(Money::isPositive('0.0001'))->toBeTrue()
        ->and(Money::isPositive('0'))->toBeFalse()
        ->and(Money::isZero('0.0000'))->toBeTrue();
});

it('formats the 4 decimal string the ledger expects', function () {
    expect(Money::fixed('5'))->toBe('5.0000')
        ->and(Money::fixed('5.5'))->toBe('5.5000')
        ->and(Money::fixed(5.25))->toBe('5.2500');
});

it('treats null and empty as zero and rejects text', function () {
    expect(Money::of(null))->toBe('0')
        ->and(Money::of(''))->toBe('0');

    expect(fn () => Money::of('abc'))->toThrow(InvalidArgumentException::class);
});
