# CLAUDE.md

Guidance for Claude Code when working on the Pulsar package.

## What is Pulsar?

Pulsar is a Laravel code generation tool that scaffolds service-oriented applications with vertical slice architecture. It generates files for a Service Layer (HTTP delivery, scoped by consumer audience) and a Domain Layer (shared business logic).

## Common Commands

```bash
# Run all tests
composer test
# or
vendor/bin/pest

# Run tests with coverage (minimum 85%)
composer test:coverage

# Run specific test file
vendor/bin/pest tests/Unit/InputValidationTest.php

# Run tests matching pattern
vendor/bin/pest --filter=validateName

# Run tests in parallel
vendor/bin/pest --parallel

# Execute Pulsar commands (from package directory)
./bin/pulsar make:service Admin
./bin/pulsar make:controller ProductController Products Admin
./bin/pulsar make:action CreateOrder Order
```

## Codebase Structure

```
src/
├── Commands/           # CLI commands (thin wrappers)
│   ├── PulsarCommand.php        # Base command class
│   └── Make{Name}Command.php   # One per generator
├── Generators/         # All file generation logic
│   ├── Generator.php           # Base class with shared utilities
│   └── {Name}Generator.php     # One per file type
├── Exceptions/         # Custom exception hierarchy
├── Traits/
│   └── Finder.php              # Path discovery utilities
└── stubs/              # Template files with {{placeholder}} syntax
```

## Core Design Pattern: Command → Generator → Stub

**Strict separation of concerns:**
- **Commands** (`src/Commands/`): Orchestrate user interaction only. Retrieve input, call generator, display output. No file operations.
- **Generators** (`src/Generators/`): All heavy lifting — file creation, path resolution, content generation, validation.
- **Stubs** (`src/stubs/`): Template files with `{{placeholder}}` syntax (e.g., `{{namespace}}`, `{{name}}`).

## Adding a New Generator

1. Create stub in `src/stubs/{name}.stub` with placeholders
2. Create generator in `src/Generators/{Name}Generator.php` extending `Generator`
3. Create command in `src/Commands/Make{Name}Command.php` extending `PulsarCommand`
4. Register command in `bin/pulsar`

Use `OperationGenerator.php` and `MakeOperationCommand.php` as canonical examples.

## Key Methods

From `Generator.php`:
```php
protected function createDirectory(string $path, int $mode = 0755, bool $recursive = true): void
protected function createFile(string $path, string $contents): void
protected function fileExists(string $path): bool
protected function loadStub(string $stubPath): string
protected function replaceStubPlaceholders(string $stub, array $replacements): string
protected function generateSlug(string $name): string
protected function validateName(string $name): void  // Security-critical
```

From `Finder` trait:
```php
protected function findServicesRootPath(): string
protected function findServiceNamespace(string $service): string
protected function findLaravelRoot(): string
protected function serviceExists(string $service): bool
```

## Path Building

Always use `DIRECTORY_SEPARATOR`, never hardcoded slashes:
```php
$path = $dir . DIRECTORY_SEPARATOR . $file;  // Correct
$path = $dir . '/' . $file;                   // Wrong
```

## Testing

Tests use Pest PHP. Critical test files:
- `InputValidationTest.php`: Reserved PHP keywords, path traversal, invalid characters
- `OperationGeneratorTest.php`: End-to-end generation workflow
- `GeneratorTest.php`: Base class shared methods
- `ExceptionsTest.php`: Custom exception hierarchy

Custom Pest expectations:
```php
expect($content)->toBeValidPhp();
expect($content)->toHaveNamespace('App\Services\Auth\Operations');
expect($content)->toHaveClass('CreateOrderOperation');
expect($content)->toHaveMethod('handle');
```

When modifying generators, test:
1. Success case: Creates file, shows relative path
2. Duplicate file: Errors with "already exists"
3. Non-existent service: Errors with "does not exist"
4. Generated file: Valid PHP syntax, correct namespace, no `{{placeholder}}` remnants
