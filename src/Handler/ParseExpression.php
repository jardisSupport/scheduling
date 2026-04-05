<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Handler;

use JardisSupport\Scheduling\Exception\InvalidCronExpressionException;

/**
 * Parses a cron expression string into resolved field arrays.
 */
final class ParseExpression
{
    private const PREDEFINED = [
        '@yearly'   => '0 0 1 1 *',
        '@annually' => '0 0 1 1 *',
        '@monthly'  => '0 0 1 * *',
        '@weekly'   => '0 0 * * 0',
        '@daily'    => '0 0 * * *',
        '@midnight' => '0 0 * * *',
        '@hourly'   => '0 * * * *',
    ];

    private const FIELD_MINUTE  = 0;
    private const FIELD_HOUR    = 1;
    private const FIELD_DAY     = 2;
    private const FIELD_MONTH   = 3;
    private const FIELD_WEEKDAY = 4;
    private const FIELD_SECOND  = 5;
    private const FIELD_YEAR    = 6;

    /** @var array<int, array{int, int}> */
    private const FIELD_RANGES = [
        self::FIELD_SECOND  => [0, 59],
        self::FIELD_MINUTE  => [0, 59],
        self::FIELD_HOUR    => [0, 23],
        self::FIELD_DAY     => [1, 31],
        self::FIELD_MONTH   => [1, 12],
        self::FIELD_WEEKDAY => [0, 7],
        self::FIELD_YEAR    => [1970, 2099],
    ];

    /**
     * @return array<int, list<int>|null> Field index => resolved values (null = wildcard)
     */
    public function __invoke(string $expression): array
    {
        $expression = trim($expression);

        if (isset(self::PREDEFINED[strtolower($expression)])) {
            $expression = self::PREDEFINED[strtolower($expression)];
        }

        /** @var list<string> $parts */
        $parts = preg_split('/\s+/', $expression) ?: [];
        $partCount = count($parts);

        if ($partCount < 5 || $partCount > 7) {
            throw InvalidCronExpressionException::fromExpression(
                $expression,
                sprintf('Expected 5-7 fields, got %d', $partCount)
            );
        }

        $hasSeconds = $partCount >= 6;
        $hasYear = $partCount === 7;
        $offset = $hasSeconds ? 1 : 0;

        $fields = [];
        $fields[self::FIELD_SECOND] = $hasSeconds
            ? $this->parseField($parts[0], self::FIELD_SECOND, $expression)
            : null;
        $fields[self::FIELD_MINUTE] = $this->parseField($parts[$offset], self::FIELD_MINUTE, $expression);
        $fields[self::FIELD_HOUR] = $this->parseField($parts[$offset + 1], self::FIELD_HOUR, $expression);
        $fields[self::FIELD_DAY] = $this->parseField($parts[$offset + 2], self::FIELD_DAY, $expression);
        $fields[self::FIELD_MONTH] = $this->parseField($parts[$offset + 3], self::FIELD_MONTH, $expression);
        $fields[self::FIELD_WEEKDAY] = $this->parseField($parts[$offset + 4], self::FIELD_WEEKDAY, $expression);
        $fields[self::FIELD_YEAR] = $hasYear
            ? $this->parseField($parts[6], self::FIELD_YEAR, $expression)
            : null;

        return $fields;
    }

    /**
     * @return list<int>|null
     */
    private function parseField(string $field, int $fieldIndex, string $expression): ?array
    {
        if ($field === '*') {
            return null;
        }

        [$min, $max] = self::FIELD_RANGES[$fieldIndex];
        $values = [];

        foreach (explode(',', $field) as $part) {
            $part = trim($part);

            if (str_contains($part, '/')) {
                [$range, $stepRaw] = explode('/', $part, 2);
                $step = (int) $stepRaw;

                if ($step < 1) {
                    throw InvalidCronExpressionException::fromExpression(
                        $expression,
                        sprintf('Invalid step value "%s"', $stepRaw)
                    );
                }

                if ($range === '*') {
                    $rangeStart = $min;
                    $rangeEnd = $max;
                } elseif (str_contains($range, '-')) {
                    [$rangeStart, $rangeEnd] = array_map('intval', explode('-', $range, 2));
                } else {
                    $rangeStart = (int) $range;
                    $rangeEnd = $max;
                }

                for ($v = $rangeStart; $v <= $rangeEnd; $v += $step) {
                    $values[] = $v;
                }
            } elseif (str_contains($part, '-')) {
                [$rangeStart, $rangeEnd] = array_map('intval', explode('-', $part, 2));

                if ($rangeStart > $rangeEnd) {
                    throw InvalidCronExpressionException::fromExpression(
                        $expression,
                        sprintf('Invalid range: %d-%d', $rangeStart, $rangeEnd)
                    );
                }

                for ($v = $rangeStart; $v <= $rangeEnd; $v++) {
                    $values[] = $v;
                }
            } else {
                $values[] = (int) $part;
            }
        }

        $this->validateValues($values, $fieldIndex, $expression);

        sort($values);
        return array_values(array_unique($values));
    }

    /**
     * @param list<int> $values
     */
    private function validateValues(array $values, int $fieldIndex, string $expression): void
    {
        [$min, $max] = self::FIELD_RANGES[$fieldIndex];

        foreach ($values as $value) {
            if ($fieldIndex === self::FIELD_WEEKDAY && $value === 7) {
                continue;
            }

            if ($value < $min || $value > $max) {
                throw InvalidCronExpressionException::fromExpression(
                    $expression,
                    sprintf('Value %d out of range [%d-%d]', $value, $min, $max)
                );
            }
        }
    }
}
