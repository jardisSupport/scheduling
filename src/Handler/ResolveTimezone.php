<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Handler;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Converts a DateTimeInterface to DateTimeImmutable in the target timezone.
 */
final class ResolveTimezone
{
    public function __construct(
        private readonly ?DateTimeZone $timezone,
    ) {
    }

    public function __invoke(DateTimeInterface $dateTime): DateTimeImmutable
    {
        $immutable = $dateTime instanceof DateTimeImmutable
            ? $dateTime
            : DateTimeImmutable::createFromInterface($dateTime);

        if ($this->timezone !== null) {
            $immutable = $immutable->setTimezone($this->timezone);
        }

        return $immutable;
    }
}
