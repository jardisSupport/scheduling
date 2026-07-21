<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Handler;

use JardisSupport\Scheduling\CronExpression;
use JardisSupport\Scheduling\Data\ScheduledTask;
use JardisSupport\Scheduling\Handler\DayOfWeek;
use JardisSupport\Scheduling\Handler\ValidateSchedule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ValidateScheduleTest extends TestCase
{
    #[Test]
    public function testInvokeWithEmptyListReturnsWarning(): void
    {
        $validate = new ValidateSchedule();
        $violations = $validate([]);

        self::assertCount(1, $violations);
        self::assertSame('warning', $violations[0]->severity);
        self::assertSame('Schedule contains no tasks', $violations[0]->message);
    }

    #[Test]
    public function testInvokeWithDuplicateNamesReturnsError(): void
    {
        $validate = new ValidateSchedule();
        $violations = $validate([
            new ScheduledTask(name: 'task-a', expression: CronExpression::parse('* * * * *')),
            new ScheduledTask(name: 'task-a', expression: CronExpression::parse('0 * * * *')),
        ]);

        self::assertCount(1, $violations);
        self::assertSame('error', $violations[0]->severity);
        self::assertSame('Duplicate task name: task-a', $violations[0]->message);
    }

    #[Test]
    public function testInvokeWithConflictingDayConstraintsReturnsWarning(): void
    {
        $validate = new ValidateSchedule();
        $violations = $validate([
            new ScheduledTask(
                name: 'conflicting',
                expression: CronExpression::parse('* * * * *'),
                constraints: [
                    DayOfWeek::weekdays(),
                    DayOfWeek::weekends(),
                ],
            ),
        ]);

        self::assertCount(1, $violations);
        self::assertSame('warning', $violations[0]->severity);
        self::assertStringContainsString('conflicting day constraints', $violations[0]->message);
    }

    #[Test]
    public function testInvokeWithValidTasksReturnsEmptyList(): void
    {
        $validate = new ValidateSchedule();
        $violations = $validate([
            new ScheduledTask(name: 'task-a', expression: CronExpression::parse('* * * * *')),
            new ScheduledTask(name: 'task-b', expression: CronExpression::parse('0 * * * *')),
        ]);

        self::assertCount(0, $violations);
    }
}
