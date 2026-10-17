<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Clock;

use DateInterval;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * PSR-20 clock frozen at a given instant. Immutable: advance() returns a new clock.
 */
final readonly class FixedClock implements ClockInterface
{
    public function __construct(private DateTimeImmutable $now)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(DateInterval $interval): static
    {
        return new static($this->now->add($interval));
    }
}
