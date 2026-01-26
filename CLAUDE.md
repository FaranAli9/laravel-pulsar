# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What is Pulse?

Pulse is a Laravel code generation tool for building service-oriented applications with vertical slice architecture. It generates scaffolding for both a Service Layer (HTTP/application orchestration) and a Domain Layer (pure business logic).

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

# Execute Pulse commands (from package directory)
./bin/pulse make:service Authentication
./bin/pulse make:controller ProductController Product Catalog
./bin/pulse make:action CreateOrder Order
```

## Architecture

### Codebase Structure

```
src/
├── Commands/           # CLI commands (thin wrappers)
│   ├── PulseCommand.php        # Base command class
│   └── Make{Name}Command.php   # One per generator
├── Generators/         # All file generation logic
│   ├── Generator.php           # Base class with shared utilities
│   └── {Name}Generator.php     # One per file type
├── Exceptions/         # Custom exception hierarchy
├── Traits/
│   └── Finder.php              # Path discovery utilities
└── stubs/              # Template files with {{placeholder}} syntax
```

### Core Design Pattern: Command → Generator → Stub

**Strict separation of concerns:**
- **Commands** (`src/Commands/`): Orchestrate user interaction only. Retrieve input, call generator, display output. No file operations.
- **Generators** (`src/Generators/`): All heavy lifting—file creation, path resolution, content generation, validation.
- **Stubs** (`src/stubs/`): Template files with `{{placeholder}}` syntax (e.g., `{{namespace}}`, `{{name}}`).

### Generated Application Architecture

Pulse generates code following vertical slice architecture with two layers:

**Service Layer** (HTTP/application):
```
app/Services/{Service}/
├── Providers/
│   ├── {Service}ServiceProvider.php
│   └── RouteServiceProvider.php
├── Routes/api.php
└── Modules/{Module}/
    ├── Controllers/    # HTTP handlers (thin)
    ├── Requests/       # Input validation
    ├── UseCases/       # Workflow orchestration
    └── Operations/     # Infrastructure (email, cache, APIs)
```

**Domain Layer** (business logic):
```
app/Domain/{Domain}/
├── Models/         # Eloquent models
├── Actions/        # Atomic business operations
├── DTOs/           # Data transfer objects
├── Policies/       # Authorization
├── Events/         # Domain events
├── Enums/          # Business states
├── Exceptions/     # Business rule violations
└── Queries/        # Complex read operations
```

## Key Patterns

### Generator Base Class Methods

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

### Adding a New Generator

1. Create stub in `src/stubs/{name}.stub` with placeholders
2. Create generator in `src/Generators/{Name}Generator.php` extending `Generator`
3. Create command in `src/Commands/Make{Name}Command.php` extending `PulseCommand`
4. Register command in `bin/pulse`

Use `OperationGenerator.php` and `MakeOperationCommand.php` as canonical examples.

### Path Building

Always use `DIRECTORY_SEPARATOR`, never hardcoded slashes:
```php
$path = $dir . DIRECTORY_SEPARATOR . $file;  // Correct
$path = $dir . '/' . $file;                   // Wrong
```

## Layer Responsibilities (Generated Code)

| Layer | Responsibility | Never |
|-------|---------------|-------|
| Controller | Extract validated data, call UseCase, return response | Contain business logic |
| Request | Validate input structure, check authorization | Business rule validation |
| UseCase | Orchestrate workflows, own transactions, emit events | Couple to HTTP Request objects |
| Action | Atomic business operation, validate business rules | Call other UseCases |
| Operation | Infrastructure (email, APIs, cache, files) | Contain business logic |

### Dependency Rules for Generated Code

- Domain layer has ZERO dependencies on Service layer
- Services communicate via events, not direct coupling
- UseCases own database transaction boundaries
- Actions stay transaction-agnostic for composability

### DI Pattern (Octane-safe)

```php
// Constructor = Dependencies (resolved once)
public function __construct(
    private CreateOrderAction $createOrder,
) {}

// execute() = Data (per request)
public function execute(OrderData $data): Order
```

## Testing

Tests use Pest PHP. Critical areas with security implications:
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

## Validation Scenarios

When modifying generators, test:
1. Success case: Creates file, shows relative path
2. Duplicate file: Errors with "already exists"
3. Non-existent service: Errors with "does not exist"
4. Generated file: Valid PHP syntax, correct namespace, no `{{placeholder}}` remnants
