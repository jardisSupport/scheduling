<?php

declare(strict_types=1);

namespace JardisSupport\Scheduling\Handler;

use DateTimeInterface;
use JardisSupport\Contract\Scheduling\ConstraintInterface;

/**
 * Restricts task execution to specific application environments.
 */
final readonly class EnvironmentMatch implements ConstraintInterface
{
    /** @var list<string> */
    private array $allowedEnvironments;

    private string $currentEnvironment;

    public function __construct(string $currentEnvironment, string ...$environments)
    {
        $this->currentEnvironment = $currentEnvironment;
        $this->allowedEnvironments = array_values($environments);
    }

    public function __invoke(DateTimeInterface $now): bool
    {
        return in_array($this->currentEnvironment, $this->allowedEnvironments, true);
    }
}
