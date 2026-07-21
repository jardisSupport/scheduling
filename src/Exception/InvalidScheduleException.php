<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Exception;

use InvalidArgumentException;

/**
 * Thrown when a schedule definition is invalid.
 */
final class InvalidScheduleException extends InvalidArgumentException
{
    public static function missingName(): self
    {
        return new self('Scheduled task requires a name');
    }

    public static function missingExpression(string $taskName): self
    {
        return new self(sprintf('Scheduled task "%s" requires a cron expression', $taskName));
    }

    public static function invalidTime(string $time): self
    {
        return new self(sprintf('Invalid time format: "%s" (expected HH:MM, e.g. "09:30")', $time));
    }
}
