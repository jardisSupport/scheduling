<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Exception;

use InvalidArgumentException;

/**
 * Thrown when a cron expression cannot be parsed.
 */
final class InvalidCronExpressionException extends InvalidArgumentException
{
    public static function fromExpression(string $expression, string $reason = ''): self
    {
        $message = sprintf('Invalid cron expression: "%s"', $expression);

        if ($reason !== '') {
            $message .= sprintf(' (%s)', $reason);
        }

        return new self($message);
    }
}
