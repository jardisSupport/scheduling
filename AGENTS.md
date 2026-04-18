# jardissupport/scheduling

Pure-logic scheduling library: cron parsing + task scheduling with fluent API, constraints, tags and priority. Defines *when* tasks should run without executing them — no I/O, no persistence, no external services.

## Usage essentials

- **Closure-Orchestrator Pattern strictly:** `Schedule` and `CronExpression` are Orchestrators in the `src/` root with **no business logic** — they bind Handler closures from `src/Handler/` via `(new Handler())->__invoke(...)` in the constructor. `CronExpression::parse()` wires 5 closures (`ResolveTimezone`, `MatchFields`, `FindNextRun`, `FindPreviousRun`, `DescribeExpression`); `ScheduledTask` is a `final readonly` VO.
- **Contracts come from `jardissupport/contract`:** Namespace `JardisSupport\Contract\Scheduling\*` (**not** `JardisSupport\Scheduling\Contract\*`). Public interfaces: `CronExpressionInterface`, `ScheduledTaskInterface`, `ScheduleInterface`, `ConstraintInterface` (`__invoke(DateTimeInterface $now): bool`) and `ScheduleViolation` (readonly VO with `taskName`/`message`/`severity`).
- **Cron expression syntax supports 5/6/7 fields:** 5 = `min hour day month weekday`, 6 = with seconds prefix, 7 = with year suffix. Wildcards (`*`), ranges (`9-17`), lists (`0,15,30`), steps (`*/5`), combinations (`1-10/3`) and predefined (`@daily`/`@hourly`/`@weekly`/`@monthly`/`@yearly`/`@midnight`). `ParseExpression` returns per field `null` (wildcard) or `list<int>` (resolved values); weekday `7` is an alias for `0` (Sunday).
- **Fluent API `Schedule::create($env)` is the default entry point:** `->task(name)->dailyAt/everyFiveMinutes/monthlyOn(...)` sets the expression, `->description()`/`->tag()`/`->priority($int)`/`->withoutOverlapping()` sets the metadata, `->between()`/`->weekdays()`/`->days(...)`/`->when(fn)`/`->skip(fn)`/`->environments(...)` sets the constraints. `EnvironmentMatch` requires a `Schedule::create($env)` — otherwise it never matches.
- **Query semantics:** `dueNow($now, $tags=[])` and `allTasks($tags=[])` sort **descending by priority** (highest value first, `usort` + spaceship) and filter tags with **OR semantics**. `validate()` returns `list<ScheduleViolation>` — empty schedule = warning, duplicate names = error, conflicting day constraints = warning. Exceptions: `InvalidCronExpressionException`, `InvalidScheduleException` (static factories: `missingName()`, `missingExpression()`, `invalidTime()`).
- **PHP code invariants:** PHP 8.3, `declare(strict_types=1)`, PHPStan Level 8, PSR-12, line length 120/150. No `traits`, no `abstract` (except exception hierarchy), no `static` (except VO named constructors like `CronExpression::parse()`). With `array_filter()` always wrap with `array_values(...)` for `list<T>` return types.

## Full reference

https://docs.jardis.io/en/support/scheduling
