<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an action would exceed the current plan's limit for a
 * given feature, or when a plan-gated feature is not enabled.
 *
 * Controllers should catch this (or let it bubble to the default
 * exception handler) and present it as a 403 with an upgrade prompt.
 */
class PlanLimitExceededException extends RuntimeException
{
    public static function forFeature(string $featureKey, ?int $limit = null): self
    {
        if ($limit !== null) {
            return new self("Plan limit reached for '{$featureKey}' (limit: {$limit}). Please upgrade your plan.");
        }

        return new self("This feature ('{$featureKey}') is not available on your current plan. Please upgrade your plan.");
    }
}