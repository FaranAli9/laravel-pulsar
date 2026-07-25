# CLAUDE.md

Guidance for Claude Code when working on the Pulsar package.

## What is Pulsar?

Pulsar is a Laravel code generation tool that scaffolds service-oriented applications with vertical slice architecture under `app/Pulsar`. It generates files for a Service Layer (HTTP, CLI, queue, and scheduler delivery scoped by consumer audience), a Domain Layer (shared business logic and Contracts), and an Infrastructure Layer (concrete outbound adapters).

## Architecture Invariants

- Controllers call UseCases only.
- Every inbound adapter (HTTP, CLI, queue, scheduler, event) is thin: validate, authorize,
  establish context, and call at most one UseCase (or one Query for a read).
- Jobs and Commands live in audience-scoped Service modules; Listeners live in Domain.
- Adapters own no transactions or branching business logic; UseCases own transactions.
- Operations are called by UseCases only (never by Controllers).
- Multiple UseCases may reuse the same Operation.
- Operations are reusable workflow fragments; conditional branching is allowed.
- Operations never start transactions and never emit events.

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
./bin/pulsar install
./bin/pulsar make:service Admin
./bin/pulsar make:controller ProductController Products Admin
./bin/pulsar make:use-case CreateProduct Products Admin
./bin/pulsar make:job ProcessOrder Orders Internal
./bin/pulsar make:command ReconcileLedger Billing Internal --signature=billing:reconcile
./bin/pulsar make:listener SendReceipt Billing --event=OrderPaid --queued
./bin/pulsar make:action CreateOrder Order
./bin/pulsar publish:context
./bin/pulsar publish:skill
./bin/pulsar ping
```

### Pulsar Command Reference

| Command | Arguments and options |
|---------|-----------------------|
| `install` | `[--dry-run] [--force]` |
| `make:service` | `{name}` |
| `make:controller` | `{name} {module} {service} [--resource]` |
| `make:request` | `{name} {module} {service}` |
| `make:resource` | `{name} {module} {service} [--collection]` |
| `make:use-case` | `{name} {module} {service}` |
| `make:operation` | `{name} {module} {service}` |
| `make:job` | `{name} {module} {service}` |
| `make:command` | `{name} {module} {service} [--signature={signature}]` |
| `make:domain` | `{name}` |
| `make:contract` | `{name} {domain}` |
| `make:model` | `{name} {domain}` |
| `make:action` | `{name} {domain}` |
| `make:dto` | `{name} {domain}` |
| `make:policy` | `{name} {domain} [--model={model}]` |
| `make:event` | `{name} {domain}` |
| `make:listener` | `{name} {domain} [--event={event}] [--queued]` |
| `make:notification` | `{name} {domain}` |
| `make:mailable` | `{name} {domain}` |
| `make:enum` | `{name} {domain}` |
| `make:value-object` | `{name} {domain}` |
| `make:exception` | `{name} {domain}` |
| `make:query` | `{name} {domain}` |
| `make:adapter` | `{name} {area} [--contract={FQCN\|name}] [--domain={domain}]` |
| `publish:context` | `[--force] [--path={path}]` |
| `publish:skill` | `[--force] [--path={path}]` |
| `ping` | — |

## Generated Application Structure

```text
app/Pulsar/
├── Services/{Service}/Modules/{Module}/
│   ├── Controllers/
│   ├── Requests/
│   ├── Resources/
│   ├── UseCases/
│   ├── Operations/
│   ├── Jobs/
│   └── Commands/
├── Domain/{Domain}/
│   ├── Contracts/
│   ├── Models/
│   ├── Actions/
│   ├── Queries/
│   ├── DTOs/
│   ├── ValueObjects/
│   ├── Enums/
│   ├── Events/
│   ├── Listeners/
│   ├── Notifications/
│   ├── Mail/
│   ├── Policies/
│   └── Exceptions/
└── Infrastructure/{Area}/
    └── {Adapter}.php
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
protected function findPulsarRootPath(): string
protected function findPulsarRootNamespace(): string
protected function findServicesRootPath(): string
protected function findDomainRootPath(): string
protected function findInfrastructureRootPath(): string
protected function findServiceNamespace(string $service): string
protected function findDomainNamespace(string $domain): string
protected function findInfrastructureNamespace(string $area): string
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
expect($content)->toHaveNamespace('App\Pulsar\Services\Auth\Modules\Orders\Operations');
expect($content)->toHaveClass('CreateOrderOperation');
expect($content)->toHaveMethod('execute');
```

When modifying generators, test:
1. Success case: Creates file, shows relative path
2. Duplicate file: Errors with "already exists"
3. Non-existent service: Errors with "does not exist"
4. Generated file: Valid PHP syntax, correct namespace, no `{{placeholder}}` remnants
