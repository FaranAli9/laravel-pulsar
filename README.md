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
    public function index(ListProductsRequest $request)
    {
        $products = (new ListProducts)->execute($request->validated());
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
class PlaceOrder
{
    public function execute(OrderData $data)
    {
        $order = (new CreateOrderAction)->execute($data);
        event(new OrderPlaced($order));
        return $order;
    }
}
```

---

#### Operations

**Purpose:** Reusable infrastructure or cross-service operations.

**Command:**

```bash
pulse make:operation SendOrderConfirmationEmail Order Checkout
```

**Location:** `app/Services/{Service}/Modules/{Module}/Operations/`

**Example:**

```php
class SendOrderConfirmationEmail
{
    public function execute(Order $order)
    {
        Mail::to($order->customer->email)->send(new OrderConfirmation($order));
    }
}
```

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
    public function execute(Product $product, int $quantity)
    {
        if ($product->stock + $quantity < 0) {
            throw new InsufficientStockException();
        }

        $product->update(['stock' => $product->stock + $quantity]);

        if ($product->stock === 0) {
            event(new ProductOutOfStock($product));
        }
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
class GetCustomerOrders
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
    public function store(PlaceOrderRequest $request)
    {
        $order = (new PlaceOrder)->execute($request->validated());
        return response()->json($order, 201);
    }
}
```

### 2. UseCases Handle Business Logic

```php
class PlaceOrder
{
    public function execute(array $data)
    {
        $order = (new CreateOrderAction)->execute(OrderData::from($data));
        event(new OrderPlaced($order));
        return $order;
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

