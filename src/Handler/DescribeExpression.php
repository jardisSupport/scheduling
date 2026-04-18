<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Handler;

/**
 * Generates a human-readable description of parsed cron fields.
 */
final class DescribeExpression
{
    private const WEEKDAY_NAMES = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    private const FIELD_MINUTE = 0;
    private const FIELD_HOUR = 1;
    private const FIELD_DAY = 2;
    private const FIELD_MONTH = 3;
    private const FIELD_WEEKDAY = 4;

    /**
     * @param array<int, list<int>|null> $fields
     */
    public function __construct(
        private readonly array $fields,
    ) {
    }

    public function __invoke(): string
    {
        $minute = $this->fields[self::FIELD_MINUTE] ?? null;
        $hour = $this->fields[self::FIELD_HOUR] ?? null;
        $day = $this->fields[self::FIELD_DAY] ?? null;
        $month = $this->fields[self::FIELD_MONTH] ?? null;
        $weekday = $this->fields[self::FIELD_WEEKDAY] ?? null;

        if ($minute === null && $hour === null && $day === null && $month === null && $weekday === null) {
            return 'Every minute';
        }

        $restWildcard = $hour === null && $day === null && $month === null && $weekday === null;

        if ($minute !== null && count($minute) > 1 && $restWildcard) {
            $step = $this->detectStep($minute);
            if ($step !== null) {
                return sprintf('Every %d minutes', $step);
            }
        }

        if ($minute !== null && count($minute) === 1 && $hour !== null && count($hour) === 1) {
            $time = sprintf('%02d:%02d', $hour[0], $minute[0]);

            if ($weekday !== null && count($weekday) === 1 && $day === null && $month === null) {
                $dayName = self::WEEKDAY_NAMES[$weekday[0]] ?? 'Day ' . $weekday[0];
                return sprintf('Weekly on %s at %s', $dayName, $time);
            }

            if ($day !== null && count($day) === 1 && $weekday === null && $month === null) {
                return sprintf('Monthly on day %d at %s', $day[0], $time);
            }

            if ($day === null && $month === null && $weekday === null) {
                return sprintf('Daily at %s', $time);
            }
        }

        return 'Custom schedule';
    }

    /**
     * @param list<int> $values
     */
    private function detectStep(array $values): ?int
    {
        if (count($values) < 2) {
            return null;
        }

        if ($values[0] !== 0) {
            return null;
        }

        $step = $values[1] - $values[0];

        for ($i = 2; $i < count($values); $i++) {
            if ($values[$i] - $values[$i - 1] !== $step) {
                return null;
            }
        }

        return $step;
    }
}
