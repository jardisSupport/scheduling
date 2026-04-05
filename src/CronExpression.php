<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling;

use Closure;
use DateTimeInterface;
use DateTimeZone;
use JardisSupport\Contract\Scheduling\CronExpressionInterface;
use JardisSupport\Scheduling\Handler\DescribeExpression;
use JardisSupport\Scheduling\Handler\FindNextRun;
use JardisSupport\Scheduling\Handler\FindPreviousRun;
use JardisSupport\Scheduling\Handler\MatchFields;
use JardisSupport\Scheduling\Handler\ParseExpression;
use JardisSupport\Scheduling\Handler\ResolveTimezone;

/**
 * Parses and evaluates standard cron expressions (5-7 fields) with timezone support.
 */
final class CronExpression implements CronExpressionInterface
{
    private const FIELD_SECOND = 5;

    /** @var Closure(DateTimeInterface): \DateTimeImmutable */
    private readonly Closure $resolveTimezone;

    /** @var Closure(\DateTimeImmutable): bool */
    private readonly Closure $matchFields;

    /** @var Closure(\DateTimeImmutable): \DateTimeImmutable */
    private readonly Closure $findNextRun;

    /** @var Closure(\DateTimeImmutable): \DateTimeImmutable */
    private readonly Closure $findPreviousRun;

    /** @var Closure(): string */
    private readonly Closure $describe;

    /**
     * @param Closure(DateTimeInterface): \DateTimeImmutable $resolveTimezone
     * @param Closure(\DateTimeImmutable): bool $matchFields
     * @param Closure(\DateTimeImmutable): \DateTimeImmutable $findNextRun
     * @param Closure(\DateTimeImmutable): \DateTimeImmutable $findPreviousRun
     * @param Closure(): string $describe
     */
    private function __construct(
        Closure $resolveTimezone,
        Closure $matchFields,
        Closure $findNextRun,
        Closure $findPreviousRun,
        Closure $describe,
    ) {
        $this->resolveTimezone = $resolveTimezone;
        $this->matchFields = $matchFields;
        $this->findNextRun = $findNextRun;
        $this->findPreviousRun = $findPreviousRun;
        $this->describe = $describe;
    }

    public static function parse(string $expression, ?DateTimeZone $timezone = null): self
    {
        $fields = (new ParseExpression())($expression);

        $resolveTimezone = (new ResolveTimezone($timezone))->__invoke(...);
        $matchFields = (new MatchFields($fields))->__invoke(...);
        $hasSeconds = $fields[self::FIELD_SECOND] !== null;
        $findNextRun = (new FindNextRun($matchFields, $hasSeconds))->__invoke(...);
        $findPreviousRun = (new FindPreviousRun($matchFields, $hasSeconds))->__invoke(...);
        $describe = (new DescribeExpression($fields))->__invoke(...);

        return new self($resolveTimezone, $matchFields, $findNextRun, $findPreviousRun, $describe);
    }

    public function isDue(DateTimeInterface $now): bool
    {
        $resolved = ($this->resolveTimezone)($now);

        return ($this->matchFields)($resolved);
    }

    public function nextRun(DateTimeInterface $from): DateTimeInterface
    {
        $resolved = ($this->resolveTimezone)($from);

        return ($this->findNextRun)($resolved);
    }

    public function previousRun(DateTimeInterface $from): DateTimeInterface
    {
        $resolved = ($this->resolveTimezone)($from);

        return ($this->findPreviousRun)($resolved);
    }

    public function describe(): string
    {
        return ($this->describe)();
    }

    /**
     * @return list<DateTimeInterface>
     */
    public function nextRuns(DateTimeInterface $from, int $count): array
    {
        $runs = [];
        $current = $from;

        for ($i = 0; $i < $count; $i++) {
            $current = $this->nextRun($current);
            $runs[] = $current;
        }

        return $runs;
    }
}
