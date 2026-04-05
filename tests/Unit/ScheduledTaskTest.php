<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit;

use DateTimeImmutable;
use JardisSupport\Scheduling\CronExpression;
use JardisSupport\Scheduling\Data\ScheduledTask;
use JardisSupport\Scheduling\Handler\CallableCondition;
use JardisSupport\Scheduling\Handler\DayOfWeek;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ScheduledTaskTest extends TestCase
{
    #[Test]
    public function testIsDueWithMatchingExpressionReturnsTrue(): void
    {
        $task = new ScheduledTask(
            name: 'test-task',
            expression: CronExpression::parse('30 10 * * *'),
            description: 'Test task',
        );

        self::assertSame('test-task', $task->name());
        self::assertSame('Test task', $task->description());
        self::assertTrue($task->isDue(new DateTimeImmutable('2026-04-05 10:30:00')));
        self::assertFalse($task->isDue(new DateTimeImmutable('2026-04-05 10:31:00')));
    }

    #[Test]
    public function testIsDueWithWeekdayConstraintBlocksWeekend(): void
    {
        $task = new ScheduledTask(
            name: 'weekday-task',
            expression: CronExpression::parse('0 10 * * *'),
            constraints: [
                DayOfWeek::weekdays(),
            ],
        );

        self::assertTrue($task->isDue(new DateTimeImmutable('2026-04-06 10:00:00')));
        self::assertFalse($task->isDue(new DateTimeImmutable('2026-04-05 10:00:00')));
    }

    #[Test]
    public function testIsDueWithFalseCallableReturnsFalse(): void
    {
        $task = new ScheduledTask(
            name: 'conditional-task',
            expression: CronExpression::parse('* * * * *'),
            constraints: [
                new CallableCondition(fn() => false),
            ],
        );

        self::assertFalse($task->isDue(new DateTimeImmutable('2026-04-05 10:00:00')));
    }

    #[Test]
    public function testNextRunFromMidHourReturnsNextFullHour(): void
    {
        $task = new ScheduledTask(
            name: 'hourly-task',
            expression: CronExpression::parse('0 * * * *'),
        );

        $next = $task->nextRun(new DateTimeImmutable('2026-04-05 10:30:00'));
        self::assertSame('2026-04-05 11:00:00', $next->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testTagsWithoutConfigurationReturnsEmptyList(): void
    {
        $task = new ScheduledTask(
            name: 'simple-task',
            expression: CronExpression::parse('* * * * *'),
        );

        self::assertSame([], $task->tags());
    }

    #[Test]
    public function testTagsWithConfiguredValuesReturnsList(): void
    {
        $task = new ScheduledTask(
            name: 'tagged-task',
            expression: CronExpression::parse('* * * * *'),
            tags: ['email', 'notifications'],
        );

        self::assertSame(['email', 'notifications'], $task->tags());
    }

    #[Test]
    public function testPriorityWithoutConfigurationReturnsZero(): void
    {
        $task = new ScheduledTask(
            name: 'default-priority',
            expression: CronExpression::parse('* * * * *'),
        );

        self::assertSame(0, $task->priority());
    }

    #[Test]
    public function testPriorityWithConfiguredValueReturnsCorrectly(): void
    {
        $task = new ScheduledTask(
            name: 'high-priority',
            expression: CronExpression::parse('* * * * *'),
            priority: 10,
        );

        self::assertSame(10, $task->priority());
    }

    #[Test]
    public function testAllowsOverlappingWithoutConfigurationReturnsTrue(): void
    {
        $task = new ScheduledTask(
            name: 'overlapping-task',
            expression: CronExpression::parse('* * * * *'),
        );

        self::assertTrue($task->allowsOverlapping());
    }

    #[Test]
    public function testAllowsOverlappingWhenDisabledReturnsFalse(): void
    {
        $task = new ScheduledTask(
            name: 'no-overlap',
            expression: CronExpression::parse('* * * * *'),
            overlapping: false,
        );

        self::assertFalse($task->allowsOverlapping());
    }

    #[Test]
    public function testConstraintsWithConfiguredListReturnsSameInstances(): void
    {
        $constraint = DayOfWeek::weekdays();
        $task = new ScheduledTask(
            name: 'constrained',
            expression: CronExpression::parse('* * * * *'),
            constraints: [$constraint],
        );

        self::assertCount(1, $task->constraints());
        self::assertSame($constraint, $task->constraints()[0]);
    }
}
