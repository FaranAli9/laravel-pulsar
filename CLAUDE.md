# CLAUDE.md

This file provides comprehensive guidance to Claude Code (claude.ai/code) when working with the Pulse codebase and understanding the architecture it generates.

## What is Pulsar?

Pulsar is a Laravel code generation tool for building service-oriented applications with vertical slice architecture. It generates scaffolding for both a Service Layer (HTTP/application orchestration) and a Domain Layer (pure business logic).

This guide covers:
1. **Pulsar Codebase**: How the tool itself is built (Commands, Generators, Stubs)
2. **Generated Architecture**: What Pulsar creates and the patterns applications should follow

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
./bin/pulsar make:service Authentication
./bin/pulsar make:controller ProductController Product Catalog
./bin/pulsar make:action CreateOrder Order
```

## Architecture

### Codebase Structure

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
3. Create command in `src/Commands/Make{Name}Command.php` extending `PulsarCommand`
4. Register command in `bin/pulsar`

Use `OperationGenerator.php` and `MakeOperationCommand.php` as canonical examples.

### Path Building

Always use `DIRECTORY_SEPARATOR`, never hardcoded slashes:
```php
$path = $dir . DIRECTORY_SEPARATOR . $file;  // Correct
$path = $dir . '/' . $file;                   // Wrong
```

## Critical Rules for Generated Code

### 1. Layer Responsibilities

**Request Flow:**
```
HTTP Request → Controller → UseCase → Actions/Operations → Models → Database
                    ↓
            HTTP Response
```

| Layer | Responsibility | What NOT to do |
|-------|---------------|----------------|
| **Controller** | Extract validated data, call UseCase, return response | Never contain business logic |
| **Request** | Validate input structure, check authorization | No business rule validation |
| **UseCase** | Orchestrate workflows, own transactions, emit events | Never coupled to HTTP Request objects |
| **Action** | Atomic business operation, validate business rules | Don't call other UseCases |
| **Operation** | Infrastructure (email, APIs, cache, files) | Don't contain business logic |
| **Model** | Data persistence, relationships | Keep thin, no business logic |

### 2. Dependency Injection Pattern (Octane-safe)

**✅ ALWAYS: Constructor = Dependencies, execute() = Data**

```php
class PlaceOrderUseCase
{
    // Dependencies injected (Laravel resolves once in Octane)
    public function __construct(
        private CreateOrderAction $createOrder,
        private EmailService $emailService,
    ) {}

    // Data passed per request
    public function execute(OrderData $data): Order
    {
        // Implementation
    }
}
```

**❌ NEVER: Data in Constructor**

```php
// Breaks Laravel Octane - singletons can't hold state
public function __construct(private OrderData $data) {}
```

### 3. Transaction Boundaries

**UseCases own transactions** (they see the full workflow):

```php
class PlaceOrderUseCase
{
    public function execute(OrderData $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = $this->createOrder->execute($data);
            $this->updateInventory->execute($data->items);
            $this->recordPayment->execute($order);

            event(new OrderPlaced($order));
            return $order;
        });
    }
}
```

**Actions don't manage transactions** (stay composable):

```php
class CreateOrderAction
{
    // No DB::transaction here
    public function execute(OrderData $data): Order
    {
        return Order::create([...]);
    }
}
```

### 4. DTOs Over Arrays

**✅ Type-safe with DTOs:**

```php
readonly class OrderData
{
    public function __construct(
        public int $customerId,
        public array $items,
        public string $shippingAddress,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            customerId: $data['customer_id'],
            items: $data['items'],
            shippingAddress: $data['shipping_address'],
        );
    }
}

// Usage in Controller
$order = $this->placeOrder->execute(
    OrderData::from($request->validated())
);
```

**❌ Weak typing:**

```php
$order = $this->placeOrder->execute($request->validated());
```

### 5. Event-Driven Side Effects

**✅ Decouple side effects with events:**

```php
class PlaceOrderUseCase
{
    public function execute(OrderData $data): Order
    {
        $order = DB::transaction(function () use ($data) {
            $order = $this->createOrder->execute($data);
            $this->updateInventory->execute($data->items);
            return $order;
        });

        // Side effects handled by listeners (async/queued)
        event(new OrderPlaced($order));

        return $order;
    }
}
```

**❌ Direct coupling:**

```php
// Don't do this in UseCase
$this->emailService->sendOrderConfirmation($order);
$this->smsService->sendNotification($order);
```

### 6. Service Isolation

**✅ Event-based communication:**

```php
// Order service emits
event(new OrderPlaced($order));

// Inventory service listens
class DecrementStockOnOrder
{
    public function handle(OrderPlaced $event): void
    {
        // React independently
    }
}
```

**❌ Direct service coupling:**

```php
app(InventoryService::class)->decrementStock($items);
```

### 7. Naming Conventions

Suffixes are **optional** but recommended for clarity in generated code:

- Controllers: `OrderController` or just `Order`
- Requests: `PlaceOrderRequest` or just `PlaceOrder`
- UseCases: `PlaceOrderUseCase` or just `PlaceOrder`
- Actions: `CreateOrderAction` or just `CreateOrder`
- Operations: `SendEmailOperation` or just `SendEmail`
- DTOs: `OrderData` or just `Order`
- Policies: `OrderPolicy` or just `Order`

**Context determines naming.** Use suffixes when disambiguation helps.

### Dependency Rules Summary

```
┌─────────────────────────────────────┐
│         Service Layer               │
│  (Controllers, UseCases, Requests)  │
│              ↓ depends on           │
│         Domain Layer                │
│   (Models, Actions, DTOs, Events)   │
└─────────────────────────────────────┘

✅ Service → Domain (allowed)
❌ Domain → Service (forbidden)
✅ Service A → Domain → Service B (via events)
❌ Service A → Service B (direct coupling forbidden)
```

## Code Templates for Generated Files

### Controller (Thin HTTP Handler)

```php
class OrderController extends Controller
{
    public function __construct(
        private PlaceOrderUseCase $placeOrder
    ) {}

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $order = $this->placeOrder->execute(
            OrderData::from($request->validated())
        );

        return response()->json($order, 201);
    }
}
```

### Request (Validation Only)

```php
class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Order::class);
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }
}
```

### UseCase (Workflow Orchestrator)

```php
class PlaceOrderUseCase
{
    public function __construct(
        private CreateOrderAction $createOrder,
        private UpdateStockAction $updateStock,
        private ReserveInventoryAction $reserveInventory,
    ) {}

    public function execute(OrderData $data): Order
    {
        return DB::transaction(function () use ($data) {
            // 1. Reserve to prevent overselling
            $this->reserveInventory->execute($data->items);

            // 2. Create order
            $order = $this->createOrder->execute($data);

            // 3. Decrement stock
            foreach ($data->items as $item) {
                $this->updateStock->execute(
                    $item['product_id'],
                    -$item['quantity']
                );
            }

            // 4. Emit event for side effects
            event(new OrderPlaced($order));

            return $order;
        });
    }
}
```

### Action (Atomic Business Operation)

```php
class UpdateStockAction
{
    public function execute(Product $product, int $quantity): Product
    {
        // Business rule validation
        if ($product->stock + $quantity < 0) {
            throw new InsufficientStockException($product);
        }

        // State mutation
        $product->update(['stock' => $product->stock + $quantity]);

        // Domain event
        if ($product->stock === 0) {
            event(new ProductOutOfStock($product));
        }

        return $product->fresh();
    }
}
```

### Operation (Infrastructure)

```php
class SendOrderConfirmationEmailOperation
{
    public function __construct(
        private Mailer $mailer,
        private LoggerInterface $logger,
    ) {}

    public function execute(Order $order): void
    {
        try {
            $this->mailer->send(
                new OrderConfirmationMail($order),
                $order->customer->email
            );

            $this->logger->info("Order confirmation sent", [
                'order_id' => $order->id,
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to send confirmation", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

### DTO (Data Transfer Object)

```php
readonly class OrderData
{
    public function __construct(
        public int $customerId,
        public array $items,
        public string $shippingAddress,
        public ?string $notes = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            customerId: $data['customer_id'],
            items: $data['items'],
            shippingAddress: $data['shipping_address'],
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'items' => $this->items,
            'shipping_address' => $this->shippingAddress,
            'notes' => $this->notes,
        ];
    }
}
```

### Query (Complex Read Operations)

```php
class GetLowStockProductsQuery
{
    public function execute(int $threshold = 10): Collection
    {
        return Product::query()
            ->where('stock', '<=', $threshold)
            ->where('status', ProductStatus::ACTIVE)
            ->with('category')
            ->orderBy('stock', 'asc')
            ->get();
    }
}
```

Use Queries for:
- Complex database reads
- Reports and analytics
- Multi-table joins
- Filtered collections

## Common Patterns

### UseCase vs Operation

| Aspect | UseCase | Operation |
|--------|---------|-----------|
| Purpose | Business workflow (WHAT) | Infrastructure task (HOW) |
| Location | Service Layer | Service Layer |
| Example | `PlaceOrderUseCase` | `SendEmailOperation` |
| Calls | Actions, Operations, Queries | External services, APIs |
| Returns | Domain objects | void, primitives, DTOs |
| Transactions | Owns boundaries | Never manages |

### Action Return Types

Actions can return:
- **Domain objects**: `CreateOrderAction` → `Order`
- **Collections**: `BulkUpdateProductsAction` → `Collection<Product>`
- **Booleans**: `ActivateAccountAction` → `bool`
- **Void**: Operations often return void
- **Primitives**: `CalculateTaxAction` → `float`

### When in Doubt

1. **Business logic?** → Action
2. **Workflow orchestration?** → UseCase
3. **HTTP handling?** → Controller
4. **Input validation?** → Request
5. **Infrastructure (email, API)?** → Operation
6. **Complex read?** → Query
7. **Data transfer?** → DTO
8. **Business state?** → Enum
9. **Business rule violation?** → Exception
10. **State change notification?** → Event

## Testing

### Testing the Pulsar Codebase

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

When modifying generators, test:
1. Success case: Creates file, shows relative path
2. Duplicate file: Errors with "already exists"
3. Non-existent service: Errors with "does not exist"
4. Generated file: Valid PHP syntax, correct namespace, no `{{placeholder}}` remnants

### Testing Generated Applications

When writing tests for applications built with Pulse:

#### Unit Test (Action)

```php
test('updates product stock', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $action = new UpdateStockAction();

    $result = $action->execute($product, -3);

    expect($result->stock)->toBe(7);
});
```

#### Integration Test (UseCase)

```php
test('places order successfully', function () {
    Event::fake();
    $useCase = app(PlaceOrderUseCase::class);
    $data = OrderData::from([
        'customer_id' => 1,
        'items' => [['product_id' => 1, 'quantity' => 2]],
        'shipping_address' => '123 Main St',
    ]);

    $order = $useCase->execute($data);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->status)->toBe(OrderStatus::PENDING);

    Event::assertDispatched(OrderPlaced::class);
});
```

#### Feature Test (Controller)

```php
test('creates order via API', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/orders', [
            'customer_id' => 1,
            'items' => [['product_id' => 1, 'quantity' => 2]],
            'shipping_address' => '123 Main St',
        ]);

    $response->assertCreated()
        ->assertJsonStructure(['id', 'total', 'status']);
});
```

## When Generating Code

### Creating New Features

When Claude generates new features using Pulse:

1. **Start with the UseCase** - defines the workflow
2. **Identify required Actions** - atomic operations needed
3. **Create supporting infrastructure** - DTOs, Events, Exceptions
4. **Wire up HTTP layer** - Controller, Request
5. **Add tests** - unit (Actions), integration (UseCases), feature (Controllers)

### Example: "Add product review feature"

```bash
# 1. Domain layer
pulsar make:action CreateReviewAction Product
pulsar make:dto ReviewData Product
pulsar make:event ReviewCreated Product
pulsar make:policy ReviewPolicy Product

# 2. Service layer
pulsar make:use-case CreateReviewUseCase Review Catalog
pulsar make:controller ReviewController Review Catalog
pulsar make:request CreateReviewRequest Review Catalog
```

### File Organization Rules

When generating code, ensure:
- **One class per file**
- **Service modules** group by feature (Cart, Payment, Order)
- **Domain folders** group by aggregate (Product, Customer, Order)
- **No cross-service imports** - use events for communication
- **Domain never imports Service** - dependency flows one way

## Quick Reference

### Pulsar Generator Commands

```bash
# Service layer
pulsar make:service Sales
pulsar make:controller OrderController Order Sales
pulsar make:request PlaceOrderRequest Order Sales
pulsar make:use-case PlaceOrderUseCase Order Sales
pulsar make:operation SendEmailOperation Order Sales

# Domain layer
pulsar make:model Order Sales
pulsar make:action CreateOrderAction Sales
pulsar make:dto OrderData Sales
pulsar make:policy OrderPolicy Sales
pulsar make:event OrderPlaced Sales
pulsar make:enum OrderStatus Sales
pulsar make:exception InvalidOrderException Sales
pulsar make:query GetActiveOrdersQuery Sales
```

### Path Resolution

When generating code, files are placed at:

- Controllers → `app/Services/{Service}/Modules/{Module}/Controllers/`
- Requests → `app/Services/{Service}/Modules/{Module}/Requests/`
- UseCases → `app/Services/{Service}/Modules/{Module}/UseCases/`
- Operations → `app/Services/{Service}/Modules/{Module}/Operations/`
- Models → `app/Domain/{Domain}/Models/`
- Actions → `app/Domain/{Domain}/Actions/`
- DTOs → `app/Domain/{Domain}/DTOs/`
- Policies → `app/Domain/{Domain}/Policies/`
- Events → `app/Domain/{Domain}/Events/`
- Enums → `app/Domain/{Domain}/Enums/`
- Exceptions → `app/Domain/{Domain}/Exceptions/`
- Queries → `app/Domain/{Domain}/Queries/`

---

**For full documentation, see:** https://github.com/faran/pulsar
