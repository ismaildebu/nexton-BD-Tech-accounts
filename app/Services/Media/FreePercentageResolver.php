<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\MediaParty;
use App\Models\Publication;

/**
 * FreePercentageResolver
 * ------------------------
 * Single source of truth for the free-copy percentage fallback chain:
 *
 *   1. MediaParty::free_percentage        (party-level override)
 *   2. Publication::default_free_percentage (publication-level default)
 *   3. config('media.default_free_percentage') (system default)
 *
 * A column is only treated as "set" when it is NOT NULL — an explicit
 * 0% override is respected and does NOT fall through to the next level.
 * No party/publication data is duplicated; this only reads it.
 */
final class FreePercentageResolver
{
    public function resolve(MediaParty $party, Publication $publication): float
    {
        if ($party->free_percentage !== null) {
            return (float) $party->free_percentage;
        }

        if ($publication->default_free_percentage !== null) {
            return (float) $publication->default_free_percentage;
        }

        return (float) config('media.default_free_percentage', 0);
    }

    /**
     * Which level the resolved percentage came from — useful for
     * displaying "(from publication default)" style hints in the UI.
     */
    public function source(MediaParty $party, Publication $publication): string
    {
        if ($party->free_percentage !== null) {
            return 'party';
        }

        if ($publication->default_free_percentage !== null) {
            return 'publication';
        }

        return 'system';
    }

    /**
     * Single source of truth for turning a paid quantity + free
     * percentage into an integer free-copy quantity. Every place in the
     * app that computes free copies (Daily Distribution today; Print
     * Planning, reports, etc. later) must call this — never
     * round()/floor()/ceil() the multiplication inline — so the rounding
     * behaviour can never diverge between modules.
     *
     * Rounding rule: round-half-up on paid_quantity * free_percentage / 100
     * (PHP's round() default, i.e. 0.5 always rounds away from zero).
     *
     *   paid=100, free%=10 -> 100 * 10 / 100 = 10.0   -> 10
     *   paid=75,  free%=10 -> 75  * 10 / 100 = 7.5     -> 8
     *   paid=0,   free%=10 -> 0                        -> 0
     */
    public function calculateFreeQuantity(int $paidQuantity, float $freePercentage): int
    {
        if ($paidQuantity <= 0 || $freePercentage <= 0) {
            return 0;
        }

        return (int) round($paidQuantity * $freePercentage / 100);
    }
}
