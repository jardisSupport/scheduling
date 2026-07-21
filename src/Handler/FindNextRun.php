<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Handler;

use Closure;
use DateInterval;
use DateTimeImmutable;
use JardisSupport\Scheduling\Exception\InvalidCronExpressionException;

/**
 * Iterates forward from a starting point until the next cron match is found.
 */
final class FindNextRun
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
        $increment = $this->hasSeconds ? 'PT1S' : 'PT1M';
        $interval = new DateInterval($increment);

        $current = $this->hasSeconds
            ? $from
            : $from->setTime((int) $from->format('G'), (int) $from->format('i'), 0);

        $current = $current->add($interval);

        for ($i = 0; $i < self::MAX_ITERATIONS; $i++) {
            if (($this->matchFields)($current)) {
                return $current;
            }
            $current = $current->add($interval);
        }

        throw InvalidCronExpressionException::fromExpression(
            'computed',
            'Unable to find next run time within reasonable range'
        );
    }
}
