# Testing Pulsar

Pulsar tests generated files in isolated mock Laravel project directories. The package test
suite checks filesystem placement, namespaces, declarations, public methods, CLI argument
wiring, command output, exit codes, and PHP syntax without requiring Laravel as a package
dependency.

## Current measured state

The latest local run on July 24, 2026 produced:

```text
Tests:    304 passed (538 assertions)
Coverage: 90.5%
```

The measured line-coverage figure is **90.5%**. The enforced minimum remains **85%**.

All 15 existing generators have dedicated feature coverage:

- Service layer: Service, Controller, Request, UseCase, Operation
- Domain layer: Model, Action, Dto, Policy, Event, Enum, Exception, Query
- Publishing: Context, Skill

Every one of the 13 `Make*Command` classes is exercised through Symfony's `CommandTester`.
Those tests assert successful and failed exit codes, output, generated paths, and the
Controller `name/module/service` to `name/service/module` constructor mapping.

Generated PHP is checked with the `toBeValidPhp`, `toHaveNamespace`, and `toHaveClass`
expectations. Tests now use Pest's reflection-based `toHaveMethod` expectation to lock current
generated method names, including `Operation::handle()` and
`Action::execute()` / `Query::execute()` / `UseCase::execute()`.

## Running the checks

Install development dependencies:

```bash
composer install
```

Run the complete Pest suite:

```bash
vendor/bin/pest
```

Run the coverage gate:

```bash
composer test:coverage
```

Run static analysis and style checks:

```bash
composer analyse
composer lint
```

Run one test file or a matching subset:

```bash
vendor/bin/pest tests/Feature/OperationGeneratorTest.php
vendor/bin/pest --filter=validateName
```

## Local coverage driver

`composer test:coverage` requires PCOV. CI installs and enables it automatically. For a local
PECL-based PHP installation:

```bash
pecl install pcov
```

Enable `extension=pcov.so` in the CLI PHP configuration, then verify it:

```bash
php -r "var_dump(extension_loaded('pcov'));"
```

The verification command must print `bool(true)` before running coverage.

## Test isolation

`tests/Pest.php` creates a unique temporary mock Laravel project for every test and removes it
afterward. Tests must not share generated files or depend on execution order. Build paths with
`DIRECTORY_SEPARATOR` so the suite remains portable.

Feature tests use real filesystem operations. Generated framework classes are loaded against
test-only stand-ins where reflection is needed; this does not claim real Laravel runtime
integration.

## Static analysis

PHPStan runs at level 6 over `src/` and `bin/pulsar` with no baseline. PHPStan-only annotations
document consistent custom-exception constructors and the value types accepted by shared
generator array parameters. New findings fail the build.

## Continuous integration

The real workflow is [`.github/workflows/ci.yml`](.github/workflows/ci.yml). It runs on pushes
and pull requests with PHP 8.2, 8.3, and 8.4. Every matrix job installs PCOV and runs:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress
vendor/bin/pest --coverage --min=85
```

The Laravel 12/13 Testbench integration matrix is intentionally not present; it belongs to
PRD 6.

## Adding or changing generators

For each generator:

1. Add or update its dedicated feature test.
2. Assert the exact relative path, namespace, declaration, applicable public methods, valid PHP,
   and absence of `{{placeholder}}` remnants.
3. Cover duplicate output and missing parent service where applicable.
4. Add or update the corresponding `CommandTester` case, including success and failure exit
   codes.
5. Run Pint, PHPStan, the full Pest suite, and coverage before handing off the change.
