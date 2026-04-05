<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling;

use Closure;
use DateTimeInterface;
use DateTimeZone;
use JardisSupport\Contract\Scheduling\ConstraintInterface;
use JardisSupport\Contract\Scheduling\ScheduledTaskInterface;
use JardisSupport\Contract\Scheduling\ScheduleViolation;
use JardisSupport\Scheduling\Data\ScheduledTask;
use JardisSupport\Scheduling\Exception\InvalidScheduleException;
use JardisSupport\Scheduling\Handler\CallableCondition;
use JardisSupport\Scheduling\Handler\DayOfWeek;
use JardisSupport\Scheduling\Handler\EnvironmentMatch;
use JardisSupport\Scheduling\Handler\TimeWindow;

/**
 * Fluent builder for configuring a scheduled task.
 */
final class TaskBuilder
{
    private ?string $cronExpression = null;
    private ?DateTimeZone $timezone = null;
    private string $description = '';
    /** @var list<ConstraintInterface> */
    private array $constraints = [];
    /** @var list<string> */
    private array $tags = [];
    private int $priority = 0;
    private bool $overlapping = true;

    public function __construct(
        private readonly string $name,
        private readonly Schedule $schedule,
        private readonly string $currentEnvironment = '',
    ) {
    }

    public function cron(string $expression): self
    {
        $this->cronExpression = $expression;
        return $this;
    }

    public function timezone(string $timezone): self
    {
        $this->timezone = new DateTimeZone($timezone);
        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    // --- Fluent Time Helpers ---

    public function everyMinute(): self
    {
        return $this->cron('* * * * *');
    }

    public function everyFiveMinutes(): self
    {
        return $this->cron('*/5 * * * *');
    }

    public function everyFifteenMinutes(): self
    {
        return $this->cron('*/15 * * * *');
    }

    public function everyThirtyMinutes(): self
    {
        return $this->cron('*/30 * * * *');
    }

    public function hourly(): self
    {
        return $this->cron('0 * * * *');
    }

    public function hourlyAt(int $minute): self
    {
        return $this->cron(sprintf('%d * * * *', $minute));
    }

    public function daily(): self
    {
        return $this->cron('0 0 * * *');
    }

    public function dailyAt(string $time): self
    {
        [$hour, $minute] = $this->parseTime($time);
        return $this->cron(sprintf('%d %d * * *', $minute, $hour));
    }

    public function weekly(): self
    {
        return $this->cron('0 0 * * 0');
    }

    public function weeklyOn(int $day, string $time = '00:00'): self
    {
        [$hour, $minute] = $this->parseTime($time);
        return $this->cron(sprintf('%d %d * * %d', $minute, $hour, $day));
    }

    public function monthly(): self
    {
        return $this->cron('0 0 1 * *');
    }

    public function monthlyOn(int $day, string $time = '00:00'): self
    {
        [$hour, $minute] = $this->parseTime($time);
        return $this->cron(sprintf('%d %d %d * *', $minute, $hour, $day));
    }

    public function yearly(): self
    {
        return $this->cron('0 0 1 1 *');
    }

    // --- Constraints ---

    public function between(string $start, string $end): self
    {
        $this->constraints[] = new TimeWindow($start, $end);
        return $this;
    }

    public function unlessBetween(string $start, string $end): self
    {
        $this->constraints[] = new TimeWindow($start, $end, inverted: true);
        return $this;
    }

    public function weekdays(): self
    {
        $this->constraints[] = DayOfWeek::weekdays();
        return $this;
    }

    public function weekends(): self
    {
        $this->constraints[] = DayOfWeek::weekends();
        return $this;
    }

    public function days(int ...$days): self
    {
        $this->constraints[] = new DayOfWeek(...$days);
        return $this;
    }

    public function when(Closure $condition): self
    {
        $this->constraints[] = new CallableCondition($condition);
        return $this;
    }

    public function skip(Closure $condition): self
    {
        $this->constraints[] = new CallableCondition($condition, inverted: true);
        return $this;
    }

    public function environments(string ...$envs): self
    {
        $this->constraints[] = new EnvironmentMatch($this->currentEnvironment, ...$envs);
        return $this;
    }

    // --- Tags, Priority, Overlap ---

    public function tag(string ...$tags): self
    {
        $this->tags = array_values(array_unique(array_merge($this->tags, $tags)));
        return $this;
    }

    public function priority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function withoutOverlapping(): self
    {
        $this->overlapping = false;
        return $this;
    }

    // --- Chain to Schedule ---

    public function task(string $name): TaskBuilder
    {
        return $this->schedule->task($name);
    }

    /**
     * @param list<string> $tags
     * @return list<ScheduledTaskInterface>
     */
    public function dueNow(DateTimeInterface $now, array $tags = []): array
    {
        return $this->schedule->dueNow($now, $tags);
    }

    /**
     * @param list<string> $tags
     * @return list<ScheduledTaskInterface>
     */
    public function allTasks(array $tags = []): array
    {
        return $this->schedule->allTasks($tags);
    }

    /**
     * @return list<ScheduleViolation>
     */
    public function validate(): array
    {
        return $this->schedule->validate();
    }

    public function build(): ScheduledTask
    {
        if ($this->cronExpression === null) {
            throw InvalidScheduleException::missingExpression($this->name);
        }

        return new ScheduledTask(
            name: $this->name,
            expression: CronExpression::parse($this->cronExpression, $this->timezone),
            description: $this->description,
            constraints: $this->constraints,
            tags: $this->tags,
            priority: $this->priority,
            overlapping: $this->overlapping,
        );
    }

    /**
     * @return array{int, int}
     */
    private function parseTime(string $time): array
    {
        if (preg_match('/^\d{1,2}:\d{2}$/', $time) !== 1) {
            throw InvalidScheduleException::invalidTime($time);
        }

        $parts = explode(':', $time);
        $hour = (int) $parts[0];
        $minute = (int) $parts[1];

        if ($hour > 23 || $minute > 59) {
            throw InvalidScheduleException::invalidTime($time);
        }

        return [$hour, $minute];
    }
}
