# Pulsar

> An opinionated Laravel architecture for building modular, domain-driven applications at scale.

**Pulsar is an opinionated architecture tool.** It provides a specific approach to organizing Laravel applications using clean architecture, domain-driven design, and service-oriented patterns. This architecture works well for medium-to-large scale applications, multi-tenant SaaS platforms, and teams that benefit from explicit boundaries between business logic and delivery mechanisms. If you prefer Laravel's default structure or other architectural patterns, Pulsar may not be the right fit for your project.

## Table of Contents

- [Installation](#installation)
- [Architecture Overview](#architecture-overview)
- [File Types](#file-types)
- [Commands Reference](#commands-reference)
- [Complete Example](#complete-example)
- [Contributing](#contributing)

## Installation

```bash
composer require faran/pulsar --dev
```

Generate your first service:

```bash
pulsar make:service Admin
```

## Architecture Overview

Pulsar organizes your Laravel application into **two complementary layers** following clean architecture and domain-driven design principles.

### Service Layer

**Purpose:** HTTP delivery and application orchestration, scoped by consumer audience (Admin, Client, etc.)

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
    ├── UseCases/                       # Application orchestration
    └── Operations/                     # Reusable action sequences
```

**Example:** Admin API Service

```
app/Services/Admin/
├── Providers/
├── Routes/api.php
└── Modules/
    ├── Orders/
    │   ├── Controllers/OrderController.php
    │   └── UseCases/ManageOrder.php
    └── Products/
        ├── Controllers/ProductController.php
        └── UseCases/CreateProduct.php
```

Routes: `/api/admin/orders`, `/api/admin/products`

### Domain Layer

**Purpose:** Pure business logic independent of HTTP, frameworks, or infrastructure

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

**Key Principle:** Services represent delivery boundaries (Admin API, Client API) scoped to consumer audiences, NOT business capabilities. Business logic lives in the shared Domain layer.

---

## File Types

> **💡 Naming Freedom:** You have complete control over class names. Examples use suffixes like `Controller`, `UseCase` for clarity, but you can name classes however you prefer.

### Service Layer Files

| File Type | Purpose | Location | Command Example |
|-----------|---------|----------|----------------|
| **Service** | Bootstrap a new service with providers and routes | `app/Services/{Service}/` | `pulsar make:service Admin` |
| **Controller** | Handle HTTP requests, orchestrate application flow | `Services/{Service}/Modules/{Module}/Controllers/` | `pulsar make:controller ProductController Products Admin` |
| **Request** | Validate input and authorize requests | `Services/{Service}/Modules/{Module}/Requests/` | `pulsar make:request AddToCartRequest Cart Client` |
| **UseCase** | Application-specific business logic coordinating domain operations | `Services/{Service}/Modules/{Module}/UseCases/` | `pulsar make:use-case PlaceOrder Orders Client` |
| **Operation** | Reusable sequences of Actions/Queries shared across multiple UseCases | `Services/{Service}/Modules/{Module}/Operations/` | `pulsar make:operation SaveAddress Account Client` |

### Domain Layer Files

| File Type | Purpose | Location | Command Example |
|-----------|---------|----------|----------------|
| **Model** | Eloquent models representing domain entities | `Domain/{Domain}/Models/` | `pulsar make:model Product Catalog` |
| **Action** | Atomic business operations encapsulating domain logic | `Domain/{Domain}/Actions/` | `pulsar make:action UpdateProductStock Catalog` |
| **DTO** | Immutable data carriers for transferring data between layers | `Domain/{Domain}/DTOs/` | `pulsar make:dto OrderData Order` |
| **Policy** | Business authorization rules for domain entities | `Domain/{Domain}/Policies/` | `pulsar make:policy OrderPolicy Order` |
| **Event** | Domain events signaling significant business occurrences | `Domain/{Domain}/Events/` | `pulsar make:event OrderPlaced Order` |
| **Enum** | Fixed sets of domain values and states | `Domain/{Domain}/Enums/` | `pulsar make:enum OrderStatus Order` |
| **Exception** | Domain-specific business rule violations | `Domain/{Domain}/Exceptions/` | `pulsar make:exception InsufficientStockException Catalog` |
| **Query** | Complex read-only domain queries | `Domain/{Domain}/Queries/` | `pulsar make:query GetCustomerOrders Order` |

---

## Commands Reference

### Service Layer Commands

| Command | Arguments | Options | Description |
|---------|-----------|---------|-------------|
| `make:service` | `{name}` | - | Create a new service |
| `make:controller` | `{name} {module} {service}` | `--resource, -r` | Create a controller |
| `make:request` | `{name} {module} {service}` | - | Create a form request |
| `make:use-case` | `{name} {module} {service}` | - | Create a use case |
| `make:operation` | `{name} {module} {service}` | - | Create an operation |

### Domain Layer Commands

| Command | Arguments | Options | Description |
|---------|-----------|---------|-------------|
| `make:model` | `{name} {domain}` | - | Create a domain model (Eloquent) |
| `make:action` | `{name} {domain}` | - | Create a domain action |
| `make:dto` | `{name} {domain}` | - | Create a DTO (Data Transfer Object) |
| `make:policy` | `{name} {domain}` | - | Create a domain policy |
| `make:event` | `{name} {domain}` | - | Create a domain event |
| `make:enum` | `{name} {domain}` | - | Create a domain enum |
| `make:exception` | `{name} {domain}` | - | Create a domain exception |
| `make:query` | `{name} {domain}` | - | Create a domain query |

---

## Complete Example

Building an e-commerce order placement feature across Service and Domain layers.

### 1. Generate Domain Layer (Business Logic)

```bash
# Create Order domain
pulsar make:model Order Order
pulsar make:action CreateOrder Order
pulsar make:action UpdateStock Catalog
pulsar make:dto OrderData Order
pulsar make:event OrderPlaced Order
pulsar make:enum OrderStatus Order
pulsar make:exception InsufficientStockException Catalog
```

**Domain structure:**

```
app/Domain/
├── Order/
│   ├── Models/Order.php
│   ├── Actions/CreateOrder.php
│   ├── DTOs/OrderData.php
│   ├── Events/OrderPlaced.php
│   └── Enums/OrderStatus.php
└── Catalog/
    ├── Actions/UpdateStock.php
    └── Exceptions/InsufficientStockException.php
```

### 2. Generate Service Layer (HTTP Delivery)

```bash
# Create Client service
pulsar make:service Client

# Create Orders module in Client service
pulsar make:controller OrderController Orders Client -r
pulsar make:request PlaceOrderRequest Orders Client
pulsar make:use-case PlaceOrder Orders Client
```

**Service structure:**

```
app/Services/Client/
├── Providers/
│   ├── ClientServiceProvider.php
│   └── RouteServiceProvider.php
├── Routes/api.php
└── Modules/
    └── Orders/
        ├── Controllers/OrderController.php
        ├── Requests/PlaceOrderRequest.php
        └── UseCases/PlaceOrder.php
```

### 3. Implement the Workflow

**Domain Action** (`app/Domain/Order/Actions/CreateOrder.php`):

```php
<?php

namespace App\Domain\Order\Actions;

use App\Domain\Order\DTOs\OrderData;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Enums\OrderStatus;

class CreateOrder
{
    public function execute(OrderData $data): Order
    {
        return Order::create([
            'customer_id' => $data->customerId,
            'items' => $data->items,
            'total' => $data->total,
            'status' => OrderStatus::PENDING,
        ]);
    }
}
```

**Domain Action** (`app/Domain/Catalog/Actions/UpdateStock.php`):

```php
<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Exceptions\InsufficientStockException;

class UpdateStock
{
    public function execute(Product $product, int $quantity): Product
    {
        if ($product->stock + $quantity < 0) {
            throw new InsufficientStockException($product);
        }

        $product->update(['stock' => $product->stock + $quantity]);

        return $product->fresh();
    }
}
```

**UseCase** (`app/Services/Client/Modules/Orders/UseCases/PlaceOrder.php`):

```php
<?php

namespace App\Services\Client\Modules\Orders\UseCases;

use App\Domain\Order\Actions\CreateOrder;
use App\Domain\Catalog\Actions\UpdateStock;
use App\Domain\Order\DTOs\OrderData;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Events\OrderPlaced;
use Illuminate\Support\Facades\DB;

class PlaceOrder
{
    public function __construct(
        private CreateOrder $createOrder,
        private UpdateStock $updateStock,
    ) {}

    public function execute(OrderData $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Create the order
            $order = $this->createOrder->execute($data);

            // Update stock for each item
            foreach ($data->items as $item) {
                $this->updateStock->execute($item['product_id'], -$item['quantity']);
            }

            // Emit event for side effects (email, notifications)
            event(new OrderPlaced($order));

            return $order;
        });
    }
}
```

**Controller** (`app/Services/Client/Modules/Orders/Controllers/OrderController.php`):

```php
<?php

namespace App\Services\Client\Modules\Orders\Controllers;

use App\Services\Client\Modules\Orders\Requests\PlaceOrderRequest;
use App\Services\Client\Modules\Orders\UseCases\PlaceOrder;
use App\Domain\Order\DTOs\OrderData;
use Illuminate\Http\JsonResponse;

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

**Request** (`app/Services/Client/Modules/Orders/Requests/PlaceOrderRequest.php`):

```php
<?php

namespace App\Services\Client\Modules\Orders\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }
}
```

**Routes** (`app/Services/Client/Routes/api.php`):

```php
use App\Services\Client\Modules\Orders\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/orders', OrderController::class);
```

### 4. Flow Summary

```
POST /api/client/orders
    ↓
OrderController → PlaceOrderRequest (validate input)
    ↓
PlaceOrder UseCase
    ↓
CreateOrder Action → Order Model
UpdateStock Action → Product Model
    ↓
Emit OrderPlaced Event
    ↓
Return Order (201 Created)
```

**Key Principles:**

- **Controllers** handle HTTP only (validate, call UseCase, return response)
- **UseCases** orchestrate workflows (coordinate Actions, own transactions, emit events)
- **Actions** are atomic business operations (one model, one operation)
- **Domain layer** has zero dependencies on Service layer
- **Services** are delivery boundaries scoped to audiences (Admin, Client)
- **Domain** contains shared business logic used by all services

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
- Clean Architecture & Vertical Slice principles
- Domain-Driven Design concepts
