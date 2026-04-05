<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Handler;

use DateTimeImmutable;
use JardisSupport\Scheduling\Handler\EnvironmentMatch;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnvironmentMatchTest extends TestCase
{
    #[Test]
    public function testInvokeWithMatchingEnvironmentReturnsTrue(): void
    {
        $constraint = new EnvironmentMatch('production', 'production', 'staging');

        self::assertTrue(($constraint)(new DateTimeImmutable()));
    }

    #[Test]
    public function testInvokeWithNonMatchingEnvironmentReturnsFalse(): void
    {
        $constraint = new EnvironmentMatch('development', 'production', 'staging');

        self::assertFalse(($constraint)(new DateTimeImmutable()));
    }
}
