<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Tests\Unit\Handler;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use JardisSupport\Scheduling\Handler\ResolveTimezone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResolveTimezoneTest extends TestCase
{
    #[Test]
    public function testResolveWithoutTimezoneReturnsUnchanged(): void
    {
        $resolve = new ResolveTimezone(null);
        $input = new DateTimeImmutable('2026-04-05 10:00:00', new DateTimeZone('UTC'));

        $result = $resolve($input);

        self::assertSame('2026-04-05 10:00:00', $result->format('Y-m-d H:i:s'));
        self::assertSame('UTC', $result->getTimezone()->getName());
    }

    #[Test]
    public function testResolveWithTimezoneConvertsCorrectly(): void
    {
        $resolve = new ResolveTimezone(new DateTimeZone('Europe/Berlin'));
        $input = new DateTimeImmutable('2026-04-05 10:00:00', new DateTimeZone('UTC'));

        $result = $resolve($input);

        self::assertSame('Europe/Berlin', $result->getTimezone()->getName());
        self::assertSame('2026-04-05 12:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testResolveMutableDateTimeReturnsImmutable(): void
    {
        $resolve = new ResolveTimezone(null);
        $input = new DateTime('2026-04-05 10:00:00', new DateTimeZone('UTC'));

        $result = $resolve($input);

        self::assertInstanceOf(DateTimeImmutable::class, $result);
        self::assertSame('2026-04-05 10:00:00', $result->format('Y-m-d H:i:s'));
    }
}
