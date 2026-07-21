<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Exception;

use InvalidArgumentException;
use JardisSupport\Scheduling\Exception\InvalidCronExpressionException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvalidCronExpressionExceptionTest extends TestCase
{
    #[Test]
    public function testFromExpressionWithoutReasonContainsExpression(): void
    {
        $exception = InvalidCronExpressionException::fromExpression('* * *');

        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        self::assertSame('Invalid cron expression: "* * *"', $exception->getMessage());
    }

    #[Test]
    public function testFromExpressionWithReasonAppendsReason(): void
    {
        $exception = InvalidCronExpressionException::fromExpression('* * *', 'too few fields');

        self::assertSame('Invalid cron expression: "* * *" (too few fields)', $exception->getMessage());
    }
}
