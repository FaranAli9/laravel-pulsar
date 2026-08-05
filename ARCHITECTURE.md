# Pulsar Architecture

This document explains **why** Pulsar is shaped the way it is. The user-facing rules live in
[`README.md`](README.md) and in the published references (`pulsar publish:context` →
`PULSAR.md`, `pulsar publish:skill` → the Claude Code skill). This document is the design
rationale behind those rules — read it when you are changing Pulsar itself, or deciding whether a
new concept belongs in the architecture.

## Philosophy

Pulsar is **deliberately opinionated**. It trades flexibility for consistency so that a team gets
predictable placement, easier reviews, safer refactors, and fewer recurring "where does this go?"
debates. A single obvious home for each concept is worth more, at team scale, than the freedom to
place it five different ways.

Two principles constrain every decision:

- **Laravel-first, not framework-independent.** Pulsar's Domain layer freely uses Eloquent,
  Laravel events, Laravel authorization, and the container. It is independent of *delivery*
  (HTTP, CLI, queue) — not of the framework. This is a deliberate rejection of hexagonal purity:
  the goal is a clear, testable structure for Laravel apps, not portability to another framework.
- **`app/Pulsar` is the architecture boundary.** Everything inside it follows Pulsar's placement,
  dependency, and transaction rules. Everything outside remains ordinary, ungoverned Laravel. A
  type belongs *inside* when it participates in a Pulsar dependency edge; it stays *outside* when
  it is framework bootstrap or configuration Laravel's tooling must own by convention
  (`bootstrap/app.php`, `config/`, `database/migrations`, `database/factories`).
  Stock Laravel directories may coexist with `app/Pulsar` indefinitely. This is an architecture
  boundary, not a demand for a big-bang directory migration.

**Non-goals.** Framework independence; supporting every Laravel class type; replacing Laravel's
discovery with something its tooling cannot see; being a runtime library (Pulsar is a build-time
generator with a deliberately tiny dependency surface). Pulsar is a poor fit for small apps or
teams that prefer Laravel's default, flexible structure — and that is stated up front.

## The three layers

```
app/Pulsar/
├── Services/{Service}/Modules/{Module}/   Delivery, scoped by consumer audience (Admin, Client, Internal)
├── Domain/{Domain}/                        Laravel-aware business capability, shared across audiences
└── Infrastructure/{Area}/                  Outbound adapters implementing Domain Contracts
```

The dependency rule, and the whole point of the structure:

> **Delivery points inward to Domain. Infrastructure points inward to Domain Contracts. Domain
> points only at itself and its own Contracts. Nothing points at Delivery.**

- A **Service** is a *consumer/audience* boundary (Admin browser/API, Client API, Internal/machine). It is
  **not** a microservice, bounded context, deployment unit, database, or schema. Modeling audience
  is what lets the same Domain capability be delivered differently to different consumers without
  duplication.
- The **Domain** layer holds the business capability once, shared by every Service. It is
  Laravel-aware (Eloquent models are the aggregates) but knows nothing about HTTP or any specific
  audience.
- The **Infrastructure** layer exists so the Domain can depend on a *port* (a Contract) for a
  volatile outbound concern (payments, search, storage, clock, message bus) while the concrete,
  vendor-coupled implementation lives outside the Domain. This keeps vendor SDKs out of the Domain
  and makes the boundary swappable and testable. It is the completion of the Contracts decision —
  a port is meaningless without a sanctioned home and binding for its implementation.

## Type taxonomy and why each exists

**Application orchestration (Service layer, module-scoped)**

- **UseCase** — owns a workflow. It is the **only** type that opens a transaction and the **only**
  type that emits domain events. It may coordinate Actions, Operations, Queries, and Events. It
  **never calls another UseCase** — shared workflow logic is extracted to an **Operation**, not
  duplicated and not hidden in an Action.
- **Operation** — a reusable workflow *fragment* called only by UseCases. It may sequence and
  branch, but it never owns a transaction and never emits events. Operations exist precisely so
  that shared multi-step logic has a home that isn't a UseCase (which would create UseCase→UseCase
  coupling) and isn't an Action (which must stay atomic).
- **Controller / Job / Console Command** — inbound delivery adapters (HTTP / queue / CLI). See the
  adapter rule below.

**Business capability (Domain layer)**

- **Action** — an atomic business operation over (typically) a single aggregate. Never calls
  another Action, never calls a Query (the UseCase or Operation passes in the data it needs), never
  emits events, never owns a transaction.
- **Query** — read-only. Encapsulates a complex read.
- **Contract** — a domain-owned *port* for a stable capability or an outbound dependency.
- **Model, DTO, Value Object, Enum, Domain Exception, Event, Listener, Policy, Notification,
  Mailable** — the supporting domain vocabulary.

**Outbound (Infrastructure layer)**

- **Adapter** — a concrete implementation of a Domain Contract. Translates vendor/framework errors
  into Domain exceptions at the boundary.

### Method convention

Every workflow-bearing type (Action, Operation, Query, UseCase) exposes a single **`execute()`**
method. One verb across the codebase removes a category of "what did they call it here?" friction.
The constructor carries dependencies; `execute()` carries runtime data — which keeps instances
free of request/tenant state and therefore Octane-safe.

## Dependency rules: passive vs behavioral

The dependency graph distinguishes two kinds of edge:

- A **passive type dependency** — a signature *mentions* a type (a Controller builds a Domain DTO
  and passes it to a UseCase; an Action accepts an Enum). These are allowed across boundaries where
  a behavioral call would not be.
- A **behavioral dependency** — one type *invokes* another's behavior. These are what the rules
  restrict (Action→Action, UseCase→UseCase, Operation→Operation, Action→Query, Controller→Operation
  are all forbidden).

This distinction is why a Controller may import a Domain DTO (passive) while remaining forbidden
from calling Queries, Operations, or Actions. The shipped architecture preset enforces the
structural core of this (Domain ↛ Services, Infrastructure ↛ workflows/delivery, Controllers depend
only on delivery types, Domain value types, and UseCases); the remaining rules are upheld by
convention and review.

## The adapter rule (all entrypoints are thin)

Every Controller method calls exactly one UseCase. Controllers never call Queries directly. Queue
Jobs and Artisan Commands also validate, authorize, establish context, and call exactly one
UseCase. A read-only UseCase may call one or more Queries and need not open a transaction. After
the UseCase returns, a Controller may perform delivery-only response assembly. The adapter owns no
transaction and contains no branching business logic.

This uniformity is deliberate. Pulsar is agentic-first: a mechanically enforceable call graph is
more valuable than avoiding a pass-through UseCase. Reads, writes, and pages without Domain data
follow the same Controller → UseCase rule, so an agent never classifies endpoint complexity or
chooses an application entrypoint type.

Because a Job or Command has no HTTP request, it re-establishes actor/tenant context from its
payload and authorizes explicitly — **authorization never lives only in a Form Request.** Jobs
carry IDs / DTOs / Value Objects, never Eloquent models, across the queue boundary (a serialized
model is only a key re-fetched on the worker — a staleness trap), and are idempotent because queues
are at-least-once.

## Browser and Inertia delivery

HTTP is not synonymous with JSON. A browser route needs Laravel's `web` middleware for cookies,
session state, flashed errors, request-forgery protection, and route bindings. `make:service --web`
therefore adds a separate, unprefixed `Routes/web.php`; it does not put browser traffic below the
existing `/api/{service}` prefix. This also preserves URLs and route names while a stock Laravel
application migrates incrementally.

Inertia remains application-owned and optional. A page Controller owns the component name,
top-level props, redirects, and Inertia's lazy/deferred/optional wrappers. The Controller owns
top-level response assembly; a Service Resource owns reusable field-level shaping.
`HandleInertiaRequests` owns sparse cross-page shared data. Queries and UseCases return
delivery-neutral values and never depend on Inertia or return Resources, redirects, or responses.

Invalid Inertia form submissions use Laravel's normal redirect-and-flash validation flow. The
generated Form Request needs no override: on a route using `web`, Laravel redirects back and
flashes the error bag, and Inertia shares it as page errors. Successful mutations normally redirect
to a GET page. Partial reload selection remains a Controller concern; Domain Queries do not inspect
Inertia headers or prop names.

## Events and the four-tier delivery rule

Domain events are immutable facts about something that already happened. They are **emitted by
UseCases only**, are `final readonly`, carry a `VERSION`, and hold IDs/scalars/DTOs/Value Objects —
never Eloquent models across a queued or durable boundary.

Pulsar is honest about what each delivery mechanism actually guarantees:

| Tier | Mechanism | Guarantee |
|---|---|---|
| 1. Synchronous | plain `event()` | Same request; **fires before commit if inside a transaction** — avoid for committed-fact side effects |
| 2. After-commit *(default)* | Event implements `ShouldDispatchAfterCommit` | Runs only after commit; **discarded on rollback**; still in-memory — **lost if the process crashes between commit and dispatch** |
| 3. Queued after-commit | queued listener / `->afterCommit()` job | After commit, on a worker, **at-least-once** (idempotency required); not atomic with the DB write |
| 4. Outbox/inbox | event row written in the same transaction, relayed | **Durable**; for integration events needing guaranteed external delivery |

The generated Event stub is tier 2 by default. Tier 4 is opt-in and never prescribed by default —
Pulsar does not pretend after-commit dispatch is durable.

## Authorization

Authorization ("may this actor do this?") lives in Policies and Gates and is enforced at the
adapter boundary for **every** audience, not only HTTP. Domain invariants ("is this operation
valid?") live inside Actions/UseCases regardless of caller. Tenant isolation is enforced
independently of both. Ability names come from per-domain backed Enums (passed to the Gate as
`->value`) rather than free-floating magic strings.

## Discovery and bootstrap

Relocating classes under `App\Pulsar` **breaks** Laravel's convention-based auto-discovery for
**Policies, Event Listeners, and Artisan Commands** — those conventions assume the default
`app/Policies`, `app/Listeners`, `app/Console/Commands` locations. Rather than fight the framework,
Pulsar re-establishes discovery *explicitly*:

- `pulsar install` generates a `PulsarServiceProvider` (Contract→adapter bindings, `scoped()` for
  request/tenant-lifetime services, `Gate::guessPolicyNamesUsing` mapping
  `Domain\{D}\Models\{Model}` → `Domain\{D}\Policies\{Model}Policy`, gates and admin hooks) and
  idempotently patches `bootstrap/app.php` to add `->withEvents(discover: […])` for listeners and a
  glob-expanded `->withCommands([…])` for commands.
- Each generated Service provider remains explicit application wiring. `make:service` prints the
  class that must be added to `bootstrap/providers.php`; it does not silently mutate bootstrap
  state.
- The patcher is idempotent, backs up before writing, supports `--dry-run`, and refuses to touch a
  `bootstrap/app.php` whose shape it cannot parse (printing manual instructions instead of
  corrupting it).

Everything else (Jobs, middleware, container bindings, Form Requests, API Resources, route-model
binding, Notifications, Mailables) is container/dispatch-resolved and needs no discovery, so it
works under relocation as long as PSR-4 autoloads `App\Pulsar`.

## Stable identifiers

Closed, owned sets are **Enums** (business states, event names, abilities — per-domain, never a
global "god enum"). Open or environment-varying values are **config** (queue names/connections).
Framework-string APIs (command signatures, route names) keep their strings. Persisted and protocol
identifiers are immutable and never renamed casually; human labels live in `label()` methods,
separate from the machine value.

## Key decisions on record

- **Three layers, not two.** A Contract without a sanctioned implementation home and binding is
  incomplete; Infrastructure is that home. Rejected: keeping implementations in ungoverned stock
  Laravel paths (loses the boundary) or mixing ports and adapters inside one Domain (blurs it).
- **Contracts use capability names, no suffix** (`PaymentGateway`, not `PaymentGatewayContract`) —
  matching `Illuminate\Contracts`. The `Contracts/` directory already signals intent; the generator
  strips a trailing `Contract`/`Interface` if one is typed.
- **The consumer owns the port.** The Domain that needs a capability defines the Contract in its own
  `Contracts/`; Infrastructure implements it.
- **`execute()` everywhere** for workflow types — one verb, less friction.
- **Runtime floor `php ^8.3`** — the modern dev toolchain (Pest 4, current Symfony Console) requires
  it, and Laravel 13 already requires 8.3; supporting older PHP for a build-time generator was not
  worth the split toolchain.
