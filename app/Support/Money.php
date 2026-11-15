<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Exact money arithmetic on decimal strings (bcmath).
 *
 * Never converts a decimal string to float. Money values are strings; every
 * result keeps up to 6 decimal places internally, `fixed()` gives the 4
 * places the ledger stores, `round()` gives the 2 places of a document total.
 */
final class Money
{
    private const WORK_SCALE = 6;

    private const LEDGER_SCALE = 4;

    /**
     * Normalise any numeric input to a plain decimal string.
     *
     * @throws InvalidArgumentException when the value is not numeric
     */
    public static function of(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return number_format($value, self::WORK_SCALE, '.', '');
        }

        if (is_string($value)) {
            $value = trim($value);

            if (preg_match('/^-?\d+(\.\d+)?$/', $value) === 1) {
                return $value;
            }

            if (is_numeric($value)) {
                // e.g. "1e3": only reached for odd input, never for database decimals
                return number_format((float) $value, self::WORK_SCALE, '.', '');
            }
        }

        throw new InvalidArgumentException('Not a numeric money value.');
    }

    public static function add(mixed $a, mixed $b): string
    {
        return bcadd(self::of($a), self::of($b), self::WORK_SCALE);
    }

    public static function sub(mixed $a, mixed $b): string
    {
        return bcsub(self::of($a), self::of($b), self::WORK_SCALE);
    }

    public static function mul(mixed $a, mixed $b): string
    {
        return bcmul(self::of($a), self::of($b), self::WORK_SCALE);
    }

    /**
     * @param  iterable<mixed>  $values
     */
    public static function sum(iterable $values): string
    {
        $total = '0';

        foreach ($values as $value) {
            $total = self::add($total, $value);
        }

        return $total;
    }

    /**
     * Compare at ledger precision (4 decimals): -1, 0 or 1.
     */
    public static function cmp(mixed $a, mixed $b): int
    {
        return bccomp(self::of($a), self::of($b), self::LEDGER_SCALE);
    }

    public static function isPositive(mixed $value): bool
    {
        return self::cmp($value, '0') > 0;
    }

    public static function isZero(mixed $value): bool
    {
        return self::cmp($value, '0') === 0;
    }

    /**
     * Round half away from zero (the way a decimal column rounds), default 2 places.
     */
    public static function round(mixed $value, int $scale = 2): string
    {
        $value = self::of($value);
        $half = '0.' . str_repeat('0', $scale) . '5';

        return bccomp($value, '0', self::WORK_SCALE) >= 0
            ? bcadd($value, $half, $scale)
            : bcsub($value, $half, $scale);
    }

    /**
     * The 4-decimal string the ledger stores (debit_amount / credit_amount).
     */
    public static function fixed(mixed $value): string
    {
        return bcadd(self::of($value), '0', self::LEDGER_SCALE);
    }
}
