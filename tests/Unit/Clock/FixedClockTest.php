<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Clock;

use DateInterval;
use DateTimeImmutable;
use JardisSupport\Scheduling\Clock\FixedClock;
use JardisSupport\Scheduling\Schedule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class FixedClockTest extends TestCase
{
    #[Test]
    public function testImplementsPsrClockInterface(): void
    {
        self::assertInstanceOf(ClockInterface::class, new FixedClock(new DateTimeImmutable('2026-01-01 00:00:00')));
    }

    #[Test]
    public function testNowIsDeterministic(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-04-05 10:00:00'));

        self::assertEquals($clock->now(), $clock->now());
        self::assertSame('2026-04-05 10:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testAdvanceReturnsNewInstanceAndLeavesOriginalUnchanged(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-04-05 10:00:00'));

        $advanced = $clock->advance(new DateInterval('PT30M'));

        self::assertNotSame($clock, $advanced);
        self::assertInstanceOf(ClockInterface::class, $advanced);
        self::assertSame('2026-04-05 10:00:00', $clock->now()->format('Y-m-d H:i:s'));
        self::assertSame('2026-04-05 10:30:00', $advanced->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testAdvanceCanBeChained(): void
    {
        $clock = (new FixedClock(new DateTimeImmutable('2026-04-05 23:50:00')))
            ->advance(new DateInterval('PT5M'))
            ->advance(new DateInterval('PT10M'));

        self::assertSame('2026-04-06 00:05:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testDrivesScheduleDueNow(): void
    {
        $schedule = Schedule::create()->task('cleanup')->dailyAt('03:00');
        $clock = new FixedClock(new DateTimeImmutable('2026-04-05 02:59:00'));

        self::assertCount(0, $schedule->dueNow($clock->now()));
        self::assertCount(1, $schedule->dueNow($clock->advance(new DateInterval('PT1M'))->now()));
    }
}
