# Pulsar

> A modern Laravel code generation tool for building service-oriented applications with vertical slice architecture.

## Table of Contents

- [Installation](#installation)
- [Architecture](#architecture)
    - [Service Layer](#service-layer)
    - [Domain Layer](#domain-layer)
- [File Types](#file-types)
    - [Service Layer Files](#service-layer-files)
    - [Domain Layer Files](#domain-layer-files)
- [Commands Reference](#commands-reference)
- [Complete Examples](#complete-examples)
    - [Service Layer Example](#service-layer-example)
    - [Domain Layer Example](#domain-layer-example)
- [Best Practices](#best-practices)
- [Contributing](#contributing)

## Installation

```bash
composer require faran/pulsar --dev
```

## Architecture

Pulse organizes code into two complementary layers:

### Service Layer

The Service Layer handles HTTP delivery and application orchestration using **vertical slice architecture**. Each Service represents an API delivery boundary scoped to a specific consumer audience (e.g., Admin, Client), not a business capability. Business logic lives in the shared Domain layer.

**Structure:**

```
app/Services/{Service}/
├── Providers/
│   ├── {Service}ServiceProvider.php    # Bootstraps the service
│   └── RouteServiceProvider.php        # Registers routes
├── Routes/
│   └── api.php                         # API routes (/api/{service-slug}/*)
└── Modules/{Module}/
    ├── Controllers/                    # HTTP request handlers
    ├── Requests/                       # Input validation
    ├── UseCases/                       # Application logic
    └── Operations/                     # Reusable action/query sequences
```

**Example:** Admin API Service

```
app/Services/Admin/
├── Providers/
├── Routes/api.php
└── Modules/
    ├── Orders/
    │   ├── Controllers/OrderController.php
    │   ├── Requests/UpdateOrderRequest.php
    │   └── UseCases/ManageOrder.php
    └── Products/
        ├── Controllers/ProductController.php
        ├── Requests/CreateProductRequest.php
        └── UseCases/CreateProduct.php
```

Routes: `/api/admin/orders`, `/api/admin/products`

The same Domain models (Order, Product) are used by both Admin and Client services — each service exposes different endpoints, validation, and response shapes for its audience.

### Domain Layer

The Domain Layer contains pure business logic independent of HTTP, frameworks, or infrastructure. Organized by business domain.

**Structure:**

```
app/Domain/{Domain}/
├── Models/                             # Eloquent models
├── Actions/                            # Business operations
├── DTOs/                               # Data transfer objects
├── Policies/                           # Authorization rules
├── Events/                             # Domain events
├── Enums/                              # Domain states
├── Exceptions/                         # Business rule violations
└── Queries/                            # Complex read operations
```

**Example:** E-commerce Product Domain

```
app/Domain/Product/
├── Models/Product.php
├── Actions/UpdateStockAction.php
├── DTOs/ProductData.php
├── Policies/ProductPolicy.php
├── Events/ProductOutOfStock.php
├── Enums/ProductStatus.php
├── Exceptions/InsufficientStockException.php
└── Queries/GetProductsByCategory.php
```

```bash
composer require faran/pulsar --dev
```

### Generate Your First Service

```bash
pulsar make:service Admin
```

Then follow the sections below to generate individual file types.

---

## File Types

> **💡 Naming Freedom:** Pulse gives you complete control over class names. Examples below use suffixes like `Controller`, `Action`, `UseCase` for clarity, but you can name classes however you prefer:
>
> - `pulsar make:controller ProductController ...` → `ProductController.php` ✅
> - `pulsar make:controller Product ...` → `Product.php` ✅
> - `pulsar make:action CreateOrderAction ...` → `CreateOrderAction.php` ✅
> - `pulsar make:action CreateOrder ...` → `CreateOrder.php` ✅
>
> The generated class name matches exactly what you specify.

### Service Layer Files

#### Controllers

**Purpose:** Handle HTTP requests and orchestrate application flow.

**Command:**

```bash
pulsar make:controller ProductController Products Admin
```

**Location:** `app/Services/{Service}/Modules/{Module}/Controllers/`

**Example:**

```php
class ProductController extends Controller
{
    public function __construct(
        private ListProductsUseCase $listProducts
    ) {}

    public function index(ListProductsRequest $request): JsonResponse
    {
        $products = $this->listProducts->execute($request->validated());
        return response()->json($products);
    }
}
```

---

#### Requests

**Purpose:** Validate input and authorize requests.

**Command:**

```bash
pulsar make:request AddToCartRequest Cart Client
```

**Location:** `app/Services/{Service}/Modules/{Module}/Requests/`

**Example:**

```php
class AddToCartRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ];
    }
}
```

---

#### UseCases

**Purpose:** Application-specific business logic coordinating domain operations.

**Command:**

```bash
pulsar make:use-case PlaceOrder Orders Client
```

**Location:** `app/Services/{Service}/Modules/{Module}/UseCases/`

**Example:**

```php
class PlaceOrderUseCase
{
    public function __construct(
        private CreateOrderAction $createOrder,
        private UpdateStockAction $updateStock,
    ) {}

    public function execute(OrderData $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = $this->createOrder->execute($data);

            foreach ($data->items as $item) {
                $this->updateStock->execute($item['product_id'], -$item['quantity']);
            }

            event(new OrderPlaced($order));

            return $order;
        });
    }
}
```

---

#### Operations

**Purpose:** Reusable sequences of Actions/Queries that can be shared across multiple UseCases.

**When multiple UseCases need the same sequence of Actions/Queries, extract it into an Operation.**

**Command:**

```bash
pulsar make:operation SaveAddress Account Client
```

**Location:** `app/Services/{Service}/Modules/{Module}/Operations/`

**Example:**

```php
class SaveAddressOperation
{
    public function __construct(
        private FormatAddressAction $formatAddress,
        private RemoveNullFieldsAction $removeNullFields,
        private SaveAddressAction $saveAddress,
    ) {}

    public function execute(array $addressData, User $user): Address
    {
        // 1. Format address (standardize formatting)
        $formatted = $this->formatAddress->execute($addressData);

        // 2. Remove null fields (clean data)
        $cleaned = $this->removeNullFields->execute($formatted);

        // 3. Save address
        return $this->saveAddress->execute($cleaned, $user);
    }
}
```

**When to use Operations:**

- Multiple UseCases perform the same sequence of Actions/Queries
- You need to reuse a multi-step process across different workflows
- A group of Actions/Queries always execute together

**When to use UseCases instead:**

- Single workflow specific to one feature (not reused elsewhere)
- Workflow owns transaction boundaries
- Workflow emits domain events

---

### Domain Layer Files

#### Models

**Purpose:** Eloquent models representing domain entities.

**Command:**

```bash
pulsar make:model Product Catalog
```

**Location:** `app/Domain/{Domain}/Models/`

**Example:**

```php
class Product extends Model
{
    protected $fillable = ['name', 'price', 'stock'];

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }
}
```

---

#### Actions

**Purpose:** Atomic business operations encapsulating domain logic.

**Command:**

```bash
pulsar make:action UpdateProductStock Catalog
```

**Location:** `app/Domain/{Domain}/Actions/`

**Example:**

```php
class UpdateProductStock
{
    public function execute(Product $product, int $quantity): Product
    {
        if ($product->stock + $quantity < 0) {
            throw new InsufficientStockException($product);
        }

        $product->update(['stock' => $product->stock + $quantity]);

        if ($product->stock === 0) {
            event(new ProductOutOfStock($product));
        }

        return $product->fresh();
    }
}
```

---

#### DTOs

**Purpose:** Immutable data carriers for transferring data between layers.

**Command:**

```bash
pulsar make:dto OrderData Order
```

**Location:** `app/Domain/{Domain}/DTOs/`

**Example:**

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
```

---

#### Policies

**Purpose:** Business authorization rules for domain entities.

**Command:**

```bash
pulsar make:policy OrderPolicy Order
```

**Location:** `app/Domain/{Domain}/Policies/`

**Example:**

```php
class OrderPolicy
{
    public function canCancel(User $user, Order $order): bool
    {
        return $order->status === OrderStatus::PENDING
            && $order->customer_id === $user->id;
    }
}
```

---

#### Events

**Purpose:** Domain events signaling significant business occurrences.

**Command:**

```bash
pulsar make:event OrderPlaced Order
```

**Location:** `app/Domain/{Domain}/Events/`

**Example:**

```php
class OrderPlaced
{
    public function __construct(
        public readonly Order $order,
        public readonly DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {}
}
```

---

#### Enums

**Purpose:** Fixed sets of domain values and states.

**Command:**

```bash
pulsar make:enum OrderStatus Order
```

**Location:** `app/Domain/{Domain}/Enums/`

**Example:**

```php
enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
```

---

#### Exceptions

**Purpose:** Domain-specific business rule violations.

**Command:**

```bash
pulsar make:exception InsufficientStockException Catalog
```

**Location:** `app/Domain/{Domain}/Exceptions/`

**Example:**

```php
class InsufficientStockException extends Exception
{
    public function __construct(Product $product)
    {
        parent::__construct("Product {$product->name} has insufficient stock");
    }
}
```

---

#### Queries

**Purpose:** Complex read-only domain queries.

**Command:**

```bash
pulsar make:query GetCustomerOrders Order
```

**Location:** `app/Domain/{Domain}/Queries/`

**Example:**

```php
class GetCustomerOrdersQuery
{
    public function execute(int $customerId): Collection
    {
        return Order::where('customer_id', $customerId)
            ->with('items.product')
            ->latest()
            ->get();
    }
}
```

---

---

## Complete Examples

### Service Layer Example

Building an Admin API service:

```bash
# 1. Create the service
pulsar make:service Admin

# 2. Create orders module (admin order management)
pulsar make:controller OrderController Orders Admin -r
pulsar make:request UpdateOrderRequest Orders Admin
pulsar make:use-case ManageOrder Orders Admin

# 3. Create products module (admin product management)
pulsar make:controller ProductController Products Admin -r
pulsar make:request CreateProductRequest Products Admin
pulsar make:use-case CreateProduct Products Admin
pulsar make:operation SyncInventory Products Admin
```

**Resulting structure:**

```
app/Services/Admin/
├── Providers/
│   ├── AdminServiceProvider.php
│   └── RouteServiceProvider.php
├── Routes/
│   └── api.php
└── Modules/
    ├── Orders/
    │   ├── Controllers/OrderController.php
    │   ├── Requests/UpdateOrderRequest.php
    │   └── UseCases/ManageOrder.php
    └── Products/
        ├── Controllers/ProductController.php
        ├── Requests/CreateProductRequest.php
        ├── UseCases/CreateProduct.php
        └── Operations/SyncInventory.php
```

**Define routes** in `app/Services/Admin/Routes/api.php`:

```php
use App\Services\Admin\Modules\Orders\Controllers\OrderController;
use App\Services\Admin\Modules\Products\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/orders', OrderController::class);
Route::apiResource('/products', ProductController::class);
```

Routes accessible at: `/api/admin/orders`, `/api/admin/products`

### Domain Layer Example

Building an e-commerce domain:

```bash
# 1. Create domain models
pulsar make:model Product Catalog
pulsar make:model Order Order

# 2. Create actions for business operations
pulsar make:action UpdateProductStock Catalog
pulsar make:action CreateOrder Order

# 3. Create DTOs for data transfer
pulsar make:dto ProductData Catalog
pulsar make:dto OrderData Order

# 4. Create policies for authorization
pulsar make:policy ProductPolicy Catalog
pulsar make:policy OrderPolicy Order

# 5. Create domain events
pulsar make:event ProductOutOfStock Catalog
pulsar make:event OrderPlaced Order

# 6. Create enums for states
pulsar make:enum ProductStatus Catalog
pulsar make:enum OrderStatus Order

# 7. Create domain exceptions
pulsar make:exception InsufficientStockException Catalog
pulsar make:exception OrderAlreadyCancelledException Order

# 8. Create queries for complex reads
pulsar make:query GetLowStockProducts Catalog
pulsar make:query GetCustomerOrders Order
```

**Resulting structure:**

```
app/Domain/
├── Catalog/
│   ├── Models/Product.php
│   ├── Actions/UpdateProductStock.php
│   ├── DTOs/ProductData.php
│   ├── Policies/ProductPolicy.php
│   ├── Events/ProductOutOfStock.php
│   ├── Enums/ProductStatus.php
│   ├── Exceptions/InsufficientStockException.php
│   └── Queries/GetLowStockProducts.php
└── Order/
    ├── Models/Order.php
    ├── Actions/CreateOrder.php
    ├── DTOs/OrderData.php
    ├── Policies/OrderPolicy.php
    ├── Events/OrderPlaced.php
    ├── Enums/OrderStatus.php
    ├── Exceptions/OrderAlreadyCancelledException.php
    └── Queries/GetCustomerOrders.php
```

---

## Architecture Best Practices

### Layer Responsibilities

```
Request → Controller → UseCase → Actions/Operations/Queries → Models
                ↓
            Response
```

#### **Controllers** (HTTP Layer)

- Extract validated data from Request
- Call UseCase with DTOs or validated arrays
- Transform domain results to HTTP responses
- **Never contain business logic**

```php
class OrderController extends Controller
{
    public function __construct(
        private PlaceOrder $placeOrder
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

#### **Requests** (Validation Layer)

- Validate input structure and format
- Authorization checks (via `authorize()` method)
- **No business logic** - only input validation
- Business rule validation belongs in Actions/UseCases

```php
class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Policy-based authorization
        return $this->user()->can('create', Order::class);
    }

    public function rules(): array
    {
        // Structure validation only
        return [
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }
}
```

#### **UseCases** (Application Orchestration)

- Orchestrate business workflows
- Coordinate multiple Actions and Operations
- Own database transaction boundaries
- Emit domain events
- **Never coupled to HTTP Request objects**

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
            // 1. Reserve inventory (prevents overselling)
            $this->reserveInventory->execute($data->items);

            // 2. Create the order
            $order = $this->createOrder->execute($data);

            // 3. Decrement stock for each item
            foreach ($data->items as $item) {
                $this->updateStock->execute($item['product_id'], -$item['quantity']);
            }

            // 4. Emit event for side effects (email, notifications, etc.)
            event(new OrderPlaced($order));

            return $order;
        });
    }
}
```

#### **Actions** (Domain Operations)

- **Atomic**: One action = one domain operation
- Encapsulate business rules
- Validate business invariants
- Can emit domain events
- Return domain objects, booleans, collections, or void

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

**Valid Action Return Types:**

- Domain objects: `CreateOrderAction` → `Order`
- Collections: `BulkUpdateProductsAction` → `Collection<Product>`
- Booleans: `ActivateAccountAction` → `bool`
- Void: `SendNotificationOperation` → `void`
- Primitives: `CalculateTaxAction` → `float`

#### **Operations** (Reusable Action Orchestration)

- **Compose Actions/Queries into reusable sequences**
- Extract common action chains shared across multiple UseCases
- Can call Actions, Queries, and Models
- **Don't call other Operations or UseCases**

```php
class SaveAddressOperation
{
    public function __construct(
        private FormatAddressAction $formatAddress,
        private RemoveNullFieldsAction $removeNullFields,
        private SaveAddressAction $saveAddress,
    ) {}

    public function execute(array $addressData, User $user): Address
    {
        // 1. Format address
        $formatted = $this->formatAddress->execute($addressData);

        // 2. Remove null fields
        $cleaned = $this->removeNullFields->execute($formatted);

        // 3. Save address
        return $this->saveAddress->execute($cleaned, $user);
    }
}
```

**Operation Examples:**

- `SaveAddressOperation` - Format → Clean → Save address (reused by multiple UseCases)
- `ProcessPaymentOperation` - Validate payment → Charge → Record transaction
- `PrepareOrderDataOperation` - Validate items → Calculate totals → Apply discounts
- `SyncInventoryOperation` - Fetch stock → Update cache → Notify if low

**UseCase vs Operation:**

```php
// ✅ UseCase: Full feature workflow (owns transactions, emits events)
class PlaceOrderUseCase
{
    public function __construct(
        private SaveAddressOperation $saveAddress,  // Reuses Operation
    ) {}

    public function execute(OrderData $data): Order
    {
        return DB::transaction(function () use ($data) {
            // UseCase orchestrates the full workflow
            $address = $this->saveAddress->execute($data->address, $data->user);
            // ... create order, update stock, etc.
            event(new OrderPlaced($order));
            return $order;
        });
    }
}

// ✅ Operation: Reusable action sequence (called by multiple UseCases)
class SaveAddressOperation
{
    public function execute(array $addressData, User $user): Address
    {
        // Operation composes multiple Actions
        return $this->saveAddress->execute(
            $this->removeNullFields->execute(
                $this->formatAddress->execute($addressData)
            ),
            $user
        );
    }
}
```

#### **Queries** (Read Operations)

- **Read-only**: Never mutate state
- Complex data retrieval
- Can return primitives, collections, or domain objects
- Optimize for specific read scenarios

```php
class GetCustomerOrdersQuery
{
    public function execute(int $customerId, ?OrderStatus $status = null): Collection
    {
        return Order::query()
            ->where('customer_id', $customerId)
            ->when($status, fn($q) => $q->where('status', $status))
            ->with(['items.product', 'customer'])
            ->latest()
            ->get();
    }
}
```

**Query Examples:**

- `HasActiveSubscriptionQuery` → `bool`
- `GetLowStockProductsQuery` → `Collection<Product>`
- `CalculateCartTotalQuery` → `float`
- `FindProductsByCategoryQuery` → `Collection<Product>`

---

### Atomicity: Actions and Queries

**Actions are atomic** — One business operation on one model/aggregate. No calling other Actions or Queries.

**Queries are atomic** — One read operation, return data. No calling other Queries or Actions.

**If you need to compose multiple Actions/Queries:** Use an Operation (reusable sequence) or UseCase (full workflow).

---

### Dependency Rules

**What Can Call What:**

```
Controllers → UseCases ✅
Controllers → Operations ✅ (for simple cases)
Controllers → Actions ❌ (use UseCase instead)

UseCases → Actions ✅
UseCases → Operations ✅
UseCases → Queries ✅
UseCases → Other UseCases ❌ (extract shared logic to Action)

Actions → Models ✅ (atomic - single operation)
Actions → Other Actions ❌ (compose in UseCase/Operation)
Actions → Queries ❌ (UseCase/Operation passes needed data)

Operations → Actions ✅
Operations → Models ✅
Operations → Other Operations ❌
Operations → UseCases ❌

Queries → Models ✅
Queries → Other Queries ❌
Queries → Actions ❌
```

**Domain Layer Purity:**

- Domain layer (Models, Actions, DTOs, Events, Enums, Exceptions, Queries) has **ZERO** dependencies on Service layer
- Domain is framework-agnostic business logic
- Services consume Domain, never the reverse

---

### Dependency Injection Patterns

**Critical for Laravel Octane compatibility and testability:**

✅ **Constructor = Dependencies, Execute = Data**

```php
class PlaceOrderUseCase
{
    // Dependencies in constructor
    public function __construct(
        private CreateOrderAction $createOrder,
        private EmailService $emailService,
        private LoggerInterface $logger,
    ) {}

    // Data in execute method
    public function execute(OrderData $data): Order
    {
        // Implementation
    }
}
```

❌ **Anti-pattern: Data in Constructor**

```php
// Don't do this - breaks Octane singleton resolution
public function __construct(
    private OrderData $data,  // ❌ State in constructor
) {}
```

**Why This Matters:**

- **Octane**: Classes are singletons - constructor called once, execute() called per request
- **Testing**: Easy to mock dependencies, easy to test with different data
- **Clarity**: Clear separation between infrastructure (injected) and data (passed)

---

### Transaction Boundaries

**UseCases own transaction boundaries** because they understand the complete business workflow:

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

**Actions Don't Manage Transactions:**

```php
class CreateOrderAction
{
    // No DB::transaction here - let UseCase handle it
    public function execute(OrderData $data): Order
    {
        return Order::create([
            'customer_id' => $data->customerId,
            'total' => $data->total,
        ]);
    }
}
```

**Why:**

- UseCase sees the full workflow atomicity requirements
- Actions stay focused and composable
- Easier to test Actions without transaction overhead

---

### Data Flow with DTOs

**Prefer DTOs over arrays for type safety:**

```php
// ✅ Type-safe with DTO
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
}

// Usage in Controller
$order = $this->placeOrder->execute(
    OrderData::from($request->validated())
);

// ❌ Weak typing with arrays
$order = $this->placeOrder->execute($request->validated());
```

---

### Event-Driven Side Effects

**Emit events instead of directly calling side effects:**

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

        // Let listeners handle side effects
        event(new OrderPlaced($order));

        return $order;
    }
}

// Listener handles email asynchronously
class SendOrderConfirmation
{
    public function handle(OrderPlaced $event): void
    {
        $this->sendEmail->execute($event->order);
    }
}
```

**Benefits:**

- Decouples core workflow from side effects
- Side effects can be async/queued
- Easy to add new side effects without modifying UseCase
- Better testability

---

### Service Isolation

Services should be **autonomous** with clear boundaries:

**✅ Good: Event-based communication**

```php
// Admin service emits event when order status changes
event(new OrderStatusUpdated($order));

// Client service listens to update customer-facing status
class NotifyCustomerOnStatusChange
{
    public function handle(OrderStatusUpdated $event): void
    {
        // Client-facing notification logic
    }
}
```

**❌ Bad: Direct coupling**

```php
// Don't call other services directly
app(ClientNotificationService::class)->notifyCustomer($order);
```

**Cross-Service Communication:**

- **Events**: Preferred for async workflows
- **Shared Domain**: Both Admin and Client services consume the same Domain models
- **API contracts**: For synchronous needs (via HTTP or internal interfaces)

---

### Testing Strategy

The architecture enables comprehensive testing:

**Unit Tests: Actions & Operations**

```php
test('updates product stock', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $action = new UpdateStockAction();

    $result = $action->execute($product, -3);

    expect($result->stock)->toBe(7);
});
```

**Integration Tests: UseCases**

```php
test('places order successfully', function () {
    $useCase = app(PlaceOrderUseCase::class);
    $data = OrderData::from([...]);

    $order = $useCase->execute($data);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->status)->toBe(OrderStatus::PENDING);
});
```

**Feature Tests: Controllers**

```php
test('creates order via API', function () {
    $response = $this->postJson('/api/orders', [...]);

    $response->assertCreated()
        ->assertJsonStructure(['id', 'total', 'status']);
});
```

---

## Commands Reference

### Service Layer Commands

| Command           | Arguments                   | Options          | Description           |
| ----------------- | --------------------------- | ---------------- | --------------------- |
| `make:service`    | `{name}`                    | -                | Create a new service  |
| `make:controller` | `{name} {module} {service}` | `--resource, -r` | Create a controller   |
| `make:request`    | `{name} {module} {service}` | -                | Create a form request |
| `make:use-case`   | `{name} {module} {service}` | -                | Create a use case     |
| `make:operation`  | `{name} {module} {service}` | -                | Create an operation   |

### Domain Layer Commands

| Command          | Arguments         | Options | Description                         |
| ---------------- | ----------------- | ------- | ----------------------------------- |
| `make:model`     | `{name} {domain}` | -       | Create a domain model (Eloquent)    |
| `make:action`    | `{name} {domain}` | -       | Create a domain action              |
| `make:dto`       | `{name} {domain}` | -       | Create a DTO (Data Transfer Object) |
| `make:policy`    | `{name} {domain}` | -       | Create a domain policy              |
| `make:event`     | `{name} {domain}` | -       | Create a domain event               |
| `make:enum`      | `{name} {domain}` | -       | Create a domain enum                |
| `make:exception` | `{name} {domain}` | -       | Create a domain exception           |
| `make:query`     | `{name} {domain}` | -       | Create a domain query               |

---

## Best Practices

### 1. Keep Controllers Thin

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

### 2. UseCases Orchestrate Business Workflows

```php
class PlaceOrderUseCase
{
    public function __construct(
        private CreateOrderAction $createOrder,
        private UpdateStockAction $updateStock,
    ) {}

    public function execute(OrderData $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = $this->createOrder->execute($data);

            foreach ($data->items as $item) {
                $this->updateStock->execute($item['product_id'], -$item['quantity']);
            }

            event(new OrderPlaced($order));

            return $order;
        });
    }
}
```

### 3. Requests Validate

```php
class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }
}
```

### 4. One Module = One Feature

Split modules by feature within each service:

- **Admin Service**:
    - `Modules/Orders` — Order management (CRUD, status changes)
    - `Modules/Products` — Product management (create, update, inventory)
    - `Modules/Users` — User management (list, ban, roles)
- **Client Service**:
    - `Modules/Orders` — Place orders, view order history
    - `Modules/Cart` — Cart management
    - `Modules/Account` — Profile, addresses

---

## Guiding AI Assistants

To ensure AI assistants (GitHub Copilot, Cursor, etc.) follow these architectural patterns:

### Option 1: Project-Level Instructions (Recommended)

Create `.github/copilot-instructions.md` or `.cursorrules` in your Laravel project:

```markdown
# Project Architecture: Pulse + Vertical Slice

This project uses Pulse for vertical slice architecture. Follow these rules:

## File Structure

- Controllers: `app/Services/{Service}/Modules/{Module}/Controllers/`
- UseCases: `app/Services/{Service}/Modules/{Module}/UseCases/`
- Actions: `app/Domain/{Domain}/Actions/`
- Models: `app/Domain/{Domain}/Models/`

## Service Model

Services are delivery boundaries scoped to a consumer audience (Admin, Client), not business capabilities. The Domain layer holds all shared business logic.

## Code Rules

1. Controllers only handle HTTP - extract validated data, call UseCase, return response
2. UseCases orchestrate workflows - coordinate Actions/Operations, own transactions
3. Actions are atomic - one operation, emit events, return domain objects
4. Requests validate structure only - no business logic
5. Use constructor DI for dependencies, execute() parameters for data
6. Prefer DTOs over arrays for type safety
7. Domain layer has zero dependencies on Service layer

## Examples

- See: https://github.com/faran/pulsar#architecture-best-practices
```

### Option 2: Inline Code Comments

Add architectural hints in base classes:

```php
<?php

namespace App\Services\Client\Modules\Orders\UseCases;

/**
 * UseCase: Orchestrates business workflow
 *
 * Rules:
 * - Constructor: Inject dependencies (Actions, Operations, Services)
 * - execute(): Accept DTOs or validated data
 * - Own transaction boundaries with DB::transaction()
 * - Emit events for side effects
 * - Never depend on Request objects
 */
class PlaceOrderUseCase
{
    public function __construct(
        private CreateOrderAction $createOrder,
        private UpdateInventoryAction $updateInventory,
    ) {}

    public function execute(OrderData $data): Order
    {
        // Implementation
    }
}
```

### Option 3: Reference Documentation

In your project's README or `docs/architecture.md`, link directly to Pulse patterns:

```markdown
# Our Architecture

We follow Pulse's vertical slice architecture patterns:

- [Architecture Overview](https://github.com/faran/pulsar#architecture)
- [Best Practices](https://github.com/faran/pulsar#architecture-best-practices)
- [Layer Responsibilities](https://github.com/faran/pulsar#layer-responsibilities)
```

### Recommended Approach

**Combine all three:**

1. **`.github/copilot-instructions.md`** for AI context
2. **Base class docblocks** for inline guidance
3. **Project docs** linking to Pulse README for team reference

This ensures both AI assistants and human developers follow consistent patterns.

---

## Contributing

Contributions are welcome! Please follow the existing code style and commit conventions.

---

## License

MIT License - see LICENSE file for details.

---

## Credits

Built with ❤️ by Faran Ali

Inspired by:

- [Lucid Architecture](https://github.com/lucid-architecture/laravel)
- Vertical Slice Architecture principles
- Domain-Driven Design concepts
