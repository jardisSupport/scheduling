<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Handler;

use DateTimeInterface;
use JardisSupport\Contract\Scheduling\ConstraintInterface;

/**
 * Restricts task execution to specific days of the week.
 */
final readonly class DayOfWeek implements ConstraintInterface
{
    /** @var list<int> */
    private array $allowedDays;

    public function __construct(int ...$days)
    {
        $sorted = $days;
        sort($sorted);
        $this->allowedDays = array_values(array_unique($sorted));
    }

    public static function weekdays(): self
    {
        return new self(1, 2, 3, 4, 5);
    }

    public static function weekends(): self
    {
        return new self(0, 6);
    }

    /**
     * @return list<int>
     */
    public function getAllowedDays(): array
    {
        return $this->allowedDays;
    }

    public function __invoke(DateTimeInterface $now): bool
    {
        return in_array((int) $now->format('w'), $this->allowedDays, true);
    }
}
