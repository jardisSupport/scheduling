# Plan: jardissupport/scheduling v1.1 — 6 neue Features

## Context

Das Scheduling-Package (v1.0) ist fertig: Cron-Parser, Fluent API, Constraints, Closure-Orchestrator Pattern. Das Contract-Package (`jardissupport/contract`) wurde mit erweiterten Interfaces deployed und ist als Dependency eingebunden. Jetzt müssen die neuen Interface-Methoden implementiert werden.

**Kritischer Namespace-Fix zuerst:** Alle `use JardisSupport\Scheduling\Contract\*` müssen zu `use JardisSupport\Contract\Scheduling\*` geändert werden (Vendor-Namespace aus dem Contract-Package).

---

## Aktuelle Struktur

```
src/
├── CronExpression.php              ← Orchestrator (chains Closures)
├── Schedule.php                    ← Orchestrator (fluent API, dueNow, allTasks)
├── TaskBuilder.php                 ← Fluent Builder
├── Data/
│   └── ScheduledTask.php           ← Value Object
├── Exception/
│   ├── InvalidCronExpressionException.php
│   └── InvalidScheduleException.php
└── Handler/
    ├── CallableCondition.php       ← Constraint: when/skip
    ├── DayOfWeek.php               ← Constraint: weekdays/weekends/days
    ├── EnvironmentMatch.php        ← Constraint: environments
    ├── FindNextRun.php             ← Iteriert vorwärts zum nächsten Cron-Match
    ├── MatchFields.php             ← Prüft Fields vs DateTime
    ├── ParseExpression.php         ← Parst Cron-String zu Field-Arrays
    ├── ResolveTimezone.php         ← Konvertiert DateTime in Ziel-Timezone
    └── TimeWindow.php              ← Constraint: between/unlessBetween
```

## Contract-Interfaces (in vendor, bereits deployed)

- `CronExpressionInterface`: isDue, nextRun, nextRuns, **previousRun** (NEU), **describe** (NEU)
- `ScheduledTaskInterface`: name, description, expression, isDue, nextRun, **tags** (NEU), **priority** (NEU), **allowsOverlapping** (NEU)
- `ScheduleInterface`: **dueNow($now, $tags=[])** (SIGNATUR GEÄNDERT), **allTasks($tags=[])** (SIGNATUR GEÄNDERT), **validate** (NEU)
- `ConstraintInterface`: __invoke (unverändert)
- `ScheduleViolation`: readonly VO mit taskName, message, severity (NEU im Contract)

---

## Ausführungsreihenfolge

### Step 0: Namespace-Fix (Prerequisite)

Alle 8 src/-Dateien: `JardisSupport\Scheduling\Contract\*` → `JardisSupport\Contract\Scheduling\*`

**Dateien:** `CronExpression.php`, `Schedule.php`, `TaskBuilder.php`, `Data/ScheduledTask.php`, `Handler/DayOfWeek.php`, `Handler/CallableCondition.php`, `Handler/EnvironmentMatch.php`, `Handler/TimeWindow.php`

---

### Step 1: `previousRun()`

**Neu:** `src/Handler/FindPreviousRun.php`
- Spiegelbild von `FindNextRun`, iteriert rückwärts (`->sub()` statt `->add()`)
- `__invoke(DateTimeImmutable $from): DateTimeImmutable`
- MAX_ITERATIONS = 525960

**Ändern:** `src/CronExpression.php`
- Neue Closure-Property `$findPreviousRun`, im `parse()` aufbauen
- Neue Methode `previousRun()`: resolveTimezone → findPreviousRun

**Tests:** `tests/Unit/Handler/FindPreviousRunTest.php`, Ergänzung `CronExpressionTest.php`

---

### Step 2: `describe()`

**Neu:** `src/Handler/DescribeExpression.php`
- Constructor nimmt parsed `$fields`, `__invoke(): string`
- Pattern-Matching Priorität:
  1. Alles Wildcard → "Every minute"
  2. Minute Step-Pattern → "Every N minutes"
  3. Minute+Hour single, Rest Wildcard → "Daily at HH:MM"
  4. Minute+Hour+Weekday single → "Weekly on DayName at HH:MM"
  5. Minute+Hour+Day single → "Monthly on day D at HH:MM"
  6. Fallback → "Custom schedule"

**Ändern:** `src/CronExpression.php`
- `$fields` im Orchestrator speichern, neue Closure `$describe`
- Neue Methode `describe(): string`

**Tests:** `tests/Unit/Handler/DescribeExpressionTest.php`, Ergänzung `CronExpressionTest.php`

---

### Step 3+4: Tags, Priority, Overlap Guard (zusammen)

**Ändern:** `src/Data/ScheduledTask.php`
- Neue Constructor-Parameter: `tags: list<string> = []`, `priority: int = 0`, `overlapping: bool = true`
- Neue Accessors: `tags()`, `priority()`, `allowsOverlapping()`, `constraints()`

**Ändern:** `src/TaskBuilder.php`
- Neue Fluent Methods: `tag(string ...$tags)`, `priority(int)`, `withoutOverlapping()`
- `build()` übergibt neue Felder an ScheduledTask
- Proxy-Methoden `dueNow()`, `allTasks()` Signaturen anpassen

**Ändern:** `src/Schedule.php`
- `dueNow(DateTimeInterface $now, array $tags = [])` — Tag-Filter + Priority-Sortierung
- `allTasks(array $tags = [])` — Tag-Filter + Priority-Sortierung
- Private Helper `matchesTags()`

**Tests:** Ergänzungen in `ScheduledTaskTest.php` und `ScheduleTest.php`

---

### Step 5: Schedule Validation

**Neu:** `src/Handler/ValidateSchedule.php`
- `__invoke(list<ScheduledTask>): list<ScheduleViolation>`
- Prüfungen: Leerer Schedule (warning), doppelte Task-Namen (error), widersprüchliche Constraints weekdays+weekends (warning)

**Ändern:** `src/Handler/DayOfWeek.php` — `getAllowedDays(): list<int>` Accessor

**Ändern:** `src/Schedule.php` — `validate()` Methode

**Tests:** `tests/Unit/Handler/ValidateScheduleTest.php`, Ergänzung `ScheduleTest.php`

---

## Neue Dateien (6)

| Datei | Zweck |
|-------|-------|
| `src/Handler/FindPreviousRun.php` | Rückwärts-Iterator |
| `src/Handler/DescribeExpression.php` | Human-readable Beschreibung |
| `src/Handler/ValidateSchedule.php` | Schedule-Validierung |
| `tests/Unit/Handler/FindPreviousRunTest.php` | Tests |
| `tests/Unit/Handler/DescribeExpressionTest.php` | Tests |
| `tests/Unit/Handler/ValidateScheduleTest.php` | Tests |

## Zu ändernde Dateien (11)

| Datei | Änderung |
|-------|----------|
| `src/CronExpression.php` | Namespace, previousRun, describe, $fields |
| `src/Schedule.php` | Namespace, Tag-Filter, Priority-Sort, validate() |
| `src/TaskBuilder.php` | Namespace, tag(), priority(), withoutOverlapping(), Proxy-Signaturen |
| `src/Data/ScheduledTask.php` | Namespace, tags/priority/overlapping, constraints() |
| `src/Handler/DayOfWeek.php` | Namespace, getAllowedDays() |
| `src/Handler/CallableCondition.php` | Namespace |
| `src/Handler/EnvironmentMatch.php` | Namespace |
| `src/Handler/TimeWindow.php` | Namespace |
| `tests/Unit/CronExpressionTest.php` | previousRun, describe Tests |
| `tests/Unit/ScheduledTaskTest.php` | tags, priority, overlapping Tests |
| `tests/Unit/ScheduleTest.php` | Tag-Filter, Priority-Sort, validate Tests |

## PHPStan Level 8 Hinweise

- `usort()` → danach `array_values()` für `list<T>` Return
- `array_filter()` → `array_values()` wrappen
- Closure-PHPDoc auf Constructor-Parameter beibehalten
- `DescribeExpression` muss `null`-Fields (Wildcards) korrekt prüfen
- `DayOfWeek::getAllowedDays()` bricht `readonly` nicht — es ist ein Getter, keine Mutation

## Verifikation

```bash
make phpunit          # Alle Tests grün
make phpstan          # Level 8, 0 Fehler
make phpcs            # PSR-12 clean
```

## Referenz-Package für Closure-Orchestrator Pattern

`jardisadapter/http` unter `/Users/Rolf/Development/headgent/jardis/adapter/http/` — dort sieht man wie HttpClient als Orchestrator Handler-Closures im Constructor bindet und in sendRequest() nur chainet.
