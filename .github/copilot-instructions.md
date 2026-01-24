# Copilot Instructions for Pulse

Pulse is a Laravel code generation tool using **vertical slice architecture**. This guide helps AI agents work effectively in this codebase.

## Architecture Overview

**Vertical Slice Architecture**: Code is organized by business capability (Service → Module → Features), not by technical layer. Each service is autonomous with its own controllers, requests, and business logic co-located.

```
app/Services/{Service}/
├── Providers/
│   ├── {Service}ServiceProvider.php
│   └── RouteServiceProvider.php
├── Routes/
│   └── api.php
└── Modules/{Module}/
    ├── Controllers/
    ├── Requests/
    ├── UseCases/
    └── Operations/
```

## Core Design Pattern: Orchestration + Generation

Pulse strictly separates concerns:

- **Commands** (`src/Commands/*.php`): Orchestrate user interaction only. Thin wrappers that retrieve input, call generator, display output.
- **Generators** (`src/Generators/*.php`): All heavy lifting—file creation, path resolution, content generation, validation.
- **Stubs** (`src/stubs/*.stub`): Template files with `{{placeholder}}` syntax.

**Critical Rule**: Never put file operations or path building in Commands. Always delegate to Generators.

## Generator Base Class Methods

From `Generator.php`:

```php
protected function createDirectory(string $path, int $mode = 0755, bool $recursive = true): void
protected function createFile(string $path, string $contents): void
protected function fileExists(string $path): bool
protected function loadStub(string $stubPath): string
protected function replaceStubPlaceholders(string $stub, array $replacements): string
protected function generateSlug(string $name): string
```

From `Finder` trait:

```php
protected function findServicesRootPath(): string
protected function findServiceNamespace(string $service): string
protected function findLaravelRoot(): string
protected function serviceExists(string $service): bool
```

## Generator Pattern Template

See [src/Generators/OperationGenerator.php](src/Generators/OperationGenerator.php) as the canonical example:

```php
class {Name}Generator extends Generator
{
    protected string $name;
    protected string $module;
    protected string $service;

    public function __construct(string $name, string $module, string $service)
    {
        $this->name = $this->ensure{Name}Suffix($name);
        $this->module = $module;
        $this->service = $service;
    }

    public function generate(): string
    {
        $this->validateServiceExists();
        $this->createModuleDirectories();

        $filePath = $this->get{Name}Path();

        if ($this->fileExists($filePath)) {
            throw new Exception("{Name} [{$this->name}] already exists in {$this->service}/{$this->module}!");
        }

        $content = $this->get{Name}Content();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    private function ensure{Name}Suffix(string $name): string
    {
        return str_ends_with($name, '{Name}') ? $name : $name . '{Name}';
    }
}
```

## Command Pattern Template

See [src/Commands/MakeOperationCommand.php](src/Commands/MakeOperationCommand.php) as the canonical example:

```php
#[AsCommand(
    name: 'make:{name}',
    description: 'Create a new {name} class',
)]
class Make{Name}Command extends PulseCommand
{
    public function handle(): int
    {
        $name = $this->argument('name');
        $module = $this->argument('module');
        $service = $this->argument('service');

        try {
            $generator = new {Name}Generator($name, $module, $service);
            $filePath = $generator->generate();

            // STRICT OUTPUT FORMAT - do not deviate
            $this->line();
            $this->success("{Name} created successfully");
            $this->line();
            $this->info("Location: {$filePath}");
            $this->line();

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->line();
            $this->error($e->getMessage());
            $this->line();

            return Command::FAILURE;
        }
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the {name}');
        $this->addArgument('module', InputArgument::REQUIRED, 'The name of the module');
        $this->addArgument('service', InputArgument::REQUIRED, 'The name of the service');
    }
}
```

## Adding a New Command: Checklist

1. **Create stub** in `src/stubs/{name}.stub` with `{{namespace}}`, `{{name}}` placeholders
2. **Create generator** in `src/Generators/{Name}Generator.php` extending `Generator`
3. **Create command** in `src/Commands/Make{Name}Command.php` extending `PulseCommand`
4. **Register command** in `bin/pulse`: `$app->addCommand(new Make{Name}Command());`
5. **Test all scenarios** (see Testing section below)

## Key Conventions

| Type       | Pattern                   | Example                        |
| ---------- | ------------------------- | ------------------------------ |
| Commands   | `Make{Name}Command`       | `MakeOperationCommand`         |
| Generators | `{Name}Generator`         | `OperationGenerator`           |
| Stubs      | `{name}.stub` (lowercase) | `operation.stub`               |
| Exceptions | `{Name}Exception`         | `ServiceDoesNotExistException` |

**Path Building**: Always use `DIRECTORY_SEPARATOR`, never `/` or `\`

```php
✅ $path = $dir . DIRECTORY_SEPARATOR . $file;
❌ $path = $dir . '/' . $file;
```

**Suffix Enforcement**: Auto-append class suffixes if missing (e.g., `Operation`, `Controller`, `Request`)

## Testing & Validation

Test these scenarios before considering work complete:

```bash
# 1. Success case
pulse make:{name} Test{Name} TestModule TestService
# → Creates file, shows success message with relative path

# 2. Duplicate file
pulse make:{name} Test{Name} TestModule TestService  # run twice
# → Should error: "{Name} [Test{Name}] already exists..."

# 3. Non-existent service
pulse make:{name} Test{Name} TestModule FakeService
# → Should error: "Service [FakeService] does not exist!"

# 4. Suffix handling
pulse make:{name} Test TestModule TestService
# → Should create Test{Name}.php (suffix auto-added)
```

**Verify generated file**:

- Valid PHP syntax
- Correct namespace: `App\Services\{Service}\Modules\{Module}\{Name}s`
- No `{{placeholder}}` remnants

## Troubleshooting

| Issue                     | Cause                   | Solution                                                                            |
| ------------------------- | ----------------------- | ----------------------------------------------------------------------------------- |
| "Stub file not found"     | Wrong path construction | Use `__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'stubs'`          |
| Placeholders not replaced | Key mismatch            | Stub uses `{{namespace}}`, code must use key `'namespace'`                          |
| Wrong namespace           | Wrong Finder method     | Use `findServiceNamespace()` for service-scoped, `findRootNamespace()` for app root |

## Running Commands

```bash
# From Laravel project with Pulse installed
vendor/bin/pulse make:operation CreateOrder Sales Order

# From Pulse package directory (development)
./bin/pulse make:operation CreateOrder Sales Order
```

## Reference Files

- **Command Template**: [src/Commands/MakeOperationCommand.php](src/Commands/MakeOperationCommand.php)
- **Generator Template**: [src/Generators/OperationGenerator.php](src/Generators/OperationGenerator.php)
- **Base Generator**: [src/Generators/Generator.php](src/Generators/Generator.php)
- **Base Command**: [src/Commands/PulseCommand.php](src/Commands/PulseCommand.php)
- **Path Utilities**: [src/Traits/Finder.php](src/Traits/Finder.php)
- **Comprehensive Guide**: [AI_ONBOARDING.md](AI_ONBOARDING.md)
