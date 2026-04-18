<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Handler;

use Closure;
use DateTimeInterface;
use JardisSupport\Contract\Scheduling\ConstraintInterface;

/**
 * Evaluates a callable condition to determine task eligibility (when/skip).
 */
final readonly class CallableCondition implements ConstraintInterface
{
    public function __construct(
        private Closure $callback,
        private bool $inverted = false,
    ) {
    }

    public function __invoke(DateTimeInterface $now): bool
    {
        $result = (bool) ($this->callback)();

        return $this->inverted ? !$result : $result;
    }
}
