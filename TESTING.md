# Testing Pulsar

Pulsar's unit/feature tier tests generated files in isolated mock Laravel project directories.
It checks filesystem placement, namespaces, declarations, public methods, CLI argument wiring,
command output, exit codes, PHP syntax, and safe bootstrap patching. A separate
Orchestra Testbench tier boots a real Laravel fixture through its generated
`bootstrap/app.php`.

## Current measured state

The latest local run on July 25, 2026 produced:

```text
Unit/feature: 514 passed (1,554 assertions)
Integration:  6 passed (18 assertions) on each Laravel major
Coverage:     95.2%
```

The measured line-coverage figure is **95.2%**. The enforced minimum remains **85%**.

Every generator has dedicated feature coverage. Every `Make*Command` is exercised through
Symfony's `CommandTester`; `InstallCommand` additionally covers dry-run and safe manual fallback.
Those tests assert successful and failed exit codes, output, generated paths, input validation,
and option/argument mapping.

Generated PHP is checked with the `toBeValidPhp`, `toHaveNamespace`, and `toHaveClass`
expectations. Reflection-based assertions lock compatible generated method names, including
`Operation::execute()` and `Action::execute()` / `Query::execute()` / `UseCase::execute()`.

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

Run the real-Laravel tier:

```bash
composer test:integration
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
real Illuminate classes now that Testbench is a development dependency. The guarded aliases in
`tests/Pest.php` remain only as a fallback when those classes are unavailable.

## Real Laravel integration

`tests/Integration/fixture` is a minimal Laravel application with an installed
`PulsarServiceProvider` and patched bootstrap chain. Testbench boots that file and proves:

- policy resolution through `Gate::guessPolicyNamesUsing`;
- listener and command discovery from the Pulsar paths;
- regular, contextual, and scoped container bindings;
- queued Job dispatch plus repeat handling (the at-least-once reality);
- rollback suppression and after-commit dispatch for `ShouldDispatchAfterCommit`.

The same six tests run locally on Laravel 12/Testbench 10 and Laravel 13/Testbench 11.

## Static analysis

PHPStan runs at level 6 over `src/` and `bin/pulsar` with no baseline. PHPStan-only annotations
document consistent custom-exception constructors and the value types accepted by shared
generator array parameters. New findings fail the build.

## Continuous integration

The real workflow is [`.github/workflows/ci.yml`](.github/workflows/ci.yml). Its unit/feature
job runs on PHP 8.3, 8.4, and 8.5. The lockfile is resolved against PHP 8.3, so every job
installs dependencies supported by Pulsar's minimum runtime. Every unit matrix job installs
PCOV and runs:

```bash
php bin/pulsar --version
php bin/pulsar ping
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress
vendor/bin/pest --configuration=phpunit.xml.dist --testsuite=Pulsar --coverage --min=85
```

The distinct integration job pins Testbench 10 for Laravel 12 on PHP 8.3/8.4 and Testbench 11
for Laravel 13 on PHP 8.3/8.4/8.5. It runs only the `Integration` testsuite, keeping framework
compatibility separate from the unit coverage matrix. The committed `phpunit.xml.dist` defines
both suites explicitly.

## Adding or changing generators

For each generator:

1. Add or update its dedicated feature test.
2. Assert the exact relative path, namespace, declaration, applicable public methods, valid PHP,
   and absence of `{{placeholder}}` remnants.
3. Cover duplicate output and missing parent service where applicable.
4. Add or update the corresponding `CommandTester` case, including success and failure exit
   codes.
5. Run Pint, PHPStan, the full Pest suite, and coverage before handing off the change.
