<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Handler;

use DateTimeImmutable;
use JardisSupport\Scheduling\Handler\FindPreviousRun;
use JardisSupport\Scheduling\Handler\MatchFields;
use JardisSupport\Scheduling\Handler\ParseExpression;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FindPreviousRunTest extends TestCase
{
    #[Test]
    public function testInvokeFromAfterMatchReturnsSameDay(): void
    {
        $fields = (new ParseExpression())('30 10 * * *');
        $matchFields = (new MatchFields($fields))->__invoke(...);
        $findPrevious = new FindPreviousRun($matchFields, false);

        $from = new DateTimeImmutable('2026-04-05 11:00:00');
        $previous = $findPrevious($from);

        self::assertSame('2026-04-05 10:30:00', $previous->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testInvokeFromBeforeMatchWrapsToPreviousDay(): void
    {
        $fields = (new ParseExpression())('0 8 * * *');
        $matchFields = (new MatchFields($fields))->__invoke(...);
        $findPrevious = new FindPreviousRun($matchFields, false);

        $from = new DateTimeImmutable('2026-04-05 07:00:00');
        $previous = $findPrevious($from);

        self::assertSame('2026-04-04 08:00:00', $previous->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testInvokeWithHourlyExpressionReturnsPreviousFullHour(): void
    {
        $fields = (new ParseExpression())('0 * * * *');
        $matchFields = (new MatchFields($fields))->__invoke(...);
        $findPrevious = new FindPreviousRun($matchFields, false);

        $from = new DateTimeImmutable('2026-04-05 10:30:00');
        $previous = $findPrevious($from);

        self::assertSame('2026-04-05 10:00:00', $previous->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testInvokeWithSecondsFieldReturnsCorrectSecond(): void
    {
        $fields = (new ParseExpression())('30 * * * * *');
        $matchFields = (new MatchFields($fields))->__invoke(...);
        $findPrevious = new FindPreviousRun($matchFields, true);

        $from = new DateTimeImmutable('2026-04-05 10:01:00');
        $previous = $findPrevious($from);

        self::assertSame('2026-04-05 10:00:30', $previous->format('Y-m-d H:i:s'));
    }
}
