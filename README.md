# Pulsar

> An opinionated Laravel architecture for building modular, domain-driven applications at scale.

**Pulsar is an opinionated architecture tool.** It provides a strict and explicit approach to organizing Laravel applications using clean architecture, domain-driven design, and service-oriented patterns. This architecture works well for medium-to-large scale applications, multi-tenant SaaS platforms, and teams that benefit from enforced boundaries between business logic and delivery mechanisms. If you prefer Laravel's default structure or flexible, ad-hoc patterns, Pulsar may not be the right fit.

This README is intentionally **concise**.
It defines the **architectural contract**, not a tutorial.

---

## Table of Contents

- [Installation & wiring](#installation--wiring)
- [Upgrading](#upgrading)
- [Architecture Overview](#architecture-overview)
- [Architecture Rules](#architecture-rules)
- [File Types](#file-types)
- [Commands Reference](#commands-reference)
- [Complete Example](#complete-example)
- [Contributing](#contributing)

---

## Installation & wiring

Pulsar requires PHP 8.3 or newer.

```bash
composer require faran/pulsar --dev
pulsar install
```

`pulsar install` generates `app/Providers/PulsarServiceProvider.php`, registers it in
`bootstrap/providers.php`, and re-establishes the Laravel conventions that move outside their
stock paths under `app/Pulsar`: policy resolution, event-listener discovery, and Artisan command
discovery. Command-directory globs are expanded before being passed to Laravel because
`withCommands()` accepts concrete directories and classes.

The installer is idempotent. It backs up `bootstrap/app.php` before writing, `--dry-run` prints
the complete diff without changing files, and `--force` restores the generated provider without
duplicating existing wiring. If a customized `Application::configure()` chain cannot be parsed
safely, Pulsar changes nothing and prints the two manual wiring calls.

The generated provider is the single place for Contract-to-adapter bindings, contextual
audience bindings, request/tenant-scoped services, non-resource gates, global authorization
hooks, and optional observers. Once wired, Laravel's `event:cache` and `optimize` commands remain
compatible with the explicit discovery paths and provider registration.

Then generate your first service:

```bash
pulsar make:service Admin
```

---

## Upgrading

Upgrading an existing application to v0.3.0 requires an Operation method rename and may require
new application wiring. Follow the ordered migration and codemod recipes in
[UPGRADING.md](UPGRADING.md). Release details are recorded in [CHANGELOG.md](CHANGELOG.md).

---

## Architecture Overview

Pulsar organizes your Laravel application into **three complementary layers**.

- **Service Layer** — delivery and orchestration
- **Domain Layer** — business logic
- **Infrastructure Layer** — outbound adapters that implement Domain Contracts

Pulsar places its generated architecture under `app/Pulsar` so the application's architectural boundary is explicit. Everything inside this directory follows Pulsar's placement, dependency, and transaction rules; everything outside it remains available for ordinary Laravel code that is not governed by Pulsar.

A type belongs inside `app/Pulsar` when it calls, is called by, or implements another Pulsar type. Pure framework bootstrap and configuration that Laravel owns by convention—migrations, factories, seeders, `bootstrap/app.php`, `config/`, and `routes/`—stay in their stock Laravel locations.

---

### Service Layer

**Purpose:** Inbound delivery and application orchestration, scoped by consumer audience (Admin, Client, Internal).

Services live in `app/Pulsar/Services` because they are part of the Pulsar delivery layer. This
keeps audience-specific HTTP, CLI, queue, and scheduler entrypoints with their use cases and
reusable operations. Machine-driven entrypoints normally belong to an `Internal` service.

```
app/Pulsar/Services/{Service}/
├── Providers/
│   ├── {Service}ServiceProvider.php
│   └── RouteServiceProvider.php
├── Routes/
│   └── api.php
└── Modules/{Module}/
    ├── Controllers/
    ├── Requests/
    ├── Resources/
    ├── UseCases/
    ├── Operations/
    ├── Jobs/
    └── Commands/
```

**A Service is:**

- A delivery boundary (Admin API, Client API)
- Scoped to a consumer audience
- A logical separation inside a single Laravel application

**A Service is NOT:**

- A microservice
- A bounded context
- A deployment unit
- An isolated database or schema

Services may share the same database, Domain layer, and deployment.

---

### Domain Layer

**Purpose:** Business logic independent of delivery concerns (HTTP, controllers).

Domains live in `app/Pulsar/Domain` because they represent business capabilities shared by all services. The Domain layer is reusable across delivery boundaries while remaining inside Laravel's autoloaded application namespace.

```
app/Pulsar/Domain/{Domain}/
├── Contracts/
├── Models/
├── Actions/
├── Queries/
├── DTOs/
├── ValueObjects/
├── Policies/
├── Events/
├── Listeners/
├── Notifications/
├── Mail/
├── Enums/
└── Exceptions/
```

The Domain layer is **Laravel-first**:

- Uses Eloquent models
- Uses Laravel events
- Uses Laravel authorization
- Has zero dependency on Services

It is independent of HTTP, **not** independent of Laravel.

---

### Infrastructure Layer

**Purpose:** Concrete outbound adapters for volatile framework and vendor concerns.

```
app/Pulsar/Infrastructure/{Area}/
└── {Adapter}.php
```

Areas group capabilities such as `Payments`, `Search`, `Storage`, `Messaging`, and `Time`.
Infrastructure adapters implement Domain Contracts and may depend on the framework or third-party
SDKs. They never import Services, UseCases, Actions, or Operations. Domain and Services depend on
the Contract; Laravel's container supplies the adapter.

The dependency rule is:

> Delivery points inward to Domain. Infrastructure points inward to Domain Contracts. Domain
> points at itself and its own Contracts. Nothing points at Delivery.

The consumer owns the port: when Billing needs a payment capability, Billing defines
`Domain/Billing/Contracts/PaymentGateway`; a concrete implementation such as
`Infrastructure/Payments/StripePaymentGateway` satisfies it.

---

## Architecture Rules

### Shared Vocabulary

| Term        | Meaning                                   |
|-------------|-------------------------------------------|
| **Service** | Delivery boundary for a consumer audience |
| **Module**  | Feature slice within a Service            |
| **Domain**  | Business capability (Order, Catalog)      |
| **UseCase** | Application workflow                      |
| **Operation** | Reusable workflow fragment for UseCases |
| **Action**  | Atomic domain operation                   |
| **Job** | Queued workflow entrypoint in a Service module |
| **Command** | CLI/scheduler workflow entrypoint in a Service module |
| **Listener** | Domain reaction; queued listeners may enter one UseCase |
| **Contract** | Domain-owned capability boundary         |
| **Adapter** | Infrastructure implementation of a Contract |

---

### Inbound Adapter Rule and Call Graphs

Every inbound adapter is thin. It may validate, authorize, establish actor/tenant/correlation
context, and call exactly one UseCase (or one Query for a read-only endpoint). It owns no
transaction and contains no branching business logic. Jobs carry IDs, DTOs, or Value Objects,
never Eloquent models, and retryable handlers are idempotent.

```text
HTTP:      Request   → Controller → UseCase → {Actions, Operations, Queries, Events}
Artisan:   Console   → Command    → UseCase → ...
Queue:     Worker    → Job        → UseCase → ...
Scheduler: Schedule  → Command|Job → UseCase → ...
Event:     UseCase   → Event → Listener → {Contract side effect | Notification/Job | (queued) UseCase}
```

Synchronous Listeners never call a UseCase. A queued Listener implements `ShouldQueue` and
`ShouldQueueAfterCommit`, may call one UseCase, and must be idempotent with a reentrancy guard.
Only UseCases call Operations; multiple UseCases may reuse an Operation.

---

### Operations

Operations are reusable workflow fragments shared across UseCases.
They may include sequencing and conditional branching decisions.
They must never own transactions or emit domain events.

### Cross-Domain Logic

Cross-domain coordination belongs in **UseCases**, never in Actions.

---

### Transactions

**UseCases own all transaction boundaries.**

Actions and Operations must never manage transactions.

### Event Delivery Guarantees

UseCases create and dispatch immutable domain events. The four tiers are deliberately distinct:

| Tier | Mechanism | Guarantee | Use when |
|------|-----------|-----------|----------|
| 1. Sync in-process | Plain `event()` | Immediate; fires before commit if dispatched inside a transaction | Pure in-memory reactions only |
| 2. After-commit | Event implements `ShouldDispatchAfterCommit` | Discarded on rollback; can be lost if the process crashes after commit | Default for domain events |
| 3. Queued after-commit | Listener uses `ShouldQueue` + `ShouldQueueAfterCommit`, or Job uses `afterCommit()` | Worker delivery is at-least-once; idempotency required; enqueue is not atomic with the write | External calls, expensive reactions, cross-aggregate work |
| 4. Outbox/inbox | Outbox row written in the business transaction and relayed | Durable across crashes; inbox/idempotency provides effectively-once effects | Integration events needing guaranteed external delivery |

### Generated Method Conventions

| Type | Public workflow method |
|------|------------------------|
| Action | `execute` |
| Operation | `execute` |
| Query | `execute` |
| UseCase | `execute` |

---

### Return Types

Actions and Queries may return domain models, collections, primitives, or void.
They must never return HTTP or framework response objects.

### Contracts and Adapters

Contracts use capability names without a `Contract` or `Interface` suffix, such as
`PaymentGateway` or `Clock`. A Contract signature may passively reference DTOs, Enums, Value
Objects, Models, Events, other Contracts, or Laravel contracts. It must not behaviorally invoke
a UseCase, Action, or Operation, and it must never import Services or concrete Infrastructure.

Concrete adapters live under `Infrastructure/{Area}` and translate vendor or framework errors
into Domain exceptions at that boundary. Bind each Contract to its adapter in
`PulsarServiceProvider::register()`. Use `scoped()` instead of `singleton()` when an adapter
holds request- or tenant-lifetime state, and make retryable side effects idempotent.

### Optional Architecture Preset

Pulsar ships a recommended, opt-in Pest architecture test. It keeps Domain independent of
Services, Infrastructure outside workflows and delivery, and Controllers limited to Domain
DTOs plus UseCases:

```bash
mkdir -p tests/Arch
cp vendor/faran/pulsar/presets/PulsarArchitectureTest.php tests/Arch/PulsarArchitectureTest.php
```

The preset is not installed automatically and is not required when an application already
enforces equivalent dependency rules.

---

### Anti-Patterns

- Fat Controllers containing business logic
- Controllers calling Operations directly
- Actions calling other Actions
- Events emitted outside UseCases; Actions and Operations never emit events
- Transactions inside Actions or Operations
- UseCases calling other UseCases

If you feel tempted to do any of the above, the architecture is being violated.

---

### Why Pulsar Exists

Pulsar optimizes for:

- Team-scale clarity
- Predictable code placement
- Easier PR reviews
- Safer refactors
- Fewer "where does this logic go?" debates

Flexibility is traded for consistency — deliberately.

---

## File Types

### Service Layer

| Type       | Purpose                       |
|------------|-------------------------------|
| Service    | Bootstrap a delivery boundary |
| Controller | HTTP handling only            |
| Request    | Validation and authorization  |
| Resource   | HTTP response shaping from Models, DTOs, and Value Objects |
| UseCase    | Workflow orchestration        |
| Operation  | Reusable workflow fragment across UseCases (branching allowed; no transactions/events) |
| Job        | Idempotent queued adapter that calls one UseCase |
| Command    | Authorized CLI/scheduler adapter that calls one UseCase |

### Domain Layer

| Type      | Purpose                   |
|-----------|---------------------------|
| Model     | Domain entity (Eloquent)  |
| Action    | Atomic business operation |
| DTO       | Data transfer             |
| Policy    | Model-aware authorization with default-deny methods |
| Event     | Immutable, versioned domain fact dispatched after commit by default |
| Listener  | Synchronous side effect or queued, idempotent workflow reaction |
| Notification | Domain outbound notification carrying DTO/VO data |
| Mailable  | Domain outbound mail representation carrying DTO/VO data |
| Enum      | Domain state              |
| Value Object | Immutable validated domain primitive |
| Exception | Business rule violation   |
| Query     | Read-only domain query    |
| Contract  | Domain-owned port for a stable capability |

### Infrastructure Layer

| Type | Purpose |
|------|---------|
| Adapter | Concrete framework/vendor implementation of a Domain Contract |

---

## Commands Reference

| Command | Arguments and options |
|---------|-----------------------|
| `install` | `[--dry-run] [--force]` |
| `make:service` | `{name}` |
| `make:controller` | `{name} {module} {service} [--resource]` |
| `make:request` | `{name} {module} {service}` |
| `make:resource` | `{name} {module} {service} [--collection]` |
| `make:use-case` | `{name} {module} {service}` |
| `make:operation` | `{name} {module} {service}` |
| `make:job` | `{name} {module} {service}` |
| `make:command` | `{name} {module} {service} [--signature={signature}]` |
| `make:domain` | `{name}` |
| `make:contract` | `{name} {domain}` |
| `make:model` | `{name} {domain}` |
| `make:action` | `{name} {domain}` |
| `make:dto` | `{name} {domain}` |
| `make:policy` | `{name} {domain} [--model={model}]` |
| `make:event` | `{name} {domain}` |
| `make:listener` | `{name} {domain} [--event={event}] [--queued]` |
| `make:notification` | `{name} {domain}` |
| `make:mailable` | `{name} {domain}` |
| `make:enum` | `{name} {domain}` |
| `make:value-object` | `{name} {domain}` |
| `make:exception` | `{name} {domain}` |
| `make:query` | `{name} {domain}` |
| `make:adapter` | `{name} {area} [--contract={FQCN\|name}] [--domain={domain}]` |
| `publish:context` | `[--force] [--path={path}]` |
| `publish:skill` | `[--force] [--path={path}]` |
| `ping` | — |

---

## Complete Example

See project documentation for a full end-to-end example covering Domain and Service layers.

---

## Contributing

Contributions are welcome. Please follow the architecture rules and existing conventions.

---

## License

MIT License.

---

## Credits

Built with ❤️ by Faran Ali

Inspired by:

- Lucid Architecture
- Clean Architecture
- Domain-Driven Design
