<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling;

use DateTimeInterface;
use JardisSupport\Contract\Scheduling\ScheduleInterface;
use JardisSupport\Contract\Scheduling\ScheduledTaskInterface;
use JardisSupport\Contract\Scheduling\ScheduleViolation;
use JardisSupport\Scheduling\Data\ScheduledTask;
use JardisSupport\Scheduling\Exception\InvalidScheduleException;
use JardisSupport\Scheduling\Handler\ValidateSchedule;

/**
 * Entry point for defining scheduled tasks via fluent API.
 */
final class Schedule implements ScheduleInterface
{
    /** @var list<TaskBuilder> */
    private array $builders = [];

    /** @var list<ScheduledTask>|null */
    private ?array $resolved = null;

    private function __construct(
        private readonly string $currentEnvironment = '',
    ) {
    }

    public static function create(string $currentEnvironment = ''): self
    {
        return new self($currentEnvironment);
    }

    public function task(string $name): TaskBuilder
    {
        if ($name === '') {
            throw InvalidScheduleException::missingName();
        }

        $this->resolved = null;
        $builder = new TaskBuilder($name, $this, $this->currentEnvironment);
        $this->builders[] = $builder;

        return $builder;
    }

    /**
     * @param list<string> $tags
     * @return list<ScheduledTaskInterface>
     */
    public function dueNow(DateTimeInterface $now, array $tags = []): array
    {
        $tasks = $this->resolve();

        $due = [];
        foreach ($tasks as $task) {
            if ($task->isDue($now) && $this->matchesTags($task, $tags)) {
                $due[] = $task;
            }
        }

        return $this->sortByPriority($due);
    }

    /**
     * @param list<string> $tags
     * @return list<ScheduledTaskInterface>
     */
    public function allTasks(array $tags = []): array
    {
        $tasks = $this->resolve();

        if ($tags !== []) {
            $tasks = array_values(array_filter(
                $tasks,
                fn (ScheduledTask $task): bool => $this->matchesTags($task, $tags),
            ));
        }

        return $this->sortByPriority($tasks);
    }

    /**
     * @return list<ScheduleViolation>
     */
    public function validate(): array
    {
        return (new ValidateSchedule())($this->resolve());
    }

    /**
     * @return list<ScheduledTask>
     */
    private function resolve(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $this->resolved = [];

        foreach ($this->builders as $builder) {
            $this->resolved[] = $builder->build();
        }

        return $this->resolved;
    }

    /**
     * @param list<string> $tags
     */
    private function matchesTags(ScheduledTask $task, array $tags): bool
    {
        if ($tags === []) {
            return true;
        }

        foreach ($tags as $tag) {
            if (in_array($tag, $task->tags(), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<ScheduledTask> $tasks
     * @return list<ScheduledTaskInterface>
     */
    private function sortByPriority(array $tasks): array
    {
        usort($tasks, static fn (ScheduledTask $a, ScheduledTask $b): int => $b->priority() <=> $a->priority());

        return $tasks;
    }
}
