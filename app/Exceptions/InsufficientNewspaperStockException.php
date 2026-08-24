<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by NewspaperStockService::removeStock() whenever a distribution,
 * damage, or negative adjustment would take a publication's stock below
 * zero. The system must never allow negative newspaper stock — this
 * exception is how that rule is enforced and surfaced to the caller.
 */
final class InsufficientNewspaperStockException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $available = 0,
        public readonly int $required = 0,
    ) {
        parent::__construct($message);
    }
}
