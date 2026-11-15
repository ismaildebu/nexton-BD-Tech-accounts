<?php

use App\Support\AmountInWords;

describe('AmountInWords (English)', function () {
    it('writes a whole amount in lakh and thousand', function () {
        expect(AmountInWords::taka('130000.00'))
            ->toBe('One Lakh Thirty Thousand Taka Only');
    });

    it('accepts the four decimal strings the database returns', function () {
        expect(AmountInWords::taka('1000.0000'))
            ->toBe('One Thousand Taka Only');
    });

    it('writes paisa', function () {
        expect(AmountInWords::taka('2500.50'))
            ->toBe('Two Thousand Five Hundred Taka and Fifty Paisa Only');
    });

    it('writes paisa on its own when there are no taka', function () {
        expect(AmountInWords::taka('0.50'))
            ->toBe('Fifty Paisa Only');
    });

    it('writes zero', function () {
        expect(AmountInWords::taka('0'))->toBe('Zero Taka Only');
        expect(AmountInWords::taka('0.00'))->toBe('Zero Taka Only');
    });

    it('writes small and round numbers', function () {
        expect(AmountInWords::taka('1'))->toBe('One Taka Only');
        expect(AmountInWords::taka('21'))->toBe('Twenty One Taka Only');
        expect(AmountInWords::taka('100'))->toBe('One Hundred Taka Only');
        expect(AmountInWords::taka('10000000'))->toBe('One Crore Taka Only');
    });

    it('writes a large mixed amount', function () {
        expect(AmountInWords::taka('12345678'))->toBe(
            'One Crore Twenty Three Lakh Forty Five Thousand '
            . 'Six Hundred Seventy Eight Taka Only'
        );
    });

    it('rounds half up to two decimals without floats', function () {
        expect(AmountInWords::taka('100.005'))
            ->toBe('One Hundred Taka and One Paisa Only');

        expect(AmountInWords::taka('10.995'))
            ->toBe('Eleven Taka Only');
    });

    it('returns an empty string for invalid or negative amounts', function () {
        expect(AmountInWords::taka(null))->toBe('');
        expect(AmountInWords::taka(''))->toBe('');
        expect(AmountInWords::taka('abc'))->toBe('');
        expect(AmountInWords::taka('-5'))->toBe('');
    });
});

describe('AmountInWords (Bengali)', function () {
    it('writes a whole amount', function () {
        expect(AmountInWords::taka('130000.00', 'bn'))
            ->toBe('এক লক্ষ ত্রিশ হাজার টাকা মাত্র');
    });

    it('writes paisa', function () {
        expect(AmountInWords::taka('2500.50', 'bn'))
            ->toBe('দুই হাজার পাঁচ শত টাকা ও পঞ্চাশ পয়সা মাত্র');
    });

    it('writes zero', function () {
        expect(AmountInWords::taka('0', 'bn'))
            ->toBe('শূন্য টাকা মাত্র');
    });

    it('writes a crore amount', function () {
        expect(AmountInWords::taka('10000000', 'bn'))
            ->toBe('এক কোটি টাকা মাত্র');
    });
});