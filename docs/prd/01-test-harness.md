# PRD 1 — Test Harness & CI

**Umbrella:** `00-architecture-rfc.md` (RFC §3.4, §12, §11) ·
**Order:** 1 of 7 · **Breaking:** No · **Depends on:** — · **Target PR:** one

> Deliberately first. Establishes the safety net *before* any generator behavior changes in
> later PRDs. No production behavior changes here — only tests, tooling, and CI.

## Problem

- `composer test:coverage` fails: no coverage driver (pcov/xdebug) available, so the `--min=85`
  gate never runs (RFC §3.4).
- Only **3 of 15** generators have dedicated tests (Operation, Context, Skill). Action,
  Controller, Dto, Enum, Event, Exception, Model, Policy, Query, Request, Service, UseCase are
  **untested** (RFC §3.4).
- No **CLI-level** tests: tests instantiate generator classes directly, never the `Make*Command`
  classes, so argument-wiring bugs (e.g. Controller arg order, RFC D10) are invisible.
- **No CI** (`.github/` absent), no static analysis, no code style — while TESTING.md ships an
  aspirational GitHub Actions matrix as if configured (RFC §3.5).
- `toHaveMethod` expectation is defined (`tests/Pest.php:49`) but never used.

## Goal

A green, enforced safety net: coverage works and gates, every existing generator has feature +
CLI tests, and CI validates the full suite across the supported PHP 8.3, 8.4, and 8.5 range.
Zero changes to `src/` behavior.

## Scope

**In:** coverage driver, backfill tests for the 12 untested generators, CLI (`CommandTester`)
tests, GitHub Actions, PHPStan, Pint, TESTING.md truth-up.
**Out:** any change to generator/stub behavior (PRD 2/3), new generators (PRD 4/5), real-Laravel
Testbench integration (PRD 6 — this PRD stays within Pulsar's existing string/`php -l` model).

## Work items

### 1. Coverage driver
- Add `pcov` to CI and document local install; confirm `composer test:coverage`
  (`pest --coverage --min=85`) runs. If current real coverage is below 85, set `--min` to the
  measured floor and record the gap as a follow-up rather than weakening intent silently.

### 2. Backfill generator feature tests (12 files under `tests/Feature/`)
One file per generator, mirroring `OperationGeneratorTest.php`'s structure. Each asserts:
- success: file created at the exact expected path; returned relative path correct;
- `toHaveNamespace(...)`, `toHaveClass(...)`, and `toHaveMethod(...)` (activate the unused
  expectation) for the generated method names *as they are today* (Operation `handle`, Action/
  Query/UseCase `execute`) — these tests are the **regression lock** PRD 3 will update;
- `toBeValidPhp()`; no `{{placeholder}}` remnants;
- duplicate file → error; missing service (service-layer) → `ServiceDoesNotExistException`.
- `ServiceGeneratorTest`: assert the directories/files created and (documenting D8) that provider
  content is produced — do **not** fix the bug here, just characterize current behavior so PRD 2
  can change it against a passing baseline.

### 3. CLI-level tests (`tests/Feature/Commands/`)
- Use Symfony `Symfony\Component\Console\Tester\CommandTester` to run each `Make*Command`.
- Assert argument mapping (explicitly cover Controller `name/module/service` → constructor
  `name/service/module`, RFC D10), success/error output strings, and non-zero exit on failure.

### 4. Static analysis & style
- `phpstan/phpstan` config (`phpstan.neon`) at a level the current code passes (target level 6+;
  raise later). `laravel/pint` with a committed `pint.json`. Add composer scripts `analyse`,
  `lint`.

### 5. CI (`.github/workflows/ci.yml`)
- Test and quality on PHP 8.3, 8.4, and 8.5; resolve the lockfile against the PHP 8.3 platform
  floor, then run `composer install`, smoke-test the CLI, Pint `--test`, PHPStan, and Pest with
  pcov + `--min=85`.
- Commit `phpunit.xml.dist` and pass it explicitly to Pest so local and hosted runs use the same
  test and coverage configuration.
- Laravel 12/13 integration remains PRD 6 and is not part of this CI work item.

### 6. TESTING.md truth-up
- Fix "Pulse"→"Pulsar"; remove the contradictory coverage claims (single measured figure);
  correct the generator count (15, not 13); mark the CI section as real once §5 lands; note
  `toHaveMethod` is now used.

## Test plan / acceptance criteria

- `vendor/bin/pest` green; total grows from 249 with the new suites.
- `composer test:coverage` **runs and passes** its `--min` gate.
- Every generator has ≥1 feature test and ≥1 CLI test.
- Full test and quality CI green on PHP 8.3, 8.4, and 8.5; PHPStan + Pint green.
- TESTING.md contains no self-contradiction and matches the repo.

## Hardening notes

- Keep the temp-dir isolation model from `tests/Pest.php` (per-test dir, teardown) — no shared
  state across tests.
- CLI tests must assert exit codes, not just output text.

## Risks / rollback

- Low risk (additive). If measured coverage is far below 85, do **not** silently lower intent —
  set `--min` to the floor, open a tracked task to raise it, and note it in TESTING.md.
