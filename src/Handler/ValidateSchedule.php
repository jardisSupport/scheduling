<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Handler;

use JardisSupport\Contract\Scheduling\ScheduleViolation;
use JardisSupport\Scheduling\Data\ScheduledTask;

/**
 * Validates a list of scheduled tasks and returns any violations found.
 */
final class ValidateSchedule
{
    /**
     * @param list<ScheduledTask> $tasks
     * @return list<ScheduleViolation>
     */
    public function __invoke(array $tasks): array
    {
        $violations = [];

        if ($tasks === []) {
            $violations[] = new ScheduleViolation(
                taskName: '',
                message: 'Schedule contains no tasks',
                severity: 'warning',
            );

            return $violations;
        }

        $violations = [...$violations, ...$this->checkDuplicateNames($tasks)];
        $violations = [...$violations, ...$this->checkConflictingDayConstraints($tasks)];

        return $violations;
    }

    /**
     * @param list<ScheduledTask> $tasks
     * @return list<ScheduleViolation>
     */
    private function checkDuplicateNames(array $tasks): array
    {
        $violations = [];
        $seen = [];

        foreach ($tasks as $task) {
            $name = $task->name();
            if (in_array($name, $seen, true)) {
                $violations[] = new ScheduleViolation(
                    taskName: $name,
                    message: sprintf('Duplicate task name: %s', $name),
                );
                continue;
            }
            $seen[] = $name;
        }

        return $violations;
    }

    /**
     * @param list<ScheduledTask> $tasks
     * @return list<ScheduleViolation>
     */
    private function checkConflictingDayConstraints(array $tasks): array
    {
        $violations = [];

        foreach ($tasks as $task) {
            $weekdayDays = [];
            $weekendDays = [];

            foreach ($task->constraints() as $constraint) {
                if (!$constraint instanceof DayOfWeek) {
                    continue;
                }

                $allowed = $constraint->getAllowedDays();

                if ($allowed === [1, 2, 3, 4, 5]) {
                    $weekdayDays = $allowed;
                } elseif ($allowed === [0, 6]) {
                    $weekendDays = $allowed;
                }
            }

            if ($weekdayDays !== [] && $weekendDays !== []) {
                $violations[] = new ScheduleViolation(
                    taskName: $task->name(),
                    message: sprintf('Task %s has conflicting day constraints', $task->name()),
                    severity: 'warning',
                );
            }
        }

        return $violations;
    }
}
