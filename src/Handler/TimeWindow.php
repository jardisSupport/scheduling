<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Handler;

use DateTimeInterface;
use JardisSupport\Contract\Scheduling\ConstraintInterface;

/**
 * Restricts task execution to a time window (between/unlessBetween).
 */
final readonly class TimeWindow implements ConstraintInterface
{
    public function __construct(
        private string $start,
        private string $end,
        private bool $inverted = false,
    ) {
    }

    public function __invoke(DateTimeInterface $now): bool
    {
        $currentTime = $now->format('H:i');
        $inWindow = $currentTime >= $this->start && $currentTime <= $this->end;

        return $this->inverted ? !$inWindow : $inWindow;
    }
}
