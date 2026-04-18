---
name: support-scheduling
description: Cron parsing, task scheduling, fluent API, tags, priority, validation. Use for CronExpression, Schedule, ScheduledTask.
user-invocable: false
---

# SCHEDULING_COMPONENT_SKILL
> jardissupport/scheduling v1.1 | NS: `JardisSupport\Scheduling` | Pure-logic scheduling | PHP 8.2+

## ARCHITECTURE
```
Schedule (Orchestrator, fluent API)
  → TaskBuilder (collects config)
    → ScheduledTask (final readonly VO)
      → CronExpression (Orchestrator, 5 closures bound in parse())
          ParseExpression     string → array<int, list<int>|null>
          ResolveTimezone     DateTimeInterface → DateTimeImmutable
          MatchFields         DateTimeImmutable → bool
          FindNextRun         DateTimeImmutable → DateTimeImmutable
          FindPreviousRun     DateTimeImmutable → DateTimeImmutable
          DescribeExpression  () → string

Constraints (ConstraintInterface: __invoke(DateTimeInterface): bool)
  TimeWindow         between / unlessBetween
  DayOfWeek          weekdays / weekends / days
  CallableCondition  when / skip (inverted flag)
  EnvironmentMatch   environments

ValidateSchedule → list<ScheduleViolation>
```

## CONTRACTS (`JardisSupport\Contract\Scheduling\*`)
```php
CronExpressionInterface: isDue($now), nextRun($from), nextRuns($from, $count), previousRun($from), describe()
ScheduledTaskInterface:  name(), description(), expression(), isDue($now), nextRun($from), tags(), priority(), allowsOverlapping()
ScheduleInterface:       dueNow($now, $tags=[]), allTasks($tags=[]), validate()
ConstraintInterface:     __invoke(DateTimeInterface $now): bool
ScheduleViolation:       final readonly { taskName, message, severity = 'error' }
```

## API — Schedule (fluent)
```php
use JardisSupport\Scheduling\Schedule;

$schedule = Schedule::create('production')
    ->task('cleanup:expired')
        ->dailyAt('03:00')
        ->description('...')
        ->tag('maintenance')
        ->priority(10)
        ->withoutOverlapping()
    ->task('sync:inventory')
        ->everyFiveMinutes()
        ->between('08:00', '18:00')
        ->weekdays()
        ->tag('sync', 'erp')
        ->environments('production', 'staging');

$schedule->dueNow(new DateTimeImmutable());          // sorted by priority desc
$schedule->dueNow($now, ['sync']);                   // tag filter — OR semantics
$schedule->allTasks(['maintenance']);
$schedule->validate();                               // list<ScheduleViolation>
```

## API — CronExpression (standalone)
```php
use JardisSupport\Scheduling\CronExpression;

$cron = CronExpression::parse('*/5 9-17 * * 1-5');
$cron = CronExpression::parse('0 8 * * *', new DateTimeZone('Europe/Berlin'));

$cron->isDue($now);            // bool
$cron->nextRun($from);         // DateTimeInterface
$cron->nextRuns($from, 5);     // list<DateTimeInterface>
$cron->previousRun($from);     // DateTimeInterface
$cron->describe();             // human-readable string
```

## CRON SYNTAX
```
Fields (5):  min hour day month weekday
Fields (6):  sec min hour day month weekday
Fields (7):  sec min hour day month weekday year

Ranges:      0 9-17 * * *
Lists:       0,15,30,45 * * * *
Steps:       */5 * * * *
Combined:    1-10/3 * * * *
Predefined:  @daily @hourly @weekly @monthly @yearly @annually @midnight
```
- `null` in parsed array = wildcard; `list<int>` = resolved values.
- Weekday `7` is alias for `0` (Sunday).

## FLUENT TIME HELPERS
```
everyMinute()              * * * * *
everyFiveMinutes()         */5 * * * *
everyFifteenMinutes()      */15 * * * *
everyThirtyMinutes()       */30 * * * *
hourly()                   0 * * * *
hourlyAt(int $min)         $min * * * *
daily()                    0 0 * * *
dailyAt('08:00')           0 8 * * *
weekly()                   0 0 * * 0
weeklyOn(int $day, 'HH:MM')
monthly()                  0 0 1 * *
monthlyOn(int $day, 'HH:MM')
yearly()                   0 0 1 1 *
cron(string $expr)         direct expression
```

## CONSTRAINTS
```php
->between('08:00', '18:00')
->unlessBetween('02:00', '06:00')
->weekdays()                      // Mon–Fri
->weekends()                      // Sat–Sun
->days(2, 4)                      // 0=Sun … 6=Sat
->when(fn() => $flag)             // true = allow
->skip(fn() => $flag)             // true = skip
->environments('production')      // matches Schedule::create($env)
```

## DESCRIBE PATTERNS
| Expression | Output |
|------------|--------|
| `* * * * *` | `'Every minute'` |
| `*/N * * * *` | `'Every N minutes'` |
| `M H * * *` | `'Daily at HH:MM'` |
| `M H * * D` | `'Weekly on DayName at HH:MM'` |
| `M H D * *` | `'Monthly on day D at HH:MM'` |
| anything else | `'Custom schedule'` |

## EXCEPTIONS
```php
InvalidCronExpressionException::fromExpression(string $expr, string $reason)
InvalidScheduleException::missingName()
InvalidScheduleException::missingExpression(string $taskName)
InvalidScheduleException::invalidTime(string $time)
```

## CONVENTIONS
- Tags: OR semantics when filtering; `array_values(array_unique(...))` on merge.
- Priority: sorted descending (highest value first).
- `ScheduledTask::constraints()` accessor used by `ValidateSchedule`.
- `ValidateSchedule` checks: empty schedule (warning), duplicate names (error), conflicting day constraints (warning).
