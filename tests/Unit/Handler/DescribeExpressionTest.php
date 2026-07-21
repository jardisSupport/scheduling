<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Handler;

use JardisSupport\Scheduling\Handler\DescribeExpression;
use JardisSupport\Scheduling\Handler\ParseExpression;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DescribeExpressionTest extends TestCase
{
    #[Test]
    #[DataProvider('expressionProvider')]
    public function testInvokeWithExpressionReturnsExpectedDescription(string $expression, string $expected): void
    {
        $fields = (new ParseExpression())($expression);
        $describe = new DescribeExpression($fields);

        self::assertSame($expected, $describe());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function expressionProvider(): array
    {
        return [
            'every minute' => ['* * * * *', 'Every minute'],
            'every 5 minutes' => ['*/5 * * * *', 'Every 5 minutes'],
            'every 15 minutes' => ['*/15 * * * *', 'Every 15 minutes'],
            'every 30 minutes' => ['*/30 * * * *', 'Every 30 minutes'],
            'daily at 09:30' => ['30 9 * * *', 'Daily at 09:30'],
            'daily at midnight' => ['0 0 * * *', 'Daily at 00:00'],
            'weekly on Monday at 09:00' => ['0 9 * * 1', 'Weekly on Monday at 09:00'],
            'weekly on Sunday at 00:00' => ['0 0 * * 0', 'Weekly on Sunday at 00:00'],
            'monthly on day 1 at 06:00' => ['0 6 1 * *', 'Monthly on day 1 at 06:00'],
            'monthly on day 15 at 12:00' => ['0 12 15 * *', 'Monthly on day 15 at 12:00'],
            'complex expression' => ['0 9-17 * * 1-5', 'Custom schedule'],
            'hourly' => ['0 * * * *', 'Custom schedule'],
        ];
    }
}
