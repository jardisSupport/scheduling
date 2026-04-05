<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Handler;

use Closure;
use DateInterval;
use DateTimeImmutable;
use JardisSupport\Scheduling\Exception\InvalidCronExpressionException;

/**
 * Iterates backward from a starting point until the previous cron match is found.
 */
final class FindPreviousRun
{
    /** ~1 year in minutes */
    private const MAX_ITERATIONS = 525960;

    /**
     * @param Closure(DateTimeImmutable): bool $matchFields
     */
    public function __construct(
        private readonly Closure $matchFields,
        private readonly bool $hasSeconds,
    ) {
    }

    public function __invoke(DateTimeImmutable $from): DateTimeImmutable
    {
        $decrement = $this->hasSeconds ? 'PT1S' : 'PT1M';
        $interval = new DateInterval($decrement);

        $current = $this->hasSeconds
            ? $from
            : $from->setTime((int) $from->format('G'), (int) $from->format('i'), 0);

        $current = $current->sub($interval);

        for ($i = 0; $i < self::MAX_ITERATIONS; $i++) {
            if (($this->matchFields)($current)) {
                return $current;
            }
            $current = $current->sub($interval);
        }

        throw InvalidCronExpressionException::fromExpression(
            'computed',
            'Unable to find previous run time within reasonable range'
        );
    }
}
