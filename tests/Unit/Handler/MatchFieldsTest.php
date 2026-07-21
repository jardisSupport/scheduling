<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Handler;

use DateTimeImmutable;
use JardisSupport\Scheduling\Handler\MatchFields;
use JardisSupport\Scheduling\Handler\ParseExpression;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MatchFieldsTest extends TestCase
{
    #[Test]
    public function testMatchAllWildcardsReturnsTrue(): void
    {
        $fields = (new ParseExpression())('* * * * *');
        $match = new MatchFields($fields);

        self::assertTrue($match(new DateTimeImmutable('2026-04-05 10:30:00')));
    }

    #[Test]
    public function testMatchExactMinuteHourReturnsTrue(): void
    {
        $fields = (new ParseExpression())('30 10 * * *');
        $match = new MatchFields($fields);

        self::assertTrue($match(new DateTimeImmutable('2026-04-05 10:30:00')));
    }

    #[Test]
    public function testMatchWrongMinuteReturnsFalse(): void
    {
        $fields = (new ParseExpression())('30 10 * * *');
        $match = new MatchFields($fields);

        self::assertFalse($match(new DateTimeImmutable('2026-04-05 10:31:00')));
    }

    #[Test]
    public function testMatchWeekdayMondayReturnsTrue(): void
    {
        $fields = (new ParseExpression())('0 0 * * 1');
        $match = new MatchFields($fields);

        self::assertTrue($match(new DateTimeImmutable('2026-04-06 00:00:00'))); // Monday
    }

    #[Test]
    public function testMatchWeekdaySundayAsSeven(): void
    {
        $fields = (new ParseExpression())('0 0 * * 7');
        $match = new MatchFields($fields);

        self::assertTrue($match(new DateTimeImmutable('2026-04-05 00:00:00'))); // Sunday
    }

    #[Test]
    public function testMatchMonthFieldReturnsCorrectly(): void
    {
        $fields = (new ParseExpression())('0 0 1 1 *');
        $match = new MatchFields($fields);

        self::assertTrue($match(new DateTimeImmutable('2026-01-01 00:00:00')));
        self::assertFalse($match(new DateTimeImmutable('2026-02-01 00:00:00')));
    }

    #[Test]
    public function testMatchSecondFieldReturnsCorrectly(): void
    {
        $fields = (new ParseExpression())('30 * * * * *');
        $match = new MatchFields($fields);

        self::assertTrue($match(new DateTimeImmutable('2026-04-05 10:00:30')));
        self::assertFalse($match(new DateTimeImmutable('2026-04-05 10:00:00')));
    }

    #[Test]
    public function testMatchYearFieldReturnsCorrectly(): void
    {
        $fields = (new ParseExpression())('0 0 12 1 1 * 2026');
        $match = new MatchFields($fields);

        self::assertTrue($match(new DateTimeImmutable('2026-01-01 12:00:00')));
        self::assertFalse($match(new DateTimeImmutable('2027-01-01 12:00:00')));
    }
}
