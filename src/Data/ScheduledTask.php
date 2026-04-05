<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Data;

use DateTimeInterface;
use JardisSupport\Contract\Scheduling\ConstraintInterface;
use JardisSupport\Contract\Scheduling\CronExpressionInterface;
use JardisSupport\Contract\Scheduling\ScheduledTaskInterface;

/**
 * Immutable value object representing a single scheduled task with its cron expression and constraints.
 */
final readonly class ScheduledTask implements ScheduledTaskInterface
{
    /**
     * @param list<ConstraintInterface> $constraints
     * @param list<string> $tags
     */
    public function __construct(
        private string $name,
        private CronExpressionInterface $expression,
        private string $description = '',
        private array $constraints = [],
        private array $tags = [],
        private int $priority = 0,
        private bool $overlapping = true,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function expression(): CronExpressionInterface
    {
        return $this->expression;
    }

    public function isDue(DateTimeInterface $now): bool
    {
        if (!$this->expression->isDue($now)) {
            return false;
        }

        foreach ($this->constraints as $constraint) {
            if (!($constraint)($now)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return $this->tags;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function allowsOverlapping(): bool
    {
        return $this->overlapping;
    }

    /**
     * @return list<ConstraintInterface>
     */
    public function constraints(): array
    {
        return $this->constraints;
    }

    public function nextRun(DateTimeInterface $from): DateTimeInterface
    {
        return $this->expression->nextRun($from);
    }
}
