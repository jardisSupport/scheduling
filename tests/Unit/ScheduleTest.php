<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit;

use DateTimeImmutable;
use JardisSupport\Scheduling\Exception\InvalidScheduleException;
use JardisSupport\Scheduling\Schedule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ScheduleTest extends TestCase
{
    #[Test]
    public function testTaskWithFluentApiCreatesCorrectTask(): void
    {
        $schedule = Schedule::create()
            ->task('cleanup')
                ->dailyAt('03:00')
                ->description('Daily cleanup')
                ->cron('0 3 * * *');

        $tasks = $schedule->allTasks();

        self::assertCount(1, $tasks);
        self::assertSame('cleanup', $tasks[0]->name());
        self::assertSame('Daily cleanup', $tasks[0]->description());
    }

    #[Test]
    public function testAllTasksWithMultipleBuildersReturnsAll(): void
    {
        $schedule = Schedule::create()
            ->task('task-a')
                ->everyMinute()
            ->task('task-b')
                ->hourly()
            ->task('task-c')
                ->daily();

        self::assertCount(3, $schedule->allTasks());
    }

    #[Test]
    public function testDueNowWithMixedTasksReturnsOnlyMatching(): void
    {
        $now = new DateTimeImmutable('2026-04-05 10:00:00');

        $schedule = Schedule::create()
            ->task('every-minute')
                ->everyMinute()
            ->task('at-noon')
                ->dailyAt('12:00');

        $due = $schedule->dueNow($now);

        self::assertCount(1, $due);
        self::assertSame('every-minute', $due[0]->name());
    }

    #[Test]
    public function testDueNowWithWeekdayConstraintExcludesWeekend(): void
    {
        $now = new DateTimeImmutable('2026-04-05 10:00:00');

        $schedule = Schedule::create()
            ->task('weekday-only')
                ->everyMinute()
                ->weekdays()
            ->task('always')
                ->everyMinute();

        $due = $schedule->dueNow($now);

        self::assertCount(1, $due);
        self::assertSame('always', $due[0]->name());
    }

    #[Test]
    public function testDueNowWithBetweenConstraintFiltersOutside(): void
    {
        $schedule = Schedule::create()
            ->task('office-hours')
                ->everyFiveMinutes()
                ->between('08:00', '18:00');

        self::assertCount(1, $schedule->dueNow(new DateTimeImmutable('2026-04-05 10:00:00')));
        self::assertCount(0, $schedule->dueNow(new DateTimeImmutable('2026-04-05 20:00:00')));
    }

    #[Test]
    public function testDueNowWithWhenConditionRespectsCallback(): void
    {
        $enabled = true;

        $schedule = Schedule::create()
            ->task('conditional')
                ->everyMinute()
                ->when(fn() => $enabled);

        self::assertCount(1, $schedule->dueNow(new DateTimeImmutable('2026-04-05 10:00:00')));

        $enabled = false;
        $schedule2 = Schedule::create()
            ->task('conditional')
                ->everyMinute()
                ->when(fn() => $enabled);

        self::assertCount(0, $schedule2->dueNow(new DateTimeImmutable('2026-04-05 10:00:00')));
    }

    #[Test]
    public function testDueNowWithSkipTrueExcludesTask(): void
    {
        $schedule = Schedule::create()
            ->task('skippable')
                ->everyMinute()
                ->skip(fn() => true);

        self::assertCount(0, $schedule->dueNow(new DateTimeImmutable('2026-04-05 10:00:00')));
    }

    #[Test]
    public function testDueNowWithTimezoneConvertsUtcCorrectly(): void
    {
        $schedule = Schedule::create()
            ->task('berlin-task')
                ->cron('0 12 * * *')
                ->timezone('Europe/Berlin');

        $utc = new DateTimeImmutable('2026-04-05 10:00:00', new \DateTimeZone('UTC'));
        $due = $schedule->dueNow($utc);

        self::assertCount(1, $due);
    }

    #[Test]
    public function testTaskWithEmptyNameThrowsException(): void
    {
        $this->expectException(InvalidScheduleException::class);

        Schedule::create()->task('');
    }

    #[Test]
    public function testAllTasksWithoutExpressionThrowsException(): void
    {
        $this->expectException(InvalidScheduleException::class);

        $schedule = Schedule::create()
            ->task('no-cron')
                ->description('Missing expression');

        $schedule->allTasks();
    }

    #[Test]
    public function testAllTasksWithTimeHelpersCreatesParseableExpressions(): void
    {
        $now = new DateTimeImmutable('2026-04-05 00:00:00');

        $schedule = Schedule::create()
            ->task('every-five')
                ->everyFiveMinutes()
            ->task('every-fifteen')
                ->everyFifteenMinutes()
            ->task('every-thirty')
                ->everyThirtyMinutes()
            ->task('hourly-at-30')
                ->hourlyAt(30)
            ->task('weekly-task')
                ->weekly()
            ->task('monthly-task')
                ->monthly()
            ->task('yearly-task')
                ->yearly();

        $tasks = $schedule->allTasks();
        self::assertCount(7, $tasks);

        $due = $schedule->dueNow($now);
        $names = array_map(fn($t) => $t->name(), $due);

        self::assertContains('every-five', $names);
        self::assertContains('weekly-task', $names);
    }

    #[Test]
    public function testDueNowWithWeeklyOnWednesdayMatchesCorrectDate(): void
    {
        $wed = new DateTimeImmutable('2026-04-08 09:30:00');

        $schedule = Schedule::create()
            ->task('wed-meeting')
                ->weeklyOn(3, '09:30');

        self::assertCount(1, $schedule->dueNow($wed));
    }

    #[Test]
    public function testDueNowWithMonthlyOnFirstMatchesCorrectDate(): void
    {
        $firstOfMonth = new DateTimeImmutable('2026-04-01 06:00:00');

        $schedule = Schedule::create()
            ->task('monthly-report')
                ->monthlyOn(1, '06:00');

        self::assertCount(1, $schedule->dueNow($firstOfMonth));
    }

    // --- Tags ---

    #[Test]
    public function testDueNowWithTagFilterReturnsMatchingTasks(): void
    {
        $now = new DateTimeImmutable('2026-04-05 10:00:00');

        $schedule = Schedule::create()
            ->task('email-task')
                ->everyMinute()
                ->tag('email')
            ->task('report-task')
                ->everyMinute()
                ->tag('reports')
            ->task('both-tags')
                ->everyMinute()
                ->tag('email', 'reports');

        $emailDue = $schedule->dueNow($now, ['email']);
        self::assertCount(2, $emailDue);

        $reportDue = $schedule->dueNow($now, ['reports']);
        self::assertCount(2, $reportDue);

        $allDue = $schedule->dueNow($now);
        self::assertCount(3, $allDue);
    }

    #[Test]
    public function testAllTasksWithTagFilterReturnsMatchingTasks(): void
    {
        $schedule = Schedule::create()
            ->task('email-task')
                ->everyMinute()
                ->tag('email')
            ->task('report-task')
                ->everyMinute()
                ->tag('reports');

        self::assertCount(1, $schedule->allTasks(['email']));
        self::assertCount(1, $schedule->allTasks(['reports']));
        self::assertCount(2, $schedule->allTasks());
    }

    // --- Priority ---

    #[Test]
    public function testDueNowWithPrioritiesSortsDescending(): void
    {
        $now = new DateTimeImmutable('2026-04-05 10:00:00');

        $schedule = Schedule::create()
            ->task('low')
                ->everyMinute()
                ->priority(1)
            ->task('high')
                ->everyMinute()
                ->priority(10)
            ->task('medium')
                ->everyMinute()
                ->priority(5);

        $due = $schedule->dueNow($now);

        self::assertSame('high', $due[0]->name());
        self::assertSame('medium', $due[1]->name());
        self::assertSame('low', $due[2]->name());
    }

    #[Test]
    public function testAllTasksWithPrioritiesSortsDescending(): void
    {
        $schedule = Schedule::create()
            ->task('low')
                ->everyMinute()
                ->priority(1)
            ->task('high')
                ->everyMinute()
                ->priority(10);

        $tasks = $schedule->allTasks();

        self::assertSame('high', $tasks[0]->name());
        self::assertSame('low', $tasks[1]->name());
    }

    // --- Overlap Guard ---

    #[Test]
    public function testWithoutOverlappingSetsFalseOnTask(): void
    {
        $schedule = Schedule::create()
            ->task('exclusive')
                ->everyMinute()
                ->withoutOverlapping();

        $tasks = $schedule->allTasks();

        self::assertFalse($tasks[0]->allowsOverlapping());
    }

    #[Test]
    public function testAllowsOverlappingWithoutConfigReturnsTrue(): void
    {
        $schedule = Schedule::create()
            ->task('normal')
                ->everyMinute();

        $tasks = $schedule->allTasks();

        self::assertTrue($tasks[0]->allowsOverlapping());
    }

    // --- Validation ---

    #[Test]
    public function testValidateWithNoTasksReturnsWarning(): void
    {
        $schedule = Schedule::create();
        $violations = $schedule->validate();

        self::assertCount(1, $violations);
        self::assertSame('warning', $violations[0]->severity);
    }

    #[Test]
    public function testValidateWithDuplicateNamesReturnsError(): void
    {
        $schedule = Schedule::create()
            ->task('dupe')
                ->everyMinute()
            ->task('dupe')
                ->hourly();

        $violations = $schedule->validate();

        self::assertCount(1, $violations);
        self::assertSame('error', $violations[0]->severity);
        self::assertStringContainsString('Duplicate task name', $violations[0]->message);
    }

    #[Test]
    public function testValidateWithUniqueNamesReturnsEmpty(): void
    {
        $schedule = Schedule::create()
            ->task('task-a')
                ->everyMinute()
            ->task('task-b')
                ->hourly();

        self::assertCount(0, $schedule->validate());
    }
}
