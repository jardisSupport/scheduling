# Jardis Scheduling

![Build Status](https://github.com/jardisSupport/scheduling/actions/workflows/ci.yml/badge.svg)
[![License: PolyForm Shield](https://img.shields.io/badge/License-PolyForm%20Shield-blue.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.2-777BB4.svg)](https://www.php.net/)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%208-brightgreen.svg)](phpstan.neon)
[![PSR-12](https://img.shields.io/badge/Code%20Style-PSR--12-blue.svg)](phpcs.xml)

> Part of the **[Jardis Business Platform](https://jardis.io)** — Enterprise-grade PHP components for Domain-Driven Design

**Scheduling rules as code.** Cron expression parsing and task scheduling with a fluent API — defines *when* tasks should run without executing them. No I/O, no persistence, no external dependencies. Pure PHP time logic.

---

## Why This Package?

- **Two ways to define timing** — Cron syntax (`*/5 9-17 * * 1-5`) for power users, fluent helpers (`->dailyAt('08:00')`) for everyone
- **Constraints beyond cron** — time windows, weekdays, environments, callable conditions
- **Tags & priority** — categorize tasks, filter by tag, execute in priority order
- **Overlap guard** — mark tasks that must not run concurrently
- **Human-readable descriptions** — `describe()` turns cron syntax into readable text
- **Schedule validation** — detect duplicate names, conflicting constraints before runtime
- **Timezone-aware** — expressions evaluate against any timezone, regardless of server time
- **Testable** — pass any `DateTimeInterface` to `isDue()`, no system clock dependency
- **Zero dependencies** — pure PHP, no framework, no cron daemon required

---

## Installation

```bash
composer require jardissupport/scheduling
```

---

## Quick Start

### Define a Schedule

```php
use JardisSupport\Scheduling\Schedule;

$schedule = Schedule::create()
    ->task('cleanup:expired')
        ->dailyAt('03:00')
        ->description('Remove expired records')
        ->tag('maintenance')
        ->priority(10)
    ->task('sync:inventory')
        ->everyFiveMinutes()
        ->between('08:00', '18:00')
        ->weekdays()
        ->tag('sync', 'erp')
        ->withoutOverlapping()
    ->task('report:monthly')
        ->monthlyOn(1, '07:00')
        ->timezone('Europe/Berlin')
        ->tag('reports');
```

### Get Due Tasks

```php
$now = new DateTimeImmutable();

// All due tasks (sorted by priority, highest first)
foreach ($schedule->dueNow($now) as $task) {
    echo $task->name();        // 'cleanup:expired'
    echo $task->description(); // 'Remove expired records'
    // Dispatch however you want — command bus, queue, subprocess
}

// Filter by tags
$syncTasks = $schedule->dueNow($now, ['sync']);
```

---

## Cron Expression Parser

Standalone cron parsing — usable without the Schedule API:

```php
use JardisSupport\Scheduling\CronExpression;

$cron = CronExpression::parse('*/5 9-17 * * 1-5');

$cron->isDue($now);              // true/false
$cron->nextRun($now);            // next matching DateTimeInterface
$cron->nextRuns($now, 5);        // next 5 matching times
$cron->previousRun($now);        // last matching DateTimeInterface
$cron->describe();               // 'Every 5 minutes', 'Daily at 09:30', etc.
```

### Supported Syntax

| Feature | Example |
|---------|---------|
| Standard 5-field | `30 8 * * *` |
| Ranges | `0 9-17 * * *` |
| Lists | `0,15,30,45 * * * *` |
| Steps | `*/5 * * * *` |
| Combined | `1-10/3 * * * *` |
| Seconds (6-field) | `*/30 * * * * *` |
| Year (7-field) | `0 0 3 1 1 * 2027` |
| Predefined | `@daily`, `@hourly`, `@weekly`, `@monthly`, `@yearly` |

### Timezone Support

```php
$cron = CronExpression::parse('0 8 * * *', new DateTimeZone('Europe/Berlin'));

// Evaluates against Berlin time, regardless of server timezone
$cron->isDue(new DateTimeImmutable('now', new DateTimeZone('UTC'))); 
```

---

## Fluent Time Helpers

No cron syntax required — readable method names that generate the right expressions:

| Method | Equivalent |
|--------|-----------|
| `everyMinute()` | `* * * * *` |
| `everyFiveMinutes()` | `*/5 * * * *` |
| `everyFifteenMinutes()` | `*/15 * * * *` |
| `everyThirtyMinutes()` | `*/30 * * * *` |
| `hourly()` | `0 * * * *` |
| `hourlyAt(30)` | `30 * * * *` |
| `daily()` | `0 0 * * *` |
| `dailyAt('08:00')` | `0 8 * * *` |
| `weekly()` | `0 0 * * 0` |
| `weeklyOn(5, '14:00')` | `0 14 * * 5` |
| `monthly()` | `0 0 1 * *` |
| `monthlyOn(25, '06:00')` | `0 6 25 * *` |
| `yearly()` | `0 0 1 1 *` |
| `cron('...')` | Direct expression |

---

## Constraints

Additional restrictions beyond the cron expression — all composable:

### Time Windows

```php
->task('api:sync')
    ->everyFiveMinutes()
    ->between('08:00', '20:00')       // only during this window

->task('db:optimize')
    ->daily()
    ->unlessBetween('09:00', '17:00') // not during business hours
```

### Day Restrictions

```php
->task('erp:sync')
    ->hourly()
    ->weekdays()                      // Mon-Fri only

->task('backup:full')
    ->dailyAt('01:00')
    ->weekends()                      // Sat-Sun only

->task('supplier:import')
    ->dailyAt('06:00')
    ->days(2, 4)                      // Tue and Thu only (0=Sun, 6=Sat)
```

### Callable Conditions

```php
->task('beta:sync')
    ->everyFiveMinutes()
    ->when(fn() => $features->isEnabled('new-sync'))   // only if true

->task('cache:warmup')
    ->everyMinute()
    ->skip(fn() => $maintenance->isActive())           // skip if true
```

### Environment Restriction

```php
$schedule = Schedule::create('production')  // pass current environment
    ->task('monitor:uptime')
        ->everyMinute()
        ->environments('production', 'staging');
```

---

## Tags, Priority & Overlap Guard

### Tags

Categorize tasks and filter by tag when querying:

```php
->task('email:digest')
    ->dailyAt('08:00')
    ->tag('email', 'notifications')

// Query filtered
$schedule->dueNow($now, ['email']);     // only tasks tagged 'email'
$schedule->allTasks(['notifications']); // only tasks tagged 'notifications'
```

Tags use OR-semantics — a task matches if it has *any* of the requested tags.

### Priority

Higher priority tasks are returned first:

```php
->task('critical:alerts')
    ->everyMinute()
    ->priority(100)

->task('low:cleanup')
    ->everyMinute()
    ->priority(1)

// dueNow() and allTasks() return tasks sorted by priority (descending)
```

### Overlap Guard

Mark tasks that should not run concurrently:

```php
->task('import:large')
    ->everyFiveMinutes()
    ->withoutOverlapping()

// Check in your runner:
if (!$task->allowsOverlapping()) {
    // Acquire lock before executing
}
```

---

## Human-Readable Descriptions

```php
CronExpression::parse('* * * * *')->describe();      // 'Every minute'
CronExpression::parse('*/5 * * * *')->describe();     // 'Every 5 minutes'
CronExpression::parse('30 9 * * *')->describe();      // 'Daily at 09:30'
CronExpression::parse('0 9 * * 1')->describe();       // 'Weekly on Monday at 09:00'
CronExpression::parse('0 6 1 * *')->describe();       // 'Monthly on day 1 at 06:00'
CronExpression::parse('0 9-17 * * 1-5')->describe();  // 'Custom schedule'
```

---

## Previous Run

Find the most recent time a cron expression would have matched:

```php
$cron = CronExpression::parse('0 8 * * *');
$previous = $cron->previousRun(new DateTimeImmutable('2026-04-05 10:00:00'));
// 2026-04-05 08:00:00
```

---

## Schedule Validation

Detect configuration problems before runtime:

```php
$violations = $schedule->validate();

foreach ($violations as $violation) {
    echo $violation->severity;  // 'error' or 'warning'
    echo $violation->taskName;
    echo $violation->message;
}
```

| Check | Severity |
|-------|----------|
| Empty schedule (no tasks) | warning |
| Duplicate task names | error |
| Conflicting weekdays + weekends constraints | warning |

---

## Inspecting the Schedule

```php
// All registered tasks (sorted by priority)
foreach ($schedule->allTasks() as $task) {
    echo $task->name();
    echo $task->description();
    echo $task->expression()->describe();
    echo $task->nextRun(new DateTimeImmutable())->format('Y-m-d H:i');
    echo $task->priority();
    echo $task->allowsOverlapping() ? 'yes' : 'no';
    echo implode(', ', $task->tags());
}

// Filter by tags
$emailTasks = $schedule->allTasks(['email']);
```

---

## Error Handling

| Exception | When |
|-----------|------|
| `InvalidCronExpressionException` | Unparseable cron syntax |
| `InvalidScheduleException` | Task without name, missing expression, invalid time format |

```php
use JardisSupport\Scheduling\Exception\InvalidCronExpressionException;

try {
    CronExpression::parse('invalid');
} catch (InvalidCronExpressionException $e) {
    // "Invalid cron expression: "invalid" (Expected 5-7 fields, got 1)"
}
```

---

## Architecture

The user sees `Schedule` + fluent API. Internally, each concern is its own class:

```
Schedule (Orchestrator)
  │
  │  Fluent API: task() returns TaskBuilder
  │  Query: dueNow($now, $tags), allTasks($tags), validate()
  │
  ├── TaskBuilder (Internal)
  │   └── Fluent methods �� builds ScheduledTask
  │
  ├── ScheduledTask (Value Object)
  │   ├── name, description, tags, priority, overlapping
  │   ├── CronExpression
  │   ├── Constraints[]
  │   └── isDue(): expression.isDue() && all constraints satisfied
  │
  ├── CronExpression (Orchestrator)
  │   ├── parse() → field arrays (null = wildcard)
  │   ├── isDue() → compare fields against DateTime
  │   ├── nextRun() → iterate forward until match
  │   ├── previousRun() → iterate backward until match
  │   └── describe() → human-readable description
  │
  ├── Constraints (ConstraintInterface)
  │   ├── TimeWindow         between/unlessBetween
  │   ├── DayOfWeek          weekdays/weekends/days
  │   ├── CallableCondition  when/skip
  │   └── EnvironmentMatch   environments
  │
  └── ValidateSchedule → list<ScheduleViolation>
```

### What This Package Does NOT Do

- **No task execution** — no process spawning, no workers, no daemons
- **No persistence** — no database, no last-run tracking
- **No overlap prevention** — no locking (the flag is advisory for the runner)
- **No queue integration** — no message dispatch
- **No retry/error handling** — that's the runner's job

The runner calls `dueNow()` and decides what to do with the results.

---

## Jardis Foundation Integration

Scheduling is a **support package** — no Foundation handler, no ENV configuration. The schedule is defined programmatically in your application layer:

```php
// In your BoundedContext or Application Service:
$schedule = Schedule::create()
    ->task('order:cleanup')->dailyAt('03:00')
    ->task('invoice:generate')->monthlyOn(1, '06:00');

// Runner (CLI Command, Cron Job):
foreach ($schedule->dueNow(new DateTimeImmutable()) as $task) {
    $this->commandBus->dispatch($task->name());
}
```

---

## Development

```bash
cp .env.example .env    # One-time setup
make install             # Install dependencies
make phpunit             # Run tests
make phpstan             # Static analysis (Level 8)
make phpcs               # Coding standards (PSR-12)
```

---

## License

[PolyForm Shield License 1.0.0](LICENSE.md) — free for all use including commercial. Only restriction: don't build a competing framework.
