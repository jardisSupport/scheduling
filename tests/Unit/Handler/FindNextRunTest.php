<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Handler;

use DateTimeImmutable;
use JardisSupport\Scheduling\Handler\FindNextRun;
use JardisSupport\Scheduling\Handler\MatchFields;
use JardisSupport\Scheduling\Handler\ParseExpression;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FindNextRunTest extends TestCase
{
    #[Test]
    public function testFindNextRunFromBeforeMatchReturnsSameDay(): void
    {
        $fields = (new ParseExpression())('30 10 * * *');
        $matchFields = (new MatchFields($fields))->__invoke(...);
        $findNext = new FindNextRun($matchFields, false);

        $from = new DateTimeImmutable('2026-04-05 09:00:00');

        self::assertSame('2026-04-05 10:30:00', $findNext($from)->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testFindNextRunFromAfterMatchWrapsToNextDay(): void
    {
        $fields = (new ParseExpression())('0 8 * * *');
        $matchFields = (new MatchFields($fields))->__invoke(...);
        $findNext = new FindNextRun($matchFields, false);

        $from = new DateTimeImmutable('2026-04-05 09:00:00');

        self::assertSame('2026-04-06 08:00:00', $findNext($from)->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testFindNextRunHourlyReturnsNextFullHour(): void
    {
        $fields = (new ParseExpression())('0 * * * *');
        $matchFields = (new MatchFields($fields))->__invoke(...);
        $findNext = new FindNextRun($matchFields, false);

        $from = new DateTimeImmutable('2026-04-05 10:30:00');

        self::assertSame('2026-04-05 11:00:00', $findNext($from)->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testFindNextRunWithSecondsFieldReturnsCorrectSecond(): void
    {
        $fields = (new ParseExpression())('30 * * * * *');
        $matchFields = (new MatchFields($fields))->__invoke(...);
        $findNext = new FindNextRun($matchFields, true);

        $from = new DateTimeImmutable('2026-04-05 10:00:00');

        self::assertSame('2026-04-05 10:00:30', $findNext($from)->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testFindNextRunFloorsSecondsInMinuteMode(): void
    {
        $fields = (new ParseExpression())('30 10 * * *');
        $matchFields = (new MatchFields($fields))->__invoke(...);
        $findNext = new FindNextRun($matchFields, false);

        $from = new DateTimeImmutable('2026-04-05 09:00:45');

        self::assertSame('2026-04-05 10:30:00', $findNext($from)->format('Y-m-d H:i:s'));
    }
}
