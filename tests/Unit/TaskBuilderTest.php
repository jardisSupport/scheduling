<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit;

use DateTimeImmutable;
use JardisSupport\Scheduling\Exception\InvalidScheduleException;
use JardisSupport\Scheduling\Schedule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TaskBuilderTest extends TestCase
{
    #[Test]
    public function testBuildWithCronExpressionCreatesTask(): void
    {
        $schedule = Schedule::create();
        $builder = $schedule->task('test-task')
            ->cron('30 10 * * *')
            ->description('A test');

        $tasks = $schedule->allTasks();

        self::assertCount(1, $tasks);
        self::assertSame('test-task', $tasks[0]->name());
        self::assertSame('A test', $tasks[0]->description());
    }

    #[Test]
    public function testBuildWithoutExpressionThrowsException(): void
    {
        $this->expectException(InvalidScheduleException::class);

        Schedule::create()
            ->task('no-cron')
            ->allTasks();
    }

    #[Test]
    public function testTagMethodMergesAndDeduplicates(): void
    {
        $schedule = Schedule::create()
            ->task('tagged')
                ->everyMinute()
                ->tag('a', 'b')
                ->tag('b', 'c');

        $tasks = $schedule->allTasks();

        self::assertSame(['a', 'b', 'c'], $tasks[0]->tags());
    }

    #[Test]
    public function testPriorityMethodSetsValue(): void
    {
        $schedule = Schedule::create()
            ->task('prio-task')
                ->everyMinute()
                ->priority(5);

        self::assertSame(5, $schedule->allTasks()[0]->priority());
    }

    #[Test]
    public function testWithoutOverlappingMethodSetsFalse(): void
    {
        $schedule = Schedule::create()
            ->task('exclusive')
                ->everyMinute()
                ->withoutOverlapping();

        self::assertFalse($schedule->allTasks()[0]->allowsOverlapping());
    }

    #[Test]
    public function testTimezoneMethodAppliesTimezone(): void
    {
        $schedule = Schedule::create()
            ->task('tz-task')
                ->cron('0 12 * * *')
                ->timezone('Europe/Berlin');

        $utc = new DateTimeImmutable('2026-04-05 10:00:00', new \DateTimeZone('UTC'));

        self::assertTrue($schedule->allTasks()[0]->isDue($utc));
    }

    #[Test]
    public function testEveryMinuteHelperSetsCronExpression(): void
    {
        $schedule = Schedule::create()
            ->task('frequent')
                ->everyMinute();

        self::assertTrue(
            $schedule->allTasks()[0]->isDue(new DateTimeImmutable('2026-04-05 10:30:00'))
        );
    }

    #[Test]
    public function testDailyAtHelperParsesTime(): void
    {
        $schedule = Schedule::create()
            ->task('daily')
                ->dailyAt('09:30');

        $tasks = $schedule->allTasks();

        self::assertTrue($tasks[0]->isDue(new DateTimeImmutable('2026-04-05 09:30:00')));
        self::assertFalse($tasks[0]->isDue(new DateTimeImmutable('2026-04-05 10:00:00')));
    }

    #[Test]
    public function testDailyAtWithInvalidTimeThrowsException(): void
    {
        $this->expectException(InvalidScheduleException::class);

        Schedule::create()
            ->task('bad-time')
            ->dailyAt('25:00');
    }

    #[Test]
    public function testDailyAtWithBadFormatThrowsException(): void
    {
        $this->expectException(InvalidScheduleException::class);

        Schedule::create()
            ->task('bad-format')
            ->dailyAt('nine-thirty');
    }

    #[Test]
    public function testWeeklyOnHelperSetsCorrectCron(): void
    {
        $schedule = Schedule::create()
            ->task('weekly')
                ->weeklyOn(3, '09:30');

        // 2026-04-08 is Wednesday (day 3)
        self::assertTrue(
            $schedule->allTasks()[0]->isDue(new DateTimeImmutable('2026-04-08 09:30:00'))
        );
    }

    #[Test]
    public function testMonthlyOnHelperSetsCorrectCron(): void
    {
        $schedule = Schedule::create()
            ->task('monthly')
                ->monthlyOn(15, '06:00');

        self::assertTrue(
            $schedule->allTasks()[0]->isDue(new DateTimeImmutable('2026-04-15 06:00:00'))
        );
    }

    #[Test]
    public function testConstraintBetweenLimitsWindow(): void
    {
        $schedule = Schedule::create()
            ->task('office')
                ->everyMinute()
                ->between('08:00', '18:00');

        $tasks = $schedule->allTasks();

        self::assertTrue($tasks[0]->isDue(new DateTimeImmutable('2026-04-05 10:00:00')));
        self::assertFalse($tasks[0]->isDue(new DateTimeImmutable('2026-04-05 20:00:00')));
    }

    #[Test]
    public function testConstraintEnvironmentsFilters(): void
    {
        $schedule = Schedule::create('production')
            ->task('prod-only')
                ->everyMinute()
                ->environments('production');

        self::assertCount(1, $schedule->dueNow(new DateTimeImmutable('2026-04-05 10:00:00')));

        $schedule2 = Schedule::create('development')
            ->task('prod-only')
                ->everyMinute()
                ->environments('production');

        self::assertCount(0, $schedule2->dueNow(new DateTimeImmutable('2026-04-05 10:00:00')));
    }

    #[Test]
    public function testChainTaskDelegatesToSchedule(): void
    {
        $schedule = Schedule::create()
            ->task('first')
                ->everyMinute()
            ->task('second')
                ->hourly();

        self::assertCount(2, $schedule->allTasks());
    }

    #[Test]
    public function testValidateProxyDelegatesToSchedule(): void
    {
        $schedule = Schedule::create()
            ->task('only')
                ->everyMinute();

        self::assertCount(0, $schedule->validate());
    }
}
