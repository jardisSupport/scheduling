<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Handler;

use DateTimeImmutable;
use JardisSupport\Scheduling\Handler\DayOfWeek;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DayOfWeekTest extends TestCase
{
    #[Test]
    public function testInvokeWithWeekdaysMatchesMondayToFriday(): void
    {
        $constraint = DayOfWeek::weekdays();

        self::assertTrue(($constraint)(new DateTimeImmutable('2026-04-06'))); // Monday
        self::assertTrue(($constraint)(new DateTimeImmutable('2026-04-07'))); // Tuesday
        self::assertFalse(($constraint)(new DateTimeImmutable('2026-04-05'))); // Sunday
        self::assertFalse(($constraint)(new DateTimeImmutable('2026-04-04'))); // Saturday
    }

    #[Test]
    public function testInvokeWithWeekendsMatchesSaturdaySunday(): void
    {
        $constraint = DayOfWeek::weekends();

        self::assertTrue(($constraint)(new DateTimeImmutable('2026-04-05'))); // Sunday
        self::assertTrue(($constraint)(new DateTimeImmutable('2026-04-04'))); // Saturday
        self::assertFalse(($constraint)(new DateTimeImmutable('2026-04-06'))); // Monday
    }

    #[Test]
    public function testInvokeWithSpecificDaysMatchesOnlyThose(): void
    {
        $constraint = new DayOfWeek(1, 3, 5);

        self::assertTrue(($constraint)(new DateTimeImmutable('2026-04-06'))); // Monday
        self::assertTrue(($constraint)(new DateTimeImmutable('2026-04-08'))); // Wednesday
        self::assertFalse(($constraint)(new DateTimeImmutable('2026-04-07'))); // Tuesday
    }
}
