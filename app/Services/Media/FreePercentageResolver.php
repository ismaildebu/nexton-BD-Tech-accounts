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
}
