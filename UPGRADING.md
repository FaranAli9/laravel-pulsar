# Upgrading Pulsar

## v0.3.0

Pulsar v0.3.0 requires PHP 8.3 or newer. Upgrade the package, then apply the steps below in
order. Review every generated diff and run the consuming application's test suite before
deploying.

### 1. Rename Operation `handle()` methods to `execute()`

This is the one hard-breaking generated-code convention in v0.3.0. Existing applications must
rename both Operation declarations and their call sites.

Inventory Operation declarations first:

```bash
rg -n 'public function handle\(' app/Pulsar/Services --glob '**/Operations/*.php'
```

Rename those declarations:

```bash
rg -l -0 'public function handle\(' app/Pulsar/Services --glob '**/Operations/*.php' \
  | xargs -0 perl -pi -e 's/public function handle\(/public function execute\(/g'
```

Then inventory call sites and update only calls whose receiver is an Operation:

```bash
rg -n -- '->handle\(' app/Pulsar tests
```

For codebases that consistently suffix injected variables with `Operation`, this codemod covers
the common call-site form:

```bash
rg -l -0 -- '\$[A-Za-z_][A-Za-z0-9_]*Operation->handle\(' app/Pulsar tests \
  | xargs -0 perl -pi -e 's/(\$[A-Za-z_][A-Za-z0-9_]*Operation)->handle\(/$1->execute(/g'
```

Review the diff, run the application test suite, and verify that no old Operation declarations
remain:

```bash
rg -n 'public function handle\(' app/Pulsar/Services --glob '**/Operations/*.php'
```

The final command should produce no output.

### 2. Install Pulsar's Laravel wiring

Preview the complete installer diff before changing the application:

```bash
vendor/bin/pulsar install --dry-run
```

Then apply it:

```bash
vendor/bin/pulsar install
```

The installer:

- generates `app/Providers/PulsarServiceProvider.php`;
- registers the provider in `bootstrap/providers.php`;
- patches `bootstrap/app.php` to discover Pulsar Domain Listeners and Service Commands.

It is idempotent and backs up `bootstrap/app.php` before writing. If the bootstrap chain is too
customized to patch safely, it changes nothing and prints the manual wiring calls. Put
Contract-to-adapter bindings, contextual bindings, non-resource gates, global authorization
hooks, and optional observers in the generated provider.

### 3. Move concrete Contract implementations to Infrastructure

The Domain owns the port, while Infrastructure owns its framework- or vendor-specific
implementation. For example, keep the payment-gateway Contract here:

```text
app/Pulsar/Domain/Billing/Contracts/PaymentGateway.php
```

Before:

```php
<?php

namespace App\Pulsar\Domain\Billing\Services;

use App\Pulsar\Domain\Billing\Contracts\PaymentGateway;

final class StripePaymentGateway implements PaymentGateway
{
    // Stripe-specific implementation...
}
```

After moving the class to `app/Pulsar/Infrastructure/Payments/StripePaymentGateway.php`:

```php
<?php

namespace App\Pulsar\Infrastructure\Payments;

use App\Pulsar\Domain\Billing\Contracts\PaymentGateway;

final class StripePaymentGateway implements PaymentGateway
{
    // Stripe-specific implementation...
}
```

Add the binding to `app/Providers/PulsarServiceProvider.php`:

```php
use App\Pulsar\Domain\Billing\Contracts\PaymentGateway;
use App\Pulsar\Infrastructure\Payments\StripePaymentGateway;

public function register(): void
{
    $this->app->bind(PaymentGateway::class, StripePaymentGateway::class);
}
```

For a new adapter, Pulsar can create the Infrastructure shell:

```bash
vendor/bin/pulsar make:adapter StripePaymentGateway Payments \
  --contract=PaymentGateway \
  --domain=Billing
```

Domain and Service code should continue to depend on `PaymentGateway`, never directly on
`StripePaymentGateway`.

### 4. Optionally adopt the new Event shape

Existing Events continue to work. New generated Events are immutable, versioned, and dispatched
after commit by default:

```php
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class OrderPaid implements ShouldDispatchAfterCommit
{
    public const int VERSION = 1;

    public function __construct(public int $orderId) {}
}
```

Adopt this shape where constructor state can be readonly. `ShouldDispatchAfterCommit` prevents
rollback leaks and read-before-commit races, but an in-memory event can still be lost if the
process crashes after commit; use a transactional outbox when durable external delivery is
required. See the [four event-delivery tiers](README.md#event-delivery-guarantees).

### Compatibility summary

Old generated applications run unchanged until they adopt the new types and discovery paths.
Only the Operation `handle()` to `execute()` rename is strictly required for consistency.
Running `pulsar install` is required when adopting Pulsar's new provider-backed policy,
Listener, Command, and Contract-to-adapter wiring.
