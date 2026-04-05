<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Handler;

use DateTimeImmutable;

/**
 * Checks whether parsed cron fields match a given point in time.
 */
final class MatchFields
{
    private const FIELD_MINUTE  = 0;
    private const FIELD_HOUR    = 1;
    private const FIELD_DAY     = 2;
    private const FIELD_MONTH   = 3;
    private const FIELD_WEEKDAY = 4;
    private const FIELD_SECOND  = 5;
    private const FIELD_YEAR    = 6;

    /**
     * @param array<int, list<int>|null> $fields
     */
    public function __construct(
        private readonly array $fields,
    ) {
    }

    public function __invoke(DateTimeImmutable $now): bool
    {
        return $this->matchesField(self::FIELD_MINUTE, (int) $now->format('i'))
            && $this->matchesField(self::FIELD_HOUR, (int) $now->format('G'))
            && $this->matchesField(self::FIELD_DAY, (int) $now->format('j'))
            && $this->matchesField(self::FIELD_MONTH, (int) $now->format('n'))
            && $this->matchesWeekday((int) $now->format('w'))
            && $this->matchesField(self::FIELD_SECOND, (int) $now->format('s'))
            && $this->matchesField(self::FIELD_YEAR, (int) $now->format('Y'));
    }

    private function matchesField(int $fieldIndex, int $value): bool
    {
        if ($this->fields[$fieldIndex] === null) {
            return true;
        }

        return in_array($value, $this->fields[$fieldIndex], true);
    }

    private function matchesWeekday(int $weekday): bool
    {
        if ($this->fields[self::FIELD_WEEKDAY] === null) {
            return true;
        }

        $normalized = array_map(
            static fn(int $d): int => $d === 7 ? 0 : $d,
            $this->fields[self::FIELD_WEEKDAY]
        );

        return in_array($weekday, $normalized, true);
    }
}
