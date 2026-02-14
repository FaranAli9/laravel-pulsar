# Pulsar

> An opinionated Laravel architecture for building modular, domain-driven applications at scale.

**Pulsar is an opinionated architecture tool.** It provides a strict and explicit approach to organizing Laravel applications using clean architecture, domain-driven design, and service-oriented patterns. This architecture works well for medium-to-large scale applications, multi-tenant SaaS platforms, and teams that benefit from enforced boundaries between business logic and delivery mechanisms. If you prefer Laravel's default structure or flexible, ad-hoc patterns, Pulsar may not be the right fit.

This README is intentionally **concise**.
It defines the **architectural contract**, not a tutorial.

---

## Table of Contents

- [Installation](#installation)
- [Architecture Overview](#architecture-overview)
- [Architecture Rules](#architecture-rules)
- [File Types](#file-types)
- [Commands Reference](#commands-reference)
- [Complete Example](#complete-example)
- [Contributing](#contributing)

---

## Installation

```bash
composer require faran/pulsar --dev
```

Generate your first service:

```bash
pulsar make:service Admin
```

---

## Architecture Overview

Pulsar organizes your Laravel application into **two complementary layers**.

- **Service Layer** — delivery and orchestration
- **Domain Layer** — business logic

---

### Service Layer

**Purpose:** HTTP delivery and application orchestration, scoped by consumer audience (Admin, Client, Internal).

```
app/Services/{Service}/
├── Providers/
│   ├── {Service}ServiceProvider.php
│   └── RouteServiceProvider.php
├── Routes/
│   └── api.php
└── Modules/{Module}/
    ├── Controllers/
    ├── Requests/
    ├── UseCases/
    └── Operations/
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

```
app/Domain/{Domain}/
├── Models/
├── Actions/
├── DTOs/
├── Policies/
├── Events/
├── Enums/
├── Exceptions/
└── Queries/
```

The Domain layer is **Laravel-first**:

- Uses Eloquent models
- Uses Laravel events
- Uses Laravel authorization
- Has zero dependency on Services

It is independent of HTTP, **not** independent of Laravel.

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

---

### Service Layer Call Graph

Controllers call **UseCases** only.
Only **UseCases** call Operations.
Multiple UseCases may call the same Operation.

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

---

### Return Types

Actions and Queries may return domain models, collections, primitives, or void.
They must never return HTTP or framework response objects.

---

### Anti-Patterns

- Fat Controllers containing business logic
- Controllers calling Operations directly
- Actions calling other Actions
- Operations emitting domain events
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
| UseCase    | Workflow orchestration        |
| Operation  | Reusable workflow fragment across UseCases (branching allowed; no transactions/events) |

### Domain Layer

| Type      | Purpose                   |
|-----------|---------------------------|
| Model     | Domain entity (Eloquent)  |
| Action    | Atomic business operation |
| DTO       | Data transfer             |
| Event     | Domain event              |
| Enum      | Domain state              |
| Exception | Business rule violation   |
| Query     | Read-only domain query    |

---

## Commands Reference

### Service Layer

- `make:service {name}`
- `make:controller {name} {module} {service} --resource`
- `make:request {name} {module} {service}`
- `make:use-case {name} {module} {service}`
- `make:operation {name} {module} {service}`

### Domain Layer

- `make:model {name} {domain}`
- `make:action {name} {domain}`
- `make:dto {name} {domain}`
- `make:policy {name} {domain}`
- `make:event {name} {domain}`
- `make:enum {name} {domain}`
- `make:exception {name} {domain}`
- `make:query {name} {domain}`

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
