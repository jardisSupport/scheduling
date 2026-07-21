<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Handler;

use DateTimeImmutable;
use JardisSupport\Scheduling\Handler\CallableCondition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CallableConditionTest extends TestCase
{
    #[Test]
    public function testInvokeWithTrueCallbackReturnsTrue(): void
    {
        $constraint = new CallableCondition(fn() => true);

        self::assertTrue(($constraint)(new DateTimeImmutable()));
    }

    #[Test]
    public function testInvokeWithFalseCallbackReturnsFalse(): void
    {
        $constraint = new CallableCondition(fn() => false);

        self::assertFalse(($constraint)(new DateTimeImmutable()));
    }

    #[Test]
    public function testInvokeWithInvertedTrueReturnsFalse(): void
    {
        $constraint = new CallableCondition(fn() => true, inverted: true);

        self::assertFalse(($constraint)(new DateTimeImmutable()));
    }

    #[Test]
    public function testInvokeWithInvertedFalseReturnsTrue(): void
    {
        $constraint = new CallableCondition(fn() => false, inverted: true);

        self::assertTrue(($constraint)(new DateTimeImmutable()));
    }
}
