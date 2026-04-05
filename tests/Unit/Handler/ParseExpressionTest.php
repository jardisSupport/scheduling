<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Handler;

use JardisSupport\Scheduling\Exception\InvalidCronExpressionException;
use JardisSupport\Scheduling\Handler\ParseExpression;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParseExpressionTest extends TestCase
{
    #[Test]
    public function testParseWildcardExpressionReturnsAllNull(): void
    {
        $fields = (new ParseExpression())('* * * * *');

        self::assertNull($fields[0]); // minute
        self::assertNull($fields[1]); // hour
        self::assertNull($fields[2]); // day
        self::assertNull($fields[3]); // month
        self::assertNull($fields[4]); // weekday
        self::assertNull($fields[5]); // second
        self::assertNull($fields[6]); // year
    }

    #[Test]
    public function testParseSingleValuesReturnsLists(): void
    {
        $fields = (new ParseExpression())('30 10 1 6 3');

        self::assertSame([30], $fields[0]);
        self::assertSame([10], $fields[1]);
        self::assertSame([1], $fields[2]);
        self::assertSame([6], $fields[3]);
        self::assertSame([3], $fields[4]);
    }

    #[Test]
    public function testParseRangeExpandsToSortedList(): void
    {
        $fields = (new ParseExpression())('0 9-12 * * *');

        self::assertSame([9, 10, 11, 12], $fields[1]);
    }

    #[Test]
    public function testParseStepFromWildcardExpandsCorrectly(): void
    {
        $fields = (new ParseExpression())('*/15 * * * *');

        self::assertSame([0, 15, 30, 45], $fields[0]);
    }

    #[Test]
    public function testParseRangeWithStepExpandsCorrectly(): void
    {
        $fields = (new ParseExpression())('1-10/3 * * * *');

        self::assertSame([1, 4, 7, 10], $fields[0]);
    }

    #[Test]
    public function testParseListSortsAndDeduplicates(): void
    {
        $fields = (new ParseExpression())('45,15,30,0,15 * * * *');

        self::assertSame([0, 15, 30, 45], $fields[0]);
    }

    #[Test]
    #[DataProvider('predefinedAliasProvider')]
    public function testParsePredefinedAliasMatchesEquivalent(string $alias, string $equivalent): void
    {
        $parse = new ParseExpression();

        self::assertSame($parse($equivalent), $parse($alias));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function predefinedAliasProvider(): array
    {
        return [
            '@yearly'   => ['@yearly', '0 0 1 1 *'],
            '@annually' => ['@annually', '0 0 1 1 *'],
            '@monthly'  => ['@monthly', '0 0 1 * *'],
            '@weekly'   => ['@weekly', '0 0 * * 0'],
            '@daily'    => ['@daily', '0 0 * * *'],
            '@midnight' => ['@midnight', '0 0 * * *'],
            '@hourly'   => ['@hourly', '0 * * * *'],
        ];
    }

    #[Test]
    public function testParseSixFieldsDetectsSeconds(): void
    {
        $fields = (new ParseExpression())('30 * * * * *');

        self::assertSame([30], $fields[5]); // second
        self::assertNull($fields[0]);       // minute = wildcard
    }

    #[Test]
    public function testParseSevenFieldsDetectsYear(): void
    {
        $fields = (new ParseExpression())('0 0 12 1 1 * 2026');

        self::assertSame([0], $fields[5]);    // second
        self::assertSame([2026], $fields[6]); // year
    }

    #[Test]
    public function testParseWeekdaySevenNormalizesInValues(): void
    {
        $fields = (new ParseExpression())('0 0 * * 7');

        self::assertSame([7], $fields[4]);
    }

    #[Test]
    public function testParseTooFewFieldsThrowsException(): void
    {
        $this->expectException(InvalidCronExpressionException::class);

        (new ParseExpression())('* * *');
    }

    #[Test]
    public function testParseTooManyFieldsThrowsException(): void
    {
        $this->expectException(InvalidCronExpressionException::class);

        (new ParseExpression())('* * * * * * * *');
    }

    #[Test]
    public function testParseInvertedRangeThrowsException(): void
    {
        $this->expectException(InvalidCronExpressionException::class);

        (new ParseExpression())('10-5 * * * *');
    }

    #[Test]
    public function testParseOutOfRangeValueThrowsException(): void
    {
        $this->expectException(InvalidCronExpressionException::class);

        (new ParseExpression())('60 * * * *');
    }

    #[Test]
    public function testParseZeroStepThrowsException(): void
    {
        $this->expectException(InvalidCronExpressionException::class);

        (new ParseExpression())('*/0 * * * *');
    }
}
