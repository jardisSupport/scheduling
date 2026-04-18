<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use JardisSupport\Scheduling\CronExpression;
use JardisSupport\Scheduling\Exception\InvalidCronExpressionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CronExpressionTest extends TestCase
{
    #[Test]
    public function testIsDueWithWildcardExpressionReturnsTrue(): void
    {
        $cron = CronExpression::parse('* * * * *');
        $now = new DateTimeImmutable('2026-04-05 10:30:00');

        self::assertTrue($cron->isDue($now));
    }

    #[Test]
    public function testIsDueWithExactMinuteAndHourMatchesCorrectly(): void
    {
        $cron = CronExpression::parse('30 10 * * *');

        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 10:30:00')));
        self::assertFalse($cron->isDue(new DateTimeImmutable('2026-04-05 10:31:00')));
        self::assertFalse($cron->isDue(new DateTimeImmutable('2026-04-05 11:30:00')));
    }

    #[Test]
    public function testIsDueWithDayAndMonthMatchesCorrectDate(): void
    {
        $cron = CronExpression::parse('0 0 1 1 *');

        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-01-01 00:00:00')));
        self::assertFalse($cron->isDue(new DateTimeImmutable('2026-02-01 00:00:00')));
    }

    #[Test]
    public function testIsDueWithWeekdayFieldMatchesMondayOnly(): void
    {
        $cron = CronExpression::parse('0 0 * * 1');
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-06 00:00:00')));
        self::assertFalse($cron->isDue(new DateTimeImmutable('2026-04-05 00:00:00')));
    }

    #[Test]
    public function testIsDueWithWeekdaySevenMatchesSunday(): void
    {
        $cron = CronExpression::parse('0 0 * * 7');
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 00:00:00')));
    }

    #[Test]
    public function testIsDueWithHourRangeMatchesBoundariesAndInterior(): void
    {
        $cron = CronExpression::parse('0 9-17 * * *');

        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 09:00:00')));
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 12:00:00')));
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 17:00:00')));
        self::assertFalse($cron->isDue(new DateTimeImmutable('2026-04-05 08:00:00')));
        self::assertFalse($cron->isDue(new DateTimeImmutable('2026-04-05 18:00:00')));
    }

    #[Test]
    public function testIsDueWithMinuteListMatchesAllValues(): void
    {
        $cron = CronExpression::parse('0,15,30,45 * * * *');

        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 10:00:00')));
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 10:15:00')));
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 10:30:00')));
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 10:45:00')));
        self::assertFalse($cron->isDue(new DateTimeImmutable('2026-04-05 10:10:00')));
    }

    #[Test]
    public function testIsDueWithStepPatternMatchesMultiples(): void
    {
        $cron = CronExpression::parse('*/15 * * * *');

        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 10:00:00')));
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 10:15:00')));
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 10:30:00')));
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 10:45:00')));
        self::assertFalse($cron->isDue(new DateTimeImmutable('2026-04-05 10:10:00')));
    }

    #[Test]
    public function testIsDueWithRangeAndStepMatchesCorrectValues(): void
    {
        $cron = CronExpression::parse('1-10/3 * * * *');

        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 10:01:00')));
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 10:04:00')));
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 10:07:00')));
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 10:10:00')));
        self::assertFalse($cron->isDue(new DateTimeImmutable('2026-04-05 10:02:00')));
    }

    #[Test]
    #[DataProvider('predefinedExpressionsProvider')]
    public function testIsDueWithPredefinedAliasMatchesEquivalent(string $alias, string $equivalent): void
    {
        $aliased = CronExpression::parse($alias);
        $explicit = CronExpression::parse($equivalent);
        $now = new DateTimeImmutable('2026-01-01 00:00:00');

        self::assertSame($aliased->isDue($now), $explicit->isDue($now));
    }

    public static function predefinedExpressionsProvider(): array
    {
        return [
            ['@yearly', '0 0 1 1 *'],
            ['@annually', '0 0 1 1 *'],
            ['@monthly', '0 0 1 * *'],
            ['@weekly', '0 0 * * 0'],
            ['@daily', '0 0 * * *'],
            ['@hourly', '0 * * * *'],
        ];
    }

    #[Test]
    public function testIsDueWithSixFieldsMatchesSecondField(): void
    {
        $cron = CronExpression::parse('30 * * * * *');

        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-04-05 10:00:30')));
        self::assertFalse($cron->isDue(new DateTimeImmutable('2026-04-05 10:00:00')));
    }

    #[Test]
    public function testIsDueWithSevenFieldsMatchesYearField(): void
    {
        $cron = CronExpression::parse('0 0 12 1 1 * 2026');

        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-01-01 12:00:00')));
        self::assertFalse($cron->isDue(new DateTimeImmutable('2027-01-01 12:00:00')));
    }

    #[Test]
    public function testIsDueWithBerlinTimezoneConvertsUtcCorrectly(): void
    {
        $cron = CronExpression::parse('0 12 * * *', new DateTimeZone('Europe/Berlin'));
        $utcTime = new DateTimeImmutable('2026-04-05 10:00:00', new DateTimeZone('UTC'));

        self::assertTrue($cron->isDue($utcTime));
    }

    #[Test]
    public function testNextRunFromBeforeMatchReturnsSameDay(): void
    {
        $cron = CronExpression::parse('30 10 * * *');
        $from = new DateTimeImmutable('2026-04-05 09:00:00');
        $next = $cron->nextRun($from);

        self::assertSame('2026-04-05 10:30:00', $next->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testNextRunFromAfterMatchWrapsToNextDay(): void
    {
        $cron = CronExpression::parse('0 8 * * *');
        $from = new DateTimeImmutable('2026-04-05 09:00:00');
        $next = $cron->nextRun($from);

        self::assertSame('2026-04-06 08:00:00', $next->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testNextRunsWithCountThreeReturnsThreeConsecutive(): void
    {
        $cron = CronExpression::parse('0 * * * *');
        $from = new DateTimeImmutable('2026-04-05 10:00:00');
        $runs = $cron->nextRuns($from, 3);

        self::assertCount(3, $runs);
        self::assertSame('2026-04-05 11:00:00', $runs[0]->format('Y-m-d H:i:s'));
        self::assertSame('2026-04-05 12:00:00', $runs[1]->format('Y-m-d H:i:s'));
        self::assertSame('2026-04-05 13:00:00', $runs[2]->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testDescribeWithWildcardReturnsEveryMinute(): void
    {
        $cron = CronExpression::parse('* * * * *');
        self::assertSame('Every minute', $cron->describe());
    }

    #[Test]
    public function testDescribeWithDailyExpressionReturnsDailyAt(): void
    {
        $cron = CronExpression::parse('30 9 * * *');
        self::assertSame('Daily at 09:30', $cron->describe());
    }

    #[Test]
    public function testDescribeWithWeeklyExpressionReturnsWeeklyOn(): void
    {
        $cron = CronExpression::parse('0 9 * * 1');
        self::assertSame('Weekly on Monday at 09:00', $cron->describe());
    }

    #[Test]
    public function testDescribeWithMonthlyExpressionReturnsMonthlyOn(): void
    {
        $cron = CronExpression::parse('0 6 1 * *');
        self::assertSame('Monthly on day 1 at 06:00', $cron->describe());
    }

    #[Test]
    public function testDescribeWithStepPatternReturnsEveryNMinutes(): void
    {
        $cron = CronExpression::parse('*/5 * * * *');
        self::assertSame('Every 5 minutes', $cron->describe());
    }

    #[Test]
    public function testPreviousRunFromAfterMatchReturnsSameDay(): void
    {
        $cron = CronExpression::parse('30 10 * * *');
        $from = new DateTimeImmutable('2026-04-05 11:00:00');
        $previous = $cron->previousRun($from);

        self::assertSame('2026-04-05 10:30:00', $previous->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testPreviousRunWrapsToPreviousDay(): void
    {
        $cron = CronExpression::parse('0 8 * * *');
        $from = new DateTimeImmutable('2026-04-05 07:00:00');
        $previous = $cron->previousRun($from);

        self::assertSame('2026-04-04 08:00:00', $previous->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testParseWithTooFewFieldsThrowsException(): void
    {
        $this->expectException(InvalidCronExpressionException::class);
        CronExpression::parse('* * *');
    }

    #[Test]
    public function testParseWithTooManyFieldsThrowsException(): void
    {
        $this->expectException(InvalidCronExpressionException::class);
        CronExpression::parse('* * * * * * * *');
    }

    #[Test]
    public function testParseWithInvertedRangeThrowsException(): void
    {
        $this->expectException(InvalidCronExpressionException::class);
        CronExpression::parse('10-5 * * * *');
    }

    #[Test]
    public function testParseWithOutOfRangeValueThrowsException(): void
    {
        $this->expectException(InvalidCronExpressionException::class);
        CronExpression::parse('60 * * * *');
    }
}
