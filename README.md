# Pulse

> A modern Laravel code generation tool for building service-oriented applications with vertical slice architecture.

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

## Installation

```bash
composer require faran/pulse --dev
```

## Usage

### Create a Service

```bash
pulse make:service Authentication
```

Creates:
```
app/Services/Authentication/
├── Providers/
│   ├── AuthenticationServiceProvider.php
│   └── RouteServiceProvider.php
├── Routes/
│   └── api.php
└── Modules/
    └── .gitkeep
```

**Register the service** in `config/app.php` or `bootstrap/providers.php`:
```php
App\Services\Authentication\Providers\AuthenticationServiceProvider::class
```

Your routes will be available at `/api/authentication/*`

---

### Create a Controller

```bash
# Empty controller
pulse make:controller AuthController Login Authentication

# Resourceful controller with CRUD methods
pulse make:controller UserController User Authentication --resource
```

**Arguments:** `{name} {module} {service}`

Creates: `app/Services/Authentication/Modules/Login/Controllers/AuthController.php`

**Auto-suffixing:** `Auth` → `AuthController`

**Note:** Controllers are empty by default. Use `--resource` or `-r` for RESTful methods.

---

### Create a Request

```bash
pulse make:request LoginRequest Login Authentication
```

**Arguments:** `{name} {module} {service}`

Creates: `app/Services/Authentication/Modules/Login/Requests/LoginRequest.php`

**Auto-suffixing:** `Login` → `LoginRequest`

Includes:
- `authorize()` method (returns `false` by default)
- `rules()` method for validation
- `messages()` method for custom error messages

---

### Create a UseCase

```bash
pulse make:use-case AuthenticateUser Login Authentication
```

**Arguments:** `{name} {module} {service}`

Creates: `app/Services/Authentication/Modules/Login/UseCases/AuthenticateUser.php`

**No auto-suffixing** — you control the exact class name.

Simple class with an `execute()` method where you implement business logic.

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

| Command | Arguments | Options | Description |
|---------|-----------|---------|-------------|
| `make:service` | `{name}` | - | Create a new service |
| `make:controller` | `{name} {module} {service}` | `--resource, -r` | Create a controller |
| `make:request` | `{name} {module} {service}` | - | Create a form request |
| `make:use-case` | `{name} {module} {service}` | - | Create a use case |

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

| Aspect | Lucid | Pulse |
|--------|-------|-------|
| **Structure** | Features, Jobs, Operations, Domains | Services, Modules, UseCases |
| **Layers** | 4 levels (Controller → Feature → Operation → Job) | 3 levels (Controller → UseCase) |
| **Complexity** | Higher learning curve | Simpler, flatter |
| **Use Cases** | Jobs in Domains folder | UseCases in Modules |
| **Philosophy** | Service-oriented with Jobs | Service-oriented with vertical slices |

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

## FAQ

**Q: Should I use Services for small apps?**
A: No. Services add overhead. Use default Laravel structure for simple CRUD apps.

**Q: How many Services should I have?**
A: Start with 1-3. Add more as domains emerge. Don't over-engineer.

**Q: Can I mix with traditional Laravel structure?**
A: Yes! Services are just organized folders. Use them where complexity demands it.

**Q: Do I need UseCases for everything?**
A: No. Simple CRUD? Put logic in the controller. Complex workflows? Extract to UseCases.

**Q: What about Models?**
A: Models stay in `app/Models`. They're shared across services. Pulse doesn't dictate data layer organization.

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
