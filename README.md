# Pulse

> A modern Laravel code generation tool for building service-oriented applications with vertical slice architecture.

## Table of Contents

- [Philosophy](#philosophy)
- [Architecture](#architecture)
- [Installation & Usage](#installation--usage)
- [File Types](#file-types)
  - [Service Providers](#service-providers)
  - [Controllers](#controllers)
  - [Requests (Form Requests)](#requests-form-requests)
  - [UseCases](#usecases)
  - [Operations](#operations)
  - [Routes](#routes)
- [Complete Example](#complete-example)
- [Commands Reference](#commands-reference)
- [Design Decisions](#design-decisions)
- [Best Practices](#best-practices)
- [Contributing](#contributing)

## Philosophy

Pulse embraces **vertical slice architecture** over traditional layered architecture. Instead of organizing code by technical concerns (Controllers, Models, Services), Pulse organizes by **business capabilities** (Services → Modules → Features).

### Core Principles

**1. Service-Oriented Structure**
Services represent major business domains or bounded contexts. Each service is autonomous and can evolve independently.

**2. Vertical Slicing**
Everything related to a feature lives together. A module contains its controllers, requests, use cases—not scattered across folders.

**3. Minimal Boilerplate**
Generated code is clean and minimal. No unnecessary abstractions. Empty classes by default; opt-in for scaffolding.

**4. Developer Experience**
Simple, memorable commands. Consistent patterns. Instant feedback. No guesswork.

## Architecture

Pulse generates a three-tier hierarchy:

```
app/Services/{Service}/
├── Providers/
│   ├── {Service}ServiceProvider.php    # Auto-registers routes
│   └── RouteServiceProvider.php        # Slug-based routing
├── Routes/
│   └── api.php                         # API routes with /api/{slug} prefix
└── Modules/{Module}/
    ├── Controllers/                    # HTTP layer
    ├── Requests/                       # Validation
    └── UseCases/                       # Business logic
```

### Why This Structure?

**Traditional Laravel:**

```
app/Http/Controllers/OrderController.php
app/Http/Requests/CreateOrderRequest.php
app/Services/OrderService.php
```

→ Code for one feature scattered across multiple directories.

**Pulse Approach:**

```
app/Services/Sales/Modules/Order/
├── Controllers/OrderController.php
├── Requests/CreateOrderRequest.php
└── UseCases/CreateOrder.php
```

→ Everything for the Order module lives together.

## Installation & Usage

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

This section explains the purpose and usage of each file type that Pulse generates.

### Service Providers

**File Type:** `ServiceProvider.php` and `RouteServiceProvider.php`

**Purpose:**
Service providers are auto-generated during `pulse make:service` and handle bootstrapping your service. They register routes, service container bindings, and event listeners specific to your service.

**What It Does:**
- Registers routes from the `Routes/api.php` file
- Auto-discovers and loads routes with the `/api/{service-slug}` prefix
- Provides a clear entry point for service-wide configuration

**Location:**
```
app/Services/{Service}/Providers/
├── {Service}ServiceProvider.php
└── RouteServiceProvider.php
```

**Usage:**
After generating a service with `pulse make:service`, register it in `config/app.php` or `bootstrap/providers.php`:

```php
App\Services\Authentication\Providers\AuthenticationServiceProvider::class
```

**Don't Modify:** The structure is managed by Pulse. Add service-specific bindings or registrations in the `ServiceProvider.php` file as needed.

**Example:**
```
app/Services/Authentication/Providers/
├── AuthenticationServiceProvider.php
└── RouteServiceProvider.php
```

---

### Controllers

**File Type:** `{Name}Controller.php`

**Purpose:**
Controllers handle HTTP requests and orchestrate the interaction between incoming requests and your business logic. Controllers receive validated data from Requests and delegate work to UseCases.

**What It Does:**
- Receives HTTP requests and extracts data
- Calls appropriate Requests for validation
- Instantiates and calls UseCases to handle business logic
- Returns HTTP responses

**Location:**
```
app/Services/{Service}/Modules/{Module}/Controllers/
└── {Name}Controller.php
```

**Create With:**
```bash
# Empty controller
pulse make:controller AuthController Login Authentication

# Resource controller with CRUD methods
pulse make:controller UserController User Authentication --resource
```

**Arguments:** `{name} {module} {service}`

**Auto-Suffixing:** `Auth` → `AuthController` (suffix added automatically if omitted)

**Generation Modes:**
- **Plain (default):** Empty class, you define methods
- **Resource (`--resource` or `-r`):** Includes standard CRUD methods (index, show, store, update, delete)

**Example (Plain):**
```php
class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $token = (new AuthenticateUser)->execute($request->validated());
        return response()->json(['token' => $token]);
    }
}
```

**Best Practice:** Keep controllers thin—delegate complex logic to UseCases.

---

### Requests (Form Requests)

**File Type:** `{Name}Request.php`

**Purpose:**
Form requests encapsulate validation logic and authorization checks. They validate incoming data before it reaches your controller or use case.

**What It Does:**
- Validates request input against defined rules
- Provides custom error messages
- Handles authorization (can user perform this action?)
- Returns validated data as an array to the controller

**Location:**
```
app/Services/{Service}/Modules/{Module}/Requests/
└── {Name}Request.php
```

**Create With:**
```bash
pulse make:request LoginRequest Login Authentication
```

**Arguments:** `{name} {module} {service}`

**Auto-Suffixing:** `Login` → `LoginRequest` (suffix added automatically if omitted)

**Default Generated Structure:**
```php
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false; // Override to true when ready
    }

    public function rules(): array
    {
        return [
            // Define your validation rules
        ];
    }

    public function messages(): array
    {
        return [
            // Define custom error messages
        ];
    }
}
```

**Usage in Controllers:**
```php
class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        // Request is already validated and authorized
        $data = $request->validated();
        // ... pass to use case
    }
}
```

**Best Practice:** Let the framework validate before your code runs—fail fast with clear error messages.

---

### UseCases

**File Type:** `{Name}.php` (no suffix)

**Purpose:**
UseCases encapsulate business logic that is independent of HTTP delivery mechanisms. They contain the core application logic that could be called from controllers, commands, jobs, or events.

**What It Does:**
- Executes a specific business operation
- Coordinates with Models, Services, and Events
- Returns domain objects or data
- Remains testable without HTTP context

**Location:**
```
app/Services/{Service}/Modules/{Module}/UseCases/
└── {Name}.php
```

**Create With:**
```bash
pulse make:use-case AuthenticateUser Login Authentication
```

**Arguments:** `{name} {module} {service}`

**No Auto-Suffixing:** The class name is exactly as you provide it (no suffix added).

**Default Generated Structure:**
```php
class AuthenticateUser
{
    public function execute(array $data)
    {
        // Your business logic here
    }
}
```

**Full Example:**
```php
class CreateUser
{
    public function execute(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new UserRegistered($user));
        
        return $user;
    }
}
```

**Usage in Controllers:**
```php
class UserController extends Controller
{
    public function store(CreateUserRequest $request)
    {
        $user = (new CreateUser)->execute($request->validated());
        return response()->json($user, 201);
    }
}
```

**Reusability:**
Call the same UseCase from multiple contexts:
```php
// From a controller
$user = (new CreateUser)->execute($data);

// From a command
$this->info('Creating users...');
$user = (new CreateUser)->execute($data);

// From an event listener
event(new SomeEvent());
// Listener calls: (new CreateUser)->execute($data);
```

**Best Practice:** Keep UseCases focused on one business operation. Don't mix multiple concerns in a single UseCase.

---

### Operations

**File Type:** `{Name}Operation.php`

**Purpose:**
Operations are similar to UseCases but are for cross-service or infrastructure-level operations. They perform lower-level tasks like database operations, external API calls, or utility functions that might be shared across multiple services.

**When to Use:**
- Complex, reusable logic that multiple services need
- Infrastructure or utility operations
- Operations that don't map directly to a user action

**Location:**
```
app/Services/{Service}/Modules/{Module}/Operations/
└── {Name}Operation.php
```

**Create With:**
```bash
pulse make:operation SendEmail User Authentication
```

**Arguments:** `{name} {module} {service}`

**Auto-Suffixing:** `Send` → `SendOperation` (suffix added automatically if omitted)

**Example:**
```php
class SendWelcomeEmailOperation
{
    public function execute(User $user)
    {
        Mail::to($user->email)->send(new WelcomeEmail($user));
    }
}
```

**Difference from UseCases:**
| UseCases | Operations |
|----------|-----------|
| User-centric business logic | Infrastructure/utility tasks |
| Called from controllers/commands | Called from UseCases/Events |
| Domain operations | Cross-cutting concerns |
| "Create User", "Process Payment" | "Send Email", "Log Activity" |

---

### Routes

**File Type:** `api.php` (in `Routes/` directory)

**Purpose:**
Routes define the HTTP endpoints for your service. Each service has its own route file, making routes modular and service-specific.

**Location:**
```
app/Services/{Service}/Routes/
└── api.php
```

**Generated Automatically:** Created when you run `pulse make:service`

**Default Generated Structure:**
```php
<?php

use Illuminate\Support\Facades\Route;

// Define routes for your service here
// Routes are automatically prefixed with /api/{service-slug}
```

**Auto-Routing with Slug:**
All routes in this file are automatically prefixed with `/api/{service-slug}`:

Service name `Authentication` → Routes prefixed with `/api/authentication`
Service name `UserManagement` → Routes prefixed with `/api/user-management`

**Defining Routes:**
```php
use App\Services\Authentication\Modules\Login\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/refresh', [AuthController::class, 'refresh']);
```

**Resulting Endpoints:**
- `POST /api/authentication/login`
- `POST /api/authentication/logout`
- `POST /api/authentication/refresh`

**Benefits:**
- Routes are organized by service
- Clear service boundaries
- Easy to find endpoint definitions
- Services can be versioned independently

**Best Practice:** Keep routes simple and close to controllers. Complex route logic should be in controllers or middleware.

---

## Installation

---

## Complete Example

Building an authentication system:

```bash
# 1. Create the service
pulse make:service Authentication

# 2. Create login module components
pulse make:controller AuthController Login Authentication
pulse make:request LoginRequest Login Authentication
pulse make:use-case AuthenticateUser Login Authentication

# 3. Create registration module components
pulse make:controller RegisterController Registration Authentication -r
pulse make:request RegisterRequest Registration Authentication
pulse make:use-case CreateUser Registration Authentication

# 4. Create password reset module
pulse make:controller PasswordController Password Authentication
pulse make:request ResetPasswordRequest Password Authentication
pulse make:use-case ResetPassword Password Authentication
```

**Resulting structure:**

```
app/Services/Authentication/
├── Providers/
├── Routes/
│   └── api.php
└── Modules/
    ├── Login/
    │   ├── Controllers/AuthController.php
    │   ├── Requests/LoginRequest.php
    │   └── UseCases/AuthenticateUser.php
    ├── Registration/
    │   ├── Controllers/RegisterController.php
    │   ├── Requests/RegisterRequest.php
    │   └── UseCases/CreateUser.php
    └── Password/
        ├── Controllers/PasswordController.php
        ├── Requests/ResetPasswordRequest.php
        └── UseCases/ResetPassword.php
```

**Define routes** in `app/Services/Authentication/Routes/api.php`:

```php
use App\Services\Authentication\Modules\Login\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
```

Routes accessible at: `/api/authentication/login`, `/api/authentication/logout`

---

## Commands Reference

| Command           | Arguments                   | Options          | Description           |
| ----------------- | --------------------------- | ---------------- | --------------------- |
| `make:service`    | `{name}`                    | -                | Create a new service  |
| `make:controller` | `{name} {module} {service}` | `--resource, -r` | Create a controller   |
| `make:request`    | `{name} {module} {service}` | -                | Create a form request |
| `make:use-case`   | `{name} {module} {service}` | -                | Create a use case     |

---

## Design Decisions

### Why Services?

Large applications naturally divide into business domains:

- **E-commerce:** `Catalog`, `Cart`, `Checkout`, `Shipping`
- **SaaS:** `Billing`, `Users`, `Analytics`, `Notifications`
- **Marketplace:** `Sellers`, `Buyers`, `Products`, `Reviews`

Services provide clear boundaries and prevent monolithic controllers.

### Why Modules?

Modules are feature-oriented slices within a service. Instead of grouping by layer (all controllers, all models), group by feature (everything for User management).

**Benefits:**

- Easy to locate related code
- Simple to onboard new developers
- Clear ownership boundaries
- Facilitates parallel development

### Why UseCases?

Controllers should be thin. UseCases encapsulate business logic, making it:

- **Testable** — Test business logic independently of HTTP
- **Reusable** — Call from controllers, commands, jobs, events
- **Maintainable** — Business rules in one place

### Why Minimal Output?

Less noise = better focus. You get what you need:

```
✓ Controller created successfully!
Location: app/Services/Auth/Modules/Login/Controllers/AuthController.php
```

No tutorials, no next steps, no fluff. Just confirmation and location.

---

## Naming Conventions

### Auto-Suffixing

**Controllers:** `User` → `UserController`
**Requests:** `Login` → `LoginRequest`
**UseCases:** No suffixing — you decide

### Slug Generation

Service names are converted to URL-friendly slugs:

- `UserManagement` → `/api/user-management`
- `ContentModeration` → `/api/content-moderation`

---

## Comparison with Lucid Architecture

Pulse is inspired by [Lucid Architecture](https://github.com/lucid-architecture/laravel) but diverges in key ways:

| Aspect         | Lucid                                             | Pulse                                 |
| -------------- | ------------------------------------------------- | ------------------------------------- |
| **Structure**  | Features, Jobs, Operations, Domains               | Services, Modules, UseCases           |
| **Layers**     | 4 levels (Controller → Feature → Operation → Job) | 3 levels (Controller → UseCase)       |
| **Complexity** | Higher learning curve                             | Simpler, flatter                      |
| **Use Cases**  | Jobs in Domains folder                            | UseCases in Modules                   |
| **Philosophy** | Service-oriented with Jobs                        | Service-oriented with vertical slices |

**Pulse is lighter and more opinionated** — fewer abstractions, clearer boundaries.

---

## Best Practices

### 1. Keep Controllers Thin

```php
class UserController extends Controller
{
    public function store(CreateUserRequest $request)
    {
        $user = (new CreateUser)->execute($request->validated());

        return response()->json($user, 201);
    }
}
```

### 2. UseCases Handle Business Logic

```php
class CreateUser
{
    public function execute(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new UserRegistered($user));

        return $user;
    }
}
```

### 3. Requests Validate

```php
class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ];
    }
}
```

### 4. One Module = One Feature

Don't create a generic "User" module with everything. Split it:

- `Modules/Registration` — Sign up
- `Modules/Profile` — Edit profile
- `Modules/Account` — Account settings

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
