<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Handler;

use DateTimeImmutable;
use JardisSupport\Scheduling\Handler\TimeWindow;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TimeWindowTest extends TestCase
{
    #[Test]
    public function testInvokeWithinWindowReturnsTrue(): void
    {
        $constraint = new TimeWindow('08:00', '18:00');

        self::assertTrue(($constraint)(new DateTimeImmutable('2026-04-05 10:00:00')));
        self::assertTrue(($constraint)(new DateTimeImmutable('2026-04-05 08:00:00')));
        self::assertTrue(($constraint)(new DateTimeImmutable('2026-04-05 18:00:00')));
    }

    #[Test]
    public function testInvokeOutsideWindowReturnsFalse(): void
    {
        $constraint = new TimeWindow('08:00', '18:00');

        self::assertFalse(($constraint)(new DateTimeImmutable('2026-04-05 07:59:00')));
        self::assertFalse(($constraint)(new DateTimeImmutable('2026-04-05 18:01:00')));
    }

    #[Test]
    public function testInvokeWithInvertedWindowInvertsResult(): void
    {
        $constraint = new TimeWindow('02:00', '06:00', inverted: true);

        self::assertTrue(($constraint)(new DateTimeImmutable('2026-04-05 10:00:00')));
        self::assertFalse(($constraint)(new DateTimeImmutable('2026-04-05 03:00:00')));
    }
}
