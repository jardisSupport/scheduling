<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Clock;

use DateTimeImmutable;
use DateTimeZone;
use JardisSupport\Scheduling\Clock\SystemClock;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class SystemClockTest extends TestCase
{
    #[Test]
    public function testImplementsPsrClockInterface(): void
    {
        self::assertInstanceOf(ClockInterface::class, new SystemClock());
    }

    #[Test]
    public function testNowReturnsDateTimeImmutable(): void
    {
        self::assertInstanceOf(DateTimeImmutable::class, (new SystemClock())->now());
    }

    #[Test]
    public function testDefaultTimezoneIsUtc(): void
    {
        self::assertSame('UTC', (new SystemClock())->now()->getTimezone()->getName());
    }

    #[Test]
    public function testGivenTimezoneIsApplied(): void
    {
        $clock = new SystemClock(new DateTimeZone('Europe/Berlin'));

        self::assertSame('Europe/Berlin', $clock->now()->getTimezone()->getName());
    }

    #[Test]
    public function testNowIsCloseToRealTime(): void
    {
        $before = time();
        $now = (new SystemClock())->now()->getTimestamp();
        $after = time();

        self::assertGreaterThanOrEqual($before, $now);
        self::assertLessThanOrEqual($after, $now);
    }

    #[Test]
    public function testConsecutiveCallsAreMonotonicallyNonDecreasing(): void
    {
        $clock = new SystemClock();

        $first = $clock->now();
        $second = $clock->now();

        self::assertGreaterThanOrEqual($first, $second);
    }
}
