# Pulse Architecture Guide for AI Assistants

> Copy this file to your Laravel project as `.github/copilot-instructions.md` or `.cursorrules` to guide AI assistants.

---

## Project Structure

This project uses **Pulse** for vertical slice architecture with two layers:

### Service Layer (HTTP/Application)
```
app/Services/{Service}/Modules/{Module}/
├── Controllers/          # HTTP handlers only
├── Requests/            # Input validation
├── UseCases/            # Workflow orchestration
└── Operations/          # Infrastructure (email, cache, APIs)
```

### Domain Layer (Business Logic)
```
app/Domain/{Domain}/
├── Models/              # Eloquent models
├── Actions/             # Atomic business operations
├── DTOs/                # Data transfer objects
├── Policies/            # Authorization
├── Events/              # Domain events
├── Enums/               # Business states
├── Exceptions/          # Business rule violations
└── Queries/             # Complex read operations
```

---

## Critical Rules

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

### 2. Dependency Injection Pattern

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

---

## Code Templates

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

---

## When Generating Code

### Creating New Features

1. **Start with the UseCase** - defines the workflow
2. **Identify required Actions** - atomic operations needed
3. **Create supporting infrastructure** - DTOs, Events, Exceptions
4. **Wire up HTTP layer** - Controller, Request
5. **Add tests** - unit (Actions), integration (UseCases), feature (Controllers)

### Example: "Add product review feature"

```bash
# 1. Domain layer
pulse make:action CreateReviewAction Product
pulse make:dto ReviewData Product
pulse make:event ReviewCreated Product
pulse make:policy ReviewPolicy Product

# 2. Service layer
pulse make:use-case CreateReviewUseCase Review Catalog
pulse make:controller ReviewController Review Catalog
pulse make:request CreateReviewRequest Review Catalog
```

### File Organization Rules

- **One class per file**
- **Service modules** group by feature (Cart, Payment, Order)
- **Domain folders** group by aggregate (Product, Customer, Order)
- **No cross-service imports** - use events for communication
- **Domain never imports Service** - dependency flows one way

---

## Testing Patterns

### Unit Test (Action)
```php
test('updates product stock', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $action = new UpdateStockAction();
    
    $result = $action->execute($product, -3);
    
    expect($result->stock)->toBe(7);
});
```

### Integration Test (UseCase)
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

### Feature Test (Controller)
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

---

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

### Query Objects (Complex Reads)

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

---

## Quick Reference

### Pulse Commands

```bash
# Service layer
pulse make:service Sales
pulse make:controller OrderController Order Sales
pulse make:request PlaceOrderRequest Order Sales
pulse make:use-case PlaceOrderUseCase Order Sales
pulse make:operation SendEmailOperation Order Sales

# Domain layer
pulse make:model Order Sales
pulse make:action CreateOrderAction Sales
pulse make:dto OrderData Sales
pulse make:policy OrderPolicy Sales
pulse make:event OrderPlaced Sales
pulse make:enum OrderStatus Sales
pulse make:exception InvalidOrderException Sales
pulse make:query GetActiveOrdersQuery Sales
```

### Path Resolution

When generating code:
- Controllers → `app/Services/{Service}/Modules/{Module}/Controllers/`
- UseCases → `app/Services/{Service}/Modules/{Module}/UseCases/`
- Actions → `app/Domain/{Domain}/Actions/`
- Models → `app/Domain/{Domain}/Models/`

---

## Dependency Rules Summary

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

---

## When in Doubt

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

---

**For full examples and detailed explanations, see:** https://github.com/faran/pulse
