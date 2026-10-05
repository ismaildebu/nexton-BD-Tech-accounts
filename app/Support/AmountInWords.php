<?php

declare(strict_types=1);

namespace App\Support;

/**
 * AmountInWords
 *
 * Writes a money amount in words using the South Asian number system
 * (Thousand, Lakh, Crore) and Taka / Paisa.
 *
 *   AmountInWords::taka('130000.00')
 *       -> "One Lakh Thirty Thousand Taka Only"
 *
 *   AmountInWords::taka('2500.50', 'bn')
 *       -> "দুই হাজার পাঁচ শত টাকা ও পঞ্চাশ পয়সা মাত্র"
 *
 * Amounts are handled as decimal strings. They are rounded half-up to
 * two decimals with bcmath, never with floats.
 */
final class AmountInWords
{
    private const EN_ONES = [
        'Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven',
        'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen',
        'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const EN_TENS = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
    ];

    /**
     * Bengali words for 0 to 99 (they are irregular, so every one is listed).
     */
    private const BN_UNDER_100 = [
        'শূন্য', 'এক', 'দুই', 'তিন', 'চার', 'পাঁচ', 'ছয়', 'সাত', 'আট', 'নয়',
        'দশ', 'এগারো', 'বারো', 'তেরো', 'চৌদ্দ', 'পনেরো', 'ষোলো', 'সতেরো', 'আঠারো', 'ঊনিশ',
        'বিশ', 'একুশ', 'বাইশ', 'তেইশ', 'চব্বিশ', 'পঁচিশ', 'ছাব্বিশ', 'সাতাশ', 'আটাশ', 'ঊনত্রিশ',
        'ত্রিশ', 'একত্রিশ', 'বত্রিশ', 'তেত্রিশ', 'চৌত্রিশ', 'পঁয়ত্রিশ', 'ছত্রিশ', 'সাঁইত্রিশ', 'আটত্রিশ', 'ঊনচল্লিশ',
        'চল্লিশ', 'একচল্লিশ', 'বিয়াল্লিশ', 'তেতাল্লিশ', 'চুয়াল্লিশ', 'পঁয়তাল্লিশ', 'ছেচল্লিশ', 'সাতচল্লিশ', 'আটচল্লিশ', 'ঊনপঞ্চাশ',
        'পঞ্চাশ', 'একান্ন', 'বাহান্ন', 'তিপ্পান্ন', 'চুয়ান্ন', 'পঞ্চান্ন', 'ছাপ্পান্ন', 'সাতান্ন', 'আটান্ন', 'ঊনষাট',
        'ষাট', 'একষট্টি', 'বাষট্টি', 'তেষট্টি', 'চৌষট্টি', 'পঁয়ষট্টি', 'ছেষট্টি', 'সাতষট্টি', 'আটষট্টি', 'ঊনসত্তর',
        'সত্তর', 'একাত্তর', 'বাহাত্তর', 'তিয়াত্তর', 'চুয়াত্তর', 'পঁচাত্তর', 'ছিয়াত্তর', 'সাতাত্তর', 'আটাত্তর', 'ঊনআশি',
        'আশি', 'একাশি', 'বিরাশি', 'তিরাশি', 'চুরাশি', 'পঁচাশি', 'ছিয়াশি', 'সাতাশি', 'অষ্টাশি', 'ঊননব্বই',
        'নব্বই', 'একানব্বই', 'বিরানব্বই', 'তিরানব্বই', 'চুরানব্বই', 'পঁচানব্বই', 'ছিয়ানব্বই', 'সাতানব্বই', 'আটানব্বই', 'নিরানব্বই',
    ];

    /**
     * Amount in words for a voucher or receipt.
     *
     * @param mixed  $amount   decimal string, int or float (non-negative)
     * @param string $language 'en' (default) or 'bn'
     *
     * Returns an empty string when the amount is not a valid
     * non-negative number.
     */
    public static function taka(mixed $amount, string $language = 'en'): string
    {
        $value = trim((string) $amount);

        if (preg_match('/^\d+(\.\d+)?$/', $value) !== 1) {
            return '';
        }

        // Round half-up to two decimals using bcmath (no floats).
        $rounded = bcadd($value, '0.005', 2);

        [$integerPart, $fraction] = explode('.', $rounded);

        $taka = (int) $integerPart;
        $paisa = (int) $fraction;

        $bengali = $language === 'bn';

        $words = '';

        if ($taka > 0 || $paisa === 0) {
            $words = ($bengali ? self::bengali($taka) : self::english($taka))
                . ($bengali ? ' টাকা' : ' Taka');
        }

        if ($paisa > 0) {
            $connector = $words === ''
                ? ''
                : ($bengali ? ' ও ' : ' and ');

            $words .= $connector
                . ($bengali ? self::bengali($paisa) : self::english($paisa))
                . ($bengali ? ' পয়সা' : ' Paisa');
        }

        return $words . ($bengali ? ' মাত্র' : ' Only');
    }

    private static function english(int $n): string
    {
        if ($n < 20) {
            return self::EN_ONES[$n];
        }

        if ($n < 100) {
            $ones = $n % 10;

            return self::EN_TENS[intdiv($n, 10)]
                . ($ones > 0 ? ' ' . self::EN_ONES[$ones] : '');
        }

        if ($n < 1000) {
            $rest = $n % 100;

            return self::EN_ONES[intdiv($n, 100)] . ' Hundred'
                . ($rest > 0 ? ' ' . self::english($rest) : '');
        }

        if ($n < 100000) {
            $rest = $n % 1000;

            return self::english(intdiv($n, 1000)) . ' Thousand'
                . ($rest > 0 ? ' ' . self::english($rest) : '');
        }

        if ($n < 10000000) {
            $rest = $n % 100000;

            return self::english(intdiv($n, 100000)) . ' Lakh'
                . ($rest > 0 ? ' ' . self::english($rest) : '');
        }

        $rest = $n % 10000000;

        return self::english(intdiv($n, 10000000)) . ' Crore'
            . ($rest > 0 ? ' ' . self::english($rest) : '');
    }

    private static function bengali(int $n): string
    {
        if ($n < 100) {
            return self::BN_UNDER_100[$n];
        }

        if ($n < 1000) {
            $rest = $n % 100;

            return self::BN_UNDER_100[intdiv($n, 100)] . ' শত'
                . ($rest > 0 ? ' ' . self::bengali($rest) : '');
        }

        if ($n < 100000) {
            $rest = $n % 1000;

            return self::bengali(intdiv($n, 1000)) . ' হাজার'
                . ($rest > 0 ? ' ' . self::bengali($rest) : '');
        }

        if ($n < 10000000) {
            $rest = $n % 100000;

            return self::bengali(intdiv($n, 100000)) . ' লক্ষ'
                . ($rest > 0 ? ' ' . self::bengali($rest) : '');
        }

        $rest = $n % 10000000;

        return self::bengali(intdiv($n, 10000000)) . ' কোটি'
            . ($rest > 0 ? ' ' . self::bengali($rest) : '');
    }
}