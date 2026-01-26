# Pulse

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
composer require faran/pulse --dev
```

## Architecture

Pulse organizes code into two complementary layers:

### Service Layer

The Service Layer handles HTTP delivery and application orchestration using **vertical slice architecture**. Code is organized by business capability (Service → Module → Features), not by technical layer.

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
    └── Operations/                     # Cross-cutting operations
```

**Example:** E-commerce Checkout Service

```
app/Services/Checkout/
├── Providers/
├── Routes/api.php
└── Modules/
    ├── Cart/
    │   ├── Controllers/CartController.php
    │   ├── Requests/AddToCartRequest.php
    │   └── UseCases/AddItemToCart.php
    └── Payment/
        ├── Controllers/PaymentController.php
        ├── Requests/ProcessPaymentRequest.php
        └── UseCases/ProcessPayment.php
```

Routes: `/api/checkout/cart`, `/api/checkout/payment`

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
composer require faran/pulse --dev
```

### Generate Your First Service

```bash
pulse make:service Authentication
```

Then follow the sections below to generate individual file types.

---

## File Types

> **💡 Naming Freedom:** Pulse gives you complete control over class names. Examples below use suffixes like `Controller`, `Action`, `UseCase` for clarity, but you can name classes however you prefer:
>
> - `pulse make:controller ProductController ...` → `ProductController.php` ✅
> - `pulse make:controller Product ...` → `Product.php` ✅
> - `pulse make:action CreateOrderAction ...` → `CreateOrderAction.php` ✅
> - `pulse make:action CreateOrder ...` → `CreateOrder.php` ✅
>
> The generated class name matches exactly what you specify.

### Service Layer Files

#### Controllers

**Purpose:** Handle HTTP requests and orchestrate application flow.

**Command:**

```bash
pulse make:controller ProductController Product Catalog
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
pulse make:request AddToCartRequest Cart Checkout
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
pulse make:use-case PlaceOrder Order Checkout
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

**Purpose:** Infrastructure and cross-cutting concerns (email, logging, caching, file storage, external APIs).

**Not Business Logic:** Operations handle "how" (send email, log event), not "what" (place order, update stock).

**Command:**

```bash
pulse make:operation SendOrderConfirmationEmail Order Checkout
```

**Location:** `app/Services/{Service}/Modules/{Module}/Operations/`

**Example:**

```php
class SendOrderConfirmationEmail
{
    public function __construct(
        private Mailer $mailer
    ) {}

    public function execute(Order $order): void
    {
        $this->mailer->to($order->customer->email)
            ->send(new OrderConfirmation($order));
    }
}
```

**When to use Operations:**

- Sending emails/notifications
- Logging/auditing
- File uploads/downloads
- External API calls
- Caching operations
- Generating PDFs/reports

**When to use UseCases instead:**

- Coordinating business workflows
- Orchestrating multiple domain Actions
- Implementing business processes

---

### Domain Layer Files

#### Models

**Purpose:** Eloquent models representing domain entities.

**Command:**

```bash
pulse make:model Product Catalog
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
pulse make:action UpdateProductStock Catalog
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
pulse make:dto OrderData Order
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
pulse make:policy OrderPolicy Order
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
pulse make:event OrderPlaced Order
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
pulse make:enum OrderStatus Order
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
pulse make:exception InsufficientStockException Catalog
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
pulse make:query GetCustomerOrders Order
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

Building a checkout system:

```bash
# 1. Create the service
pulse make:service Checkout

# 2. Create cart module
pulse make:controller CartController Cart Checkout
pulse make:request AddToCartRequest Cart Checkout
pulse make:use-case AddItemToCart Cart Checkout

# 3. Create payment module
pulse make:controller PaymentController Payment Checkout -r
pulse make:request ProcessPaymentRequest Payment Checkout
pulse make:use-case ProcessPayment Payment Checkout

# 4. Create order module
pulse make:controller OrderController Order Checkout
pulse make:request PlaceOrderRequest Order Checkout
pulse make:use-case PlaceOrder Order Checkout
pulse make:operation SendOrderConfirmationEmail Order Checkout
```

**Resulting structure:**

```
app/Services/Checkout/
├── Providers/
│   ├── CheckoutServiceProvider.php
│   └── RouteServiceProvider.php
├── Routes/
│   └── api.php
└── Modules/
    ├── Cart/
    │   ├── Controllers/CartController.php
    │   ├── Requests/AddToCartRequest.php
    │   └── UseCases/AddItemToCart.php
    ├── Payment/
    │   ├── Controllers/PaymentController.php
    │   ├── Requests/ProcessPaymentRequest.php
    │   └── UseCases/ProcessPayment.php
    └── Order/
        ├── Controllers/OrderController.php
        ├── Requests/PlaceOrderRequest.php
        ├── UseCases/PlaceOrder.php
        └── Operations/SendOrderConfirmationEmail.php
```

**Define routes** in `app/Services/Checkout/Routes/api.php`:

```php
use App\Services\Checkout\Modules\Cart\Controllers\CartController;
use App\Services\Checkout\Modules\Order\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::post('/cart', [CartController::class, 'add']);
Route::post('/orders', [OrderController::class, 'store']);
```

Routes accessible at: `/api/checkout/cart`, `/api/checkout/orders`

### Domain Layer Example

Building an e-commerce domain:

```bash
# 1. Create domain models
pulse make:model Product Catalog
pulse make:model Order Order

# 2. Create actions for business operations
pulse make:action UpdateProductStock Catalog
pulse make:action CreateOrder Order

# 3. Create DTOs for data transfer
pulse make:dto ProductData Catalog
pulse make:dto OrderData Order

# 4. Create policies for authorization
pulse make:policy ProductPolicy Catalog
pulse make:policy OrderPolicy Order

# 5. Create domain events
pulse make:event ProductOutOfStock Catalog
pulse make:event OrderPlaced Order

# 6. Create enums for states
pulse make:enum ProductStatus Catalog
pulse make:enum OrderStatus Order

# 7. Create domain exceptions
pulse make:exception InsufficientStockException Catalog
pulse make:exception OrderAlreadyCancelledException Order

# 8. Create queries for complex reads
pulse make:query GetLowStockProducts Catalog
pulse make:query GetCustomerOrders Order
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

#### **Operations** (Infrastructure/Cross-Cutting)

- **Infrastructure concerns**: Email, logging, caching, file storage, external APIs
- **"How" not "What"**: Handle technical execution, not business decisions
- Can use Actions for data retrieval
- **Don't call other Operations or UseCases**

```php
class GenerateInvoicePDFOperation
{
    public function __construct(
        private PDFGenerator $pdf,
        private FileStorage $storage,
    ) {}

    public function execute(Order $order): string
    {
        $pdf = $this->pdf->generate('invoice', [
            'order' => $order,
            'items' => $order->items,
            'total' => $order->total,
        ]);

        $path = "invoices/{$order->id}.pdf";
        $this->storage->put($path, $pdf);

        return $path;
    }
}
```

**Operation Examples:**

- `SendOrderConfirmationEmailOperation` - Sends email via mail service
- `GenerateInvoicePDFOperation` - Creates PDF document
- `UploadProductImageOperation` - Handles file upload to storage
- `LogUserActivityOperation` - Records audit trail
- `CacheProductCatalogOperation` - Updates cache layer
- `NotifyExternalSystemOperation` - Calls third-party API

**UseCase vs Operation:**

```php
// ✅ UseCase: Business workflow (WHAT to do)
class ProcessRefundUseCase
{
    public function execute(RefundData $data): Refund
    {
        // Business logic: validate refund, update order, create refund record
    }
}

// ✅ Operation: Infrastructure (HOW to do it)
class SendRefundEmailOperation
{
    public function execute(Refund $refund): void
    {
        // Infrastructure: send email notification
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

Actions → Models ✅
Actions → Other Actions ❌ (compose in UseCase instead)
Actions → Queries ✅ (for reads)

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
// Order service emits event
event(new OrderPlaced($order));

// Inventory service listens
class DecrementStockOnOrder
{
    public function handle(OrderPlaced $event): void
    {
        // Inventory service reacts independently
    }
}
```

**❌ Bad: Direct coupling**

```php
// Don't call other services directly
app(InventoryService::class)->decrementStock($items);
```

**Cross-Service Communication:**

- **Events**: Preferred for async workflows
- **API contracts**: For synchronous needs (via HTTP or internal interfaces)
- **Shared Domain**: Common domain models can be shared

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

Split modules by business capability:

- **Checkout Service**:
    - `Modules/Cart` — Cart management
    - `Modules/Payment` — Payment processing
    - `Modules/Order` — Order placement

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

## Code Rules

1. Controllers only handle HTTP - extract validated data, call UseCase, return response
2. UseCases orchestrate workflows - coordinate Actions/Operations, own transactions
3. Actions are atomic - one operation, emit events, return domain objects
4. Requests validate structure only - no business logic
5. Use constructor DI for dependencies, execute() parameters for data
6. Prefer DTOs over arrays for type safety
7. Domain layer has zero dependencies on Service layer

## Examples

- See: https://github.com/faran/pulse#architecture-best-practices
```

### Option 2: Inline Code Comments

Add architectural hints in base classes:

```php
<?php

namespace App\Services\Checkout\Modules\Order\UseCases;

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

- [Architecture Overview](https://github.com/faran/pulse#architecture)
- [Best Practices](https://github.com/faran/pulse#architecture-best-practices)
- [Layer Responsibilities](https://github.com/faran/pulse#layer-responsibilities)
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
