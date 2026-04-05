<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Exception;

use InvalidArgumentException;
use JardisSupport\Scheduling\Exception\InvalidScheduleException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvalidScheduleExceptionTest extends TestCase
{
    #[Test]
    public function testMissingNameReturnsCorrectMessage(): void
    {
        $exception = InvalidScheduleException::missingName();

        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        self::assertSame('Scheduled task requires a name', $exception->getMessage());
    }

    #[Test]
    public function testMissingExpressionContainsTaskName(): void
    {
        $exception = InvalidScheduleException::missingExpression('my-task');

        self::assertSame(
            'Scheduled task "my-task" requires a cron expression',
            $exception->getMessage()
        );
    }

    #[Test]
    public function testInvalidTimeContainsTimeValue(): void
    {
        $exception = InvalidScheduleException::invalidTime('25:00');

        self::assertSame(
            'Invalid time format: "25:00" (expected HH:MM, e.g. "09:30")',
            $exception->getMessage()
        );
    }
}
