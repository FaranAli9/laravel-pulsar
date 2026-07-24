# RFC: Pulsar Architecture Completion

**Status:** Draft for maintainer review · **Target version:** `v0.3.0` · **Pass:** 1 of 2 (design only — no code changes this pass)

---

## Context

Pulsar is an opinionated Laravel code-generation CLI that scaffolds a vertical-slice
architecture under `app/Pulsar`. It already ships **13 `make:*` generators plus 2 `publish:*`
generators**, but its public architecture stops short of the concepts real Laravel apps need
every day: it names Policies, Events, and Listeners as domain citizens without defining how
they are registered, discovered, or invoked; it has no home for Listeners, Jobs, Console
Commands, Notifications, Mailables, or Contracts; and its published guidance contradicts its
own generated stubs in several places. Meanwhile several "security-critical" safeguards
(`validateName`, `sanitizeDirectoryName`) are **dead code** that never runs.

This RFC completes the architecture. One decision is fixed by the maintainer: **Contracts
become a first-class Pulsar Domain type.** Three further forks were decided during review:

1. **A third `Infrastructure` layer** is added to hold concrete implementations of Domain
   Contracts (outbound adapters). Domain stays pure of concrete infrastructure.
2. **Full scope** — this effort delivers Contracts + drift/bug fixes + the events/authorization
   wiring + the full non-HTTP entrypoint suite (Jobs, Console Commands, Notifications,
   Mailables, Listeners, Value Objects).
3. **Contracts use no suffix** — capability names (`PaymentGateway`, `Clock`), matching
   Laravel's own `Illuminate\Contracts`.

The goal is that after maintainer approval, pass 2 can implement without reopening any
placement, dependency, lifecycle, or generator decision.

> **Terminology note:** "Pulsar" (`Faran\Pulsar\...`) is the *package*. "`App\Pulsar\...`" is
> the *generated architecture inside a consuming Laravel app*. All paths below are
> consuming-app paths unless prefixed with `src/` (package source).

---

## 1. Executive recommendation

Pulsar becomes a **three-layer** architecture:

- **`app/Pulsar/Services/{Service}/Modules/{Module}/...`** — inbound delivery adapters, scoped
  by consumer audience (Admin, Client, Internal). Controllers, Form Requests, API Resources,
  Middleware, **Jobs (workflow entrypoints)**, and **Console Commands** live here. They
  translate a transport event into a UseCase call and translate the result back to the
  transport. They own no business logic and no transactions.
- **`app/Pulsar/Domain/{Domain}/...`** — Laravel-aware business capability shared across
  audiences. UseCases (orchestration + transactions), Operations, Actions, Queries, Models,
  DTOs, Enums, Value Objects, Events, **Listeners**, **Notifications/Mailables**, Policies,
  Domain Exceptions, and **Contracts** (the ports it depends on).
- **`app/Pulsar/Infrastructure/{Area}/...`** — **NEW** outbound adapters: concrete
  implementations of Domain Contracts (payment gateways, search clients, clocks, file
  storage, message buses). Depends on Domain Contracts + framework + third-party SDKs; is
  depended on by nobody except the container binding.

The dependency rule, memorable enough for the README, becomes:

> **Delivery (Services) points inward to Domain. Infrastructure points inward to Domain
> Contracts. Domain points at nothing but itself and its own Contracts. Nothing points at
> Delivery.**

The most important decisions, and why they follow from Pulsar's philosophy:

- **Contracts + Infrastructure together.** Pulsar's stated non-goal is framework independence
  ("Laravel-first, not framework-independent"), so a Contract is not about hexagonal purity —
  it exists to create a *testable, swappable boundary* around volatile outbound concerns and
  to keep Domain free of vendor SDKs. That boundary is meaningless without a sanctioned
  implementation home and binding mechanism, so the third layer is the completion of the
  Contracts decision, not a separate feature.
- **Explicit registration over auto-discovery.** Relocating classes to `App\Pulsar` **breaks**
  Laravel's convention-based discovery for Policies, Event Listeners, and Artisan Commands
  (§4). Pulsar therefore generates a `PulsarServiceProvider` that re-establishes these
  conventions deterministically. This is squarely in Pulsar's "predictable placement, fewer
  debates" philosophy: discovery is centralized and cache-friendly, not scattered.
- **Events are committed facts emitted by UseCases only, after commit by default.** This
  resolves the sharpest correctness gap in the current docs (§3) with a four-tier honesty
  rule about delivery guarantees.
- **Adapters stay thin; business logic cannot hide.** Every non-HTTP entrypoint (Job, Command,
  Listener) is a delivery adapter that calls a UseCase — never an Action/Operation directly,
  never owning a transaction. This is the same rule already applied to Controllers, extended
  to machine/queue/scheduler audiences.
- **Fix the foundation first.** Wiring the dead-code validators and correcting the
  stub/documentation contradictions is prerequisite, not optional — new generators built on a
  broken base inherit the break.

---

## 2. Reconstructed Pulsar philosophy

**Principles (documented intent, verified against the repo):**

1. Opinionated: flexibility is traded for consistency *deliberately* (README:188).
2. `app/Pulsar` is the explicit architecture boundary; inside it, Pulsar's placement/
   dependency/transaction rules apply; outside it is ordinary ungoverned Laravel (README:45).
3. Two layers today: Services (delivery, per audience) and Domain (Laravel-aware business).
4. A Service is a **consumer/audience** boundary (Admin/Client/Internal), *not* a
   microservice, bounded context, database, or deployment unit.
5. Controllers → UseCases only. UseCases orchestrate + own transactions, may coordinate
   Actions/Operations/Queries/Events, and never call other UseCases. Operations are reusable
   fragments called only by UseCases, never owning transactions or emitting events. Actions
   are atomic. Queries are read-only. Domain must not depend on Services.
6. **Laravel-first, not framework-independent** (README:111) — Domain freely uses Eloquent,
   events, authorization. This is the single most load-bearing principle for the new
   decisions: it means Notifications, Mailables, Policies, and Events legitimately live *in*
   Domain, and Contracts exist for *volatility/testability*, not for framework abstraction.

**Recommended clarifications (new, to be adopted):**

- **The inside-vs-outside decision rule** (currently absent): *A type belongs inside
  `app/Pulsar` when it participates in a Pulsar dependency edge — it calls, or is called by, a
  Pulsar type, or implements a Pulsar Contract. A type stays in stock Laravel when it is pure
  framework bootstrap/configuration that Laravel's tooling must own by convention
  (migrations, factories, seeders, `bootstrap/app.php`, `config/`, `routes/`).*
- **The adapter rule** (generalizes the controller rule): *Any entrypoint — HTTP, CLI, queue,
  scheduler, event — is a thin adapter. It may validate, authorize, establish context, and
  call exactly one UseCase (or, for read-only endpoints, one Query). It owns no transaction
  and contains no branching business logic.*
- **The port-ownership rule:** *The consumer of a capability owns its Contract, in its own
  Domain's `Contracts/`. Implementations live in Infrastructure.*

**Non-goals (explicit):** framework independence; supporting every Laravel class type;
replacing Laravel discovery with something Laravel tooling cannot see; being a runtime library
(Pulsar is a build-time generator with a deliberately tiny dependency surface).

---

## 3. Repository audit

### 3.1 What Pulsar actually is (confirmed)

A standalone **Symfony Console** CLI. `composer.json` runtime require is only `php ^8.2` +
`symfony/console ^7.2|^8.0`; require-dev is only `pestphp/pest ^4.4`. **No `laravel/framework`,
no `illuminate/*`, no `orchestra/testbench`** in Pulsar's own dependencies (confirmed against
`composer.lock`). There is no `extra.laravel.providers` block — Pulsar is never registered as a
Laravel provider. Version `0.2.0` is hardcoded in exactly one place (`bin/pulsar:38`) and in the
git tag; `composer.json` has no `version` field. Package namespace is `Faran\Pulsar\`.

**Runtime PHP vs dev PHP.** Pulsar's end-user runtime support remains `php ^8.2`, verified by
the runtime compatibility job on PHP 8.2 and 8.3 using lowest supported runtime dependencies.
The dev/CI toolchain floor is PHP 8.4 because Pest 4 and the locked Symfony Console v8 require
a newer PHP. A library's lockfile is not consumed by downstream installations, so this modern
dev lock does not narrow the package's advertised runtime support.

### 3.2 Generators that exist today (16 commands, `bin/pulsar:41-62`)

| Command | Generator | Output path | Stub |
|---|---|---|---|
| `make:service` | ServiceGenerator | `Services/{Service}/{Providers,Routes,Modules}` | inline heredoc (**bug**) |
| `make:controller` (`-r`) | ControllerGenerator | `Services/{S}/Modules/{M}/Controllers/{N}.php` | `controller-plain` / `controller-resource` |
| `make:request` | RequestGenerator | `.../Requests/{N}.php` | `request` |
| `make:use-case` | UseCaseGenerator | `.../UseCases/{N}.php` | `use-case` |
| `make:operation` | OperationGenerator | `.../Operations/{N}.php` | `operation` (**`handle()` drift**) |
| `make:model` | ModelGenerator | `Domain/{D}/Models/{N}.php` | `model` |
| `make:action` | ActionGenerator | `Domain/{D}/Actions/{N}.php` | `action` |
| `make:dto` | DtoGenerator | `Domain/{D}/DTOs/{N}.php` | `dto` (readonly ✅) |
| `make:policy` | PolicyGenerator | `Domain/{D}/Policies/{N}.php` | `policy` (**empty stub**) |
| `make:event` | EventGenerator | `Domain/{D}/Events/{N}.php` | `event` (**not readonly**) |
| `make:enum` | EnumGenerator | `Domain/{D}/Enums/{N}.php` | `enum` |
| `make:exception` | ExceptionGenerator | `Domain/{D}/Exceptions/{N}.php` | `exception` |
| `make:query` | QueryGenerator | `Domain/{D}/Queries/{N}.php` | `query` |
| `publish:context` | ContextGenerator | `PULSAR.md` | `context` (496 lines, verbatim) |
| `publish:skill` | SkillGenerator | `.claude/skills/pulsar/SKILL.md` | `skill` (312 lines, verbatim) |
| `ping` | — | — | — |

### 3.3 Confirmed defects and drift

| # | Finding | Evidence | Severity |
|---|---|---|---|
| D1 | `Generator::validateName()` and `sanitizeDirectoryName()` are **never called** — no name validation or path-traversal sanitization runs on any user input. CLAUDE.md calls `validateName` "Security-critical." | `Generator.php:152,198`; grep confirms zero callers | **High (security)** |
| D2 | Operation stub emits `public function handle()`; all published guidance defines/calls Operations via `execute()`. | `operation.stub:7` vs `context.stub:172,290`, `skill.stub:163` | High |
| D3 | Event stub is a plain (non-`readonly`) class; skill guide says "Event — Readonly properties." | `event.stub:5` vs `skill.stub:116` | High |
| D4 | Policy stub is an empty `class {N} {}` — no methods, no `bool` returns, no model awareness; not a "standard Laravel policy." | `policy.stub:5-8` vs `skill.stub:122` | High |
| D5 | Published `context.stub` ships `UpdateStockAction` (an **Action**) that emits `event(new ProductOutOfStock(...))`, contradicting "Events emitted by UseCases only." | `context.stub:151-153` | High |
| D6 | Events dispatched **inside** `DB::transaction()` in some examples, **after** in others; no `afterCommit`/rollback/queue guidance anywhere. | `context.stub:127,330` (inside) vs `context.stub:363` (after) | High |
| D7 | Listeners are used by the docs but have **no directory** in the layout and no `make:listener`; policy/event registration/discovery is undocumented. | `skill.stub:178`, `context.stub:388-397`; no `Listeners/` in layout | High |
| D8 | `ServiceGenerator` looks for stubs at `src/Generators/stubs/` (nonexistent), so it always uses inline heredocs; `service-provider.stub`/`route-service-provider.stub`/`routes-api.stub` are dead. | `ServiceGenerator.php:139,161,182` | Medium |
| D9 | Domain generators never validate the domain exists (they auto-create it); Service generators validate the service. Asymmetric, and a typo silently creates a new domain. | e.g. `ModelGenerator.php` (no `validateDomainExists`) | Medium |
| D10 | Controller CLI arg order (`name, module, service`) differs from constructor positional order (`name, service, module`); wired correctly but confusing API. | `MakeControllerCommand.php:57-59` vs `ControllerGenerator.php:38` | Low |
| D11 | CLAUDE.md documents `make:usecase`; real command is `make:use-case`. CLAUDE.md/AGENTS.md omit `publish:context`, `publish:skill`, `ping`. | CLAUDE.md vs `bin/pulsar:47,61-62` | Low |
| D12 | AGENTS.md is a byte-for-byte duplicate of CLAUDE.md (only title/audience differ) — duplicate source of truth. | AGENTS.md:5-129 | Low |
| D13 | Version string single-sourced only in `bin/pulsar`, absent from `composer.json`; commit `a666053` mentions "v2.0.0" while tags are v0.1.x/v0.2.0. | `bin/pulsar:38` | Low |
| D14 | `createRecursiveDirectories()`, `Finder::relativeFromReal()` dead code. | `Generator.php:52`, `Finder.php:157` | Low |

### 3.4 Test status (actually run)

`vendor/bin/pest` → **249 passed (315 assertions), 0 failures, exit 0.** `composer test:coverage`
→ **fails**: "No code coverage driver is available" (no xdebug/pcov). Only **3 of 15** generators
have dedicated tests (Operation, Context, Skill). **No** tests for Action, Controller, Dto, Enum,
Event, Exception, Model, Policy, Query, Request, Service, UseCase. **No** command-level (CLI)
tests, **no** architecture tests, **no** snapshot tests, **no** real-Laravel/Testbench
integration. `createMockLaravelApp()` writes a 4-file fake (`composer.json` + one-line `artisan`
+ empty dirs). Custom expectation `toHaveMethod` is defined (`Pest.php:49`) but never used.

### 3.5 Docs vs reality drift

- **No CI** (`.github/` does not exist); TESTING.md ships an aspirational GitHub Actions matrix
  (TESTING.md:444-475) as if configured. No PHPStan/Pint/Infection config present though
  referenced (TESTING.md:606-635).
- TESTING.md says "Pulse package" (stale rebrand, TESTING.md:2), claims both "100% coverage"
  (L43) and "~75% lines / 30% classes" (L331-334) in one file, and miscounts generators
  ("13 generators" vs 15 files).
- README omits `publish:*` and `ping`; keyword `artisan` and TESTING.md:613 ("Laravel 11")
  imply Artisan integration that does not exist.

**Fact vs inference:** All table rows above are confirmed by cited file:line or by executed
commands. Inference: that D1 is exploitable depends on how a consumer invokes the CLI, but the
absence of validation is a confirmed fact.

---

## 4. External research (Laravel 12.x / 13.x, primary sources)

Laravel 13 released 2026-03-17, min PHP 8.3, promoted as zero breaking changes to application
code. **For every mechanism below, 12.x and 13.x behavior is identical** — cite 12.x URLs and
swap `12.x`→`13.x`.

**The load-bearing fact — the Laravel 11+ "slim skeleton":** there is no `app/Http/Kernel.php`,
no `app/Console/Kernel.php`, and no default `EventServiceProvider`/`AuthServiceProvider`/
`RouteServiceProvider`. Registration now lives in **`bootstrap/app.php`** (middleware, routing,
commands, schedule, exceptions) and **`AppServiceProvider::boot()`** (gates, policies, event
bindings). Providers are listed in **`bootstrap/providers.php`**.
[Providers](https://laravel.com/docs/12.x/providers)

**What BREAKS when classes move to `App\Pulsar` (requires explicit registration):**

1. **Policy discovery.** Default convention: a `Policies` directory at or above the model's
   directory, `{Model}Policy` naming. For `App\Pulsar\Domain\Orders\Models\Order` this does not
   resolve to `App\Pulsar\Domain\Orders\Policies\OrderPolicy`. Fix with
   `Gate::guessPolicyNamesUsing(Closure)`, per-model `#[UsePolicy(...)]`, or explicit
   `Gate::policy()`. [Authorization](https://laravel.com/docs/12.x/authorization#registering-policies)
2. **Event/listener discovery.** ON by default in 11/12/13, scans `app/Listeners`. A custom
   namespace is not scanned unless you pass `->withEvents(discover: [...])` (globs allowed) in
   `bootstrap/app.php`, or register explicitly via `Event::listen`. **Correction:**
   `#[AsEventListener]` is a *Symfony* attribute — it does **not** exist in Laravel; do not
   design around it. [Events](https://laravel.com/docs/12.x/events#event-discovery)
3. **Artisan command discovery.** Default scans `app/Console/Commands`. Custom namespace needs
   `->withCommands([...dirs or classes])` in `bootstrap/app.php`.
   [Artisan](https://laravel.com/docs/12.x/artisan#registering-commands)

**Safe under relocation (imperative / container-resolved, no path discovery), given PSR-4
maps `App\Pulsar`:** Gates and `before/after` hooks; queued Jobs (dispatch + all contracts);
middleware; container bindings/contextual binding; Form Requests; API Resources; route-model
binding; Notifications; Mailables; broadcast channel classes.

**Transaction/async facts (drive §6.3):**
- `ShouldDispatchAfterCommit` (event) / `ShouldQueueAfterCommit` (listener) / `->afterCommit()`
  (job) / `'after_commit'=>true` (connection) defer until the outer transaction commits and are
  **discarded on rollback**. [Events](https://laravel.com/docs/12.x/events#dispatching-events-after-database-transactions)
- **Durability caveat:** after-commit deferral is **in-memory** — a crash between COMMIT and
  dispatch loses the event/job. It solves the read-your-writes race, not durability. Guaranteed
  delivery requires a transactional outbox you build yourself.
  [Queues](https://laravel.com/docs/12.x/queues#jobs-and-database-transactions)
- Queues are **at-least-once** (except SQS FIFO) → jobs must be idempotent. `SerializesModels`
  serializes **only the primary key** and re-fetches fresh on the worker (relationships dropped)
  — a Job captures identity, not a snapshot, so the worker sees the row's *current* state.
- Retry/backoff: `$tries`, `$backoff` (int|array|method), `retryUntil()` (precedence over
  `$tries`), `$maxExceptions`, `$timeout`. Uniqueness: `ShouldBeUnique` /
  `ShouldBeUniqueUntilProcessing`. Middleware: `WithoutOverlapping`, `RateLimited`. Batches/
  chains via `Bus`. `failed(Throwable)` after exhaustion.

**Octane hazard (independent of relocation):** singletons that capture request/tenant/config in
their constructor leak across requests (cross-tenant contamination). Use `scoped()` bindings for
request/tenant-lifetime services; resolve context via helpers/closures, never constructor
capture. [Octane](https://laravel.com/docs/12.x/octane#dependency-injection-and-octane)

**Scheduling** now lives in `routes/console.php` (`Schedule` facade) or `->withSchedule()`;
`withoutOverlapping`, `onOneServer`, `runInBackground`.
[Scheduling](https://laravel.com/docs/12.x/scheduling)

**Inspirations (used to illuminate, not replace):** Lucid (Services/Features/Jobs → Pulsar's
Services/UseCases/Operations) validates the audience-scoped delivery split; Clean Architecture's
dependency rule (dependencies point inward to abstractions) justifies Contracts-in-Domain +
Infrastructure-implements; DDD's Value Objects / Domain Events inform §6.3 and the VO decision —
but Pulsar deliberately keeps Eloquent models as its aggregate (Laravel-first), not pure POPOs.

---

## 5. Decision matrix

Classification key: **FC** = first-class Pulsar type (path rule + generator); **FC-nogen** =
first-class placement rule, no generator; **Stock** = governed stock Laravel path; **Ext** =
documented extension point (no generator); **No** = unsupported/discouraged.

| Concern | Role | Class | Path | Gen? | Registration/discovery | May depend on | Txn/async | Rationale / rejected alt |
|---|---|---|---|---|---|---|---|---|
| **Contracts/interfaces** | Domain port | FC | `Domain/{D}/Contracts/{N}.php` | **Yes** `make:contract` | Container bind in `PulsarServiceProvider` | DTOs, Enums, VOs, Models, Events, other Contracts, Laravel contracts (all passive) | n/a | Fixed decision. Rejected: `Contract`/`Interface` suffix (redundant w/ dir). |
| **Value Objects** | Domain | FC | `Domain/{D}/ValueObjects/{N}.php` | **Yes** `make:value-object` | none | Enums, other VOs | n/a | Immutable domain primitives; readonly. Rejected: force all IDs to VOs (too heavy). |
| **Domain Services** | Domain | Ext | `Domain/{D}/Services/{N}.php` (rare) | No | none | Contracts, Queries, VOs | never txn | Most such logic is an Action/Operation. Generator would blur the Action boundary. |
| **Specifications** | Domain | Ext | `Domain/{D}/Specifications/` (rare) | No | none | Models, VOs | n/a | Queries cover reads; specs rare. No enforced invariant → no generator. |
| **DTOs** | Domain | FC (exists) | `Domain/{D}/DTOs/{N}.php` | Yes (exists) | none | Enums, VOs | n/a | Keep. readonly `from()` factory already correct. |
| **Enums** | Domain | FC (exists) | `Domain/{D}/Enums/{N}.php` | Yes (exists) | none | none | n/a | Keep; add backed-enum guidance (§8). |
| **Domain Exceptions** | Domain | FC (exists) | `Domain/{D}/Exceptions/{N}.php` | Yes (exists) | Rendered via `withExceptions()` | none | n/a | Keep. |
| **Infra Exceptions** | Infra | FC-nogen | `Infrastructure/{Area}/{N}Exception.php` | No | thrown by adapters, mapped to Domain exc | none | n/a | Adapters translate vendor errors; no separate generator. |
| **Actions** | Domain | FC (exists) | `Domain/{D}/Actions/{N}.php` | Yes (exists) | none | Contracts, Queries?(no), Models, DTOs, VOs, Enums | **never txn, never emit events** | Atomic. Fix D5 (no event emission). |
| **Queries** | Domain | FC (exists) | `Domain/{D}/Queries/{N}.php` | Yes (exists) | none | Models, DTOs, VOs | read-only | Keep. |
| **Operations** | Domain(service-scoped module) | FC (exists) | `Services/{S}/Modules/{M}/Operations/{N}.php` | Yes (exists) | none | Actions, Queries, Contracts | never txn, never emit events | Fix D2 (`handle`→`execute`). |
| **UseCases** | Application orchestration | FC (exists) | `Services/{S}/Modules/{M}/UseCases/{N}.php` | Yes (exists) | Actions, Operations, Queries, Events, Contracts | **owns txn; emits events; no other UseCase** | Keep. |
| **Controllers** | HTTP delivery | FC (exists) | `Services/{S}/Modules/{M}/Controllers/{N}.php` | Yes (exists) | UseCases only | no txn | Keep; fix D10. |
| **Form Requests** | HTTP delivery | FC (exists) | `.../Requests/{N}.php` | Yes (exists) | container-resolved on inject | validation rules | n/a | Keep; clarify `authorize()` default-deny + that auth must also exist for non-HTTP paths. |
| **Custom validation Rules** | HTTP delivery | Stock | `app/Rules/` | No | container-resolved | none | n/a | Generic Laravel; no Pulsar invariant. |
| **Middleware** | HTTP delivery | FC-nogen | `Services/{S}/Middleware/{N}.php` | No | `->withMiddleware()` alias in `bootstrap/app.php` | Contracts | no txn | Governed placement; explicit registration; no generator (no invariant beyond location). |
| **API Resources** | HTTP delivery | FC | `Services/{S}/Modules/{M}/Resources/{N}.php` | **Yes** `make:resource` | container/`toArray` | Models, DTOs, VOs | n/a | Enforces "response shaping in Services, never Domain" (README:160). |
| **Route-model binding** | HTTP delivery | Ext | routes / `resolveRouteBinding` | No | `SubstituteBindings` middleware | n/a | n/a | Works with relocated models; document. |
| **Response objects** | HTTP delivery | Ext | (Resources/JsonResponse) | No | n/a | n/a | n/a | Covered by Resources; Domain must never return responses. |
| **Policies** | Authorization (Domain) | FC (exists, **rewrite stub**) | `Domain/{D}/Policies/{N}Policy.php` | Yes (exists) | `Gate::guessPolicyNamesUsing` + `#[UsePolicy]` fallback in `PulsarServiceProvider` | passed data; Queries (allowed) | no txn | Fix D4 (model-aware, `bool` methods, `before`). |
| **Gates (non-resource)** | Authorization | FC-nogen | defined in `PulsarServiceProvider::boot()` | No | `Gate::define` | Enums (ability) | n/a | Central home; ability names via per-domain enums (§8). |
| **Ability identifiers** | Authorization | FC-nogen | `Domain/{D}/Enums/{N}Ability.php` (string-backed) | via `make:enum` | passed to Gate as `->value` | none | n/a | Closed set, domain-owned; avoid magic strings. |
| **Domain Events** | Domain fact | FC (exists, **rewrite stub**) | `Domain/{D}/Events/{N}.php` | Yes (exists) | dispatched by UseCases; discovered listeners | IDs/scalars/DTOs/VOs (**not models across queue**) | after-commit by default | Fix D3, D5, D6. readonly + `ShouldDispatchAfterCommit`. |
| **Integration Events** | Cross-boundary fact | Ext | `Domain/{D}/Events/` + outbox (Infra) | No | outbox relay | stable machine name + version | tier 4 (outbox) | Only when guaranteed external delivery needed; not default. |
| **Laravel framework Events** | Framework | Ext | consumed, not authored | No | `Event::listen` | n/a | n/a | Listen where relevant; don't author. |
| **Eloquent model Events / Observers** | Persistence | No (discouraged) | `Domain/{D}/Observers/` if unavoidable | No | `Model::observe` in provider | persistence-only | n/a | Business logic in observers hides workflows; allow only for persistence concerns (UUIDs). |
| **Listeners** | Domain reaction | **FC (NEW)** | `Domain/{D}/Listeners/{N}.php` | **Yes** `make:listener` | `->withEvents(discover:)` glob + provider | Contracts, Notifications, Jobs; **UseCase only if queued** | sync: no txn/no UseCase; queued: may call UseCase (idempotent) | Fills D7. |
| **Event Subscribers** | Domain reaction | Ext | `Domain/{D}/Listeners/{N}Subscriber.php` | No | `Event::subscribe` in provider | as Listeners | as Listeners | Rare; documented. |
| **Jobs (workflow entrypoint)** | Async delivery | **FC (NEW)** | `Services/{S}/Modules/{M}/Jobs/{N}.php` | **Yes** `make:job` | dispatched; no discovery | UseCase only | may `->afterCommit()`; idempotent; owns no txn (UseCase does) | Async twin of a Controller. |
| **Jobs (outbound side-effect)** | Infra | FC-nogen | `Infrastructure/{Area}/Jobs/{N}.php` | No | dispatched | Contracts, SDKs | idempotent | One infra side effect; not a workflow. |
| **Job middleware** | Infra/delivery | Ext | alongside job | No | `middleware()` | none | n/a | `WithoutOverlapping`, `RateLimited`; document. |
| **Batches / chains** | Delivery coordination | Ext | in UseCase or Job | No | `Bus::batch/chain` | Jobs | n/a | Coordinated in a UseCase; document. |
| **Queued Listeners** | Domain reaction (async) | via `make:listener --queued` | `Domain/{D}/Listeners/{N}.php` | Yes | discovery | may call UseCase | `ShouldQueueAfterCommit`; idempotent | Preferred over a Job when reacting to a domain event. |
| **Console Commands (consuming app)** | CLI/scheduler delivery | **FC (NEW)** | `Services/{S}/Modules/{M}/Commands/{N}.php` | **Yes** `make:command` | `->withCommands()` glob | UseCase only | no txn | Machine audience = `Internal` service typically. |
| **Pulsar's own Symfony commands** | Package | FC (exists) | `src/Commands/` | n/a | `bin/pulsar` | generators | n/a | Unchanged. |
| **Scheduled tasks** | Delivery | Ext | `routes/console.php` / `->withSchedule()` | No | `Schedule::command/job` | Commands/Jobs | `withoutOverlapping`,`onOneServer` | Schedule Pulsar commands/jobs; document. |
| **Notifications** | Domain outbound | **FC (NEW)** | `Domain/{D}/Notifications/{N}.php` | **Yes** `make:notification` | container-resolved | Models, DTOs, VOs | n/a | Laravel-first: domain communication. Invoked by Listeners. |
| **Mailables** | Domain outbound | **FC (NEW)** | `Domain/{D}/Mail/{N}.php` | **Yes** `make:mailable` | container-resolved | Models, DTOs, VOs | n/a | As Notifications. |
| **Broadcast events / channels** | Outbound | Ext | Event `implements ShouldBroadcast`; `routes/channels.php` | No | `withRouting(channels:)` | n/a | after-commit | Document; channel auth stays stock. |
| **External API / payment / storage / search / bus clients** | Infra | **FC (NEW)** | `Infrastructure/{Area}/{N}.php` | **Yes** `make:adapter` | container bind to Contract | Contracts, DTOs, VOs, SDKs, framework | idempotent where relevant | The concrete side of Contracts. |
| **Concrete impls of Domain Contracts** | Infra | via `make:adapter` | `Infrastructure/{Area}/{N}.php` | Yes | `$app->bind(Contract, Impl)` in provider | as above | — | Chosen 3rd layer. |
| **Service Providers** | Bootstrap | FC (partial) | `app/Providers/PulsarServiceProvider.php` + `Services/{S}/Providers/*` | via `make:service` + `pulsar install` | `bootstrap/providers.php` | all | n/a | Fix D8; add PulsarServiceProvider. |
| **Container bindings** | Bootstrap | FC-nogen | `PulsarServiceProvider::register()` | No | `bind`/`singleton`/`when` | Contracts+Impls | n/a | Central binding home; contextual binding per audience. |
| **Middleware aliases/groups, schedules, cmd/event discovery** | Bootstrap | Stock | `bootstrap/app.php` | via `pulsar install` (patched once) | native | n/a | n/a | Framework must own these files. |
| **Config** | Bootstrap | Stock | `config/` | No | native | n/a | n/a | Framework convention. |
| **Exception reporting/rendering** | Bootstrap | Stock | `bootstrap/app.php` `withExceptions()` | No | native | Domain exceptions | n/a | Map Domain exceptions to responses here. |
| **Models** | Domain | FC (exists) | `Domain/{D}/Models/{N}.php` | Yes (exists) | Eloquent | Casts, VOs, Enums | n/a | Keep. |
| **Casts** | Domain | Ext | `Domain/{D}/Casts/{N}.php` | No | referenced in `$casts` | VOs, Enums | n/a | Alongside models; no generator. |
| **Factories** | Persistence support | Stock | `database/factories/` | No | `HasFactory` resolution (pass FQCN) | Models | n/a | Laravel tooling owns this path. |
| **Migrations** | Persistence | Stock | `database/migrations/` | No | `migrate` | n/a | n/a | Framework tooling. |
| **Seeders** | Persistence | Stock | `database/seeders/` | No | `db:seed` | Models/UseCases | n/a | Framework tooling. |
| **Test doubles/fakes for Contracts** | Testing | Ext | `tests/.../Fakes/` | No | bound in tests | Contracts | n/a | Guidance in §12. |

Every "No generator" decision is because the type either has no path-based invariant Pulsar
needs to enforce (Rules, Casts, Middleware config) or is owned by Laravel tooling (migrations,
factories, seeders, `bootstrap/app.php`). Every new generator enforces a real invariant:
placement in the correct layer and a correct dependency direction.

---

## 6. Detailed design

### 6.1 Contracts (fixed first-class type)

**Path (confirmed):** `app/Pulsar/Domain/{Domain}/Contracts/{Name}.php`, namespace
`App\Pulsar\Domain\{Domain}\Contracts`.

**Naming (decided): no suffix, capability names** — `PaymentGateway`, `Clock`, `EventBus`,
`FileStorage`. Matches `Illuminate\Contracts\*`; the `Contracts/` directory already signals
role. Implementations carry the descriptive prefix (`StripePaymentGateway`), so there is no
name collision. The generator **normalizes** away a trailing `Contract`/`Interface` a user
types, to keep the convention pure.

**What qualifies as a Domain Contract:**
1. Domain-owned **ports for infrastructure** (payment, search, storage, clock, message bus).
2. **Stable behavioral interfaces** with more than one real implementation (or a real fake
   used in tests).
3. **Event-type/name contracts** implemented by per-domain backed Enums (see below).
4. **Policy/strategy interfaces** where the strategy genuinely varies.

**What does NOT belong in `Contracts/`** (guardrail against a junk drawer): DTOs, Value Objects,
Enums, traits, helper classes, concrete implementations, framework aliases, and
"interface-per-class" ports where only one implementation will ever exist and it is pure domain
logic (that is an Action/Query, not a Contract). The generator's help text and the SKILL/context
docs state this explicitly.

**Dependency rules (passive vs behavioral):** A Contract's method *signatures* may reference —
as **passive type dependencies** — DTOs, Enums, Value Objects, Domain Models, Domain Events,
other Domain Contracts, and Laravel framework contracts (`Illuminate\Contracts\*`). A Contract
must **never** import Service-layer types, concrete Infrastructure classes, or express a
**behavioral dependency** on a workflow type (it never calls a UseCase/Action/Operation). This
distinction resolves the "too-coarse diagram" problem: an Action legitimately *accepts* a DTO/
Enum (passive) while remaining forbidden from *invoking* another workflow type (behavioral).

**Cross-domain ownership:** the **consumer** owns the port in its own domain. If Domain
`Billing` needs to read from Domain `Catalog`, `Billing` defines a narrow Contract it needs;
the implementation (in Infrastructure, or a thin adapter over `Catalog`'s Query) satisfies it.
Domains never import each other's internals — only, at most, each other's Contracts, and
preferably not even that.

**Implementations & binding:** concrete implementations live in
`app/Pulsar/Infrastructure/{Area}/`. Bindings are registered in
`app/Providers/PulsarServiceProvider::register()`:
```php
$this->app->bind(\App\Pulsar\Domain\Billing\Contracts\PaymentGateway::class,
                 \App\Pulsar\Infrastructure\Payments\StripePaymentGateway::class);
```
Per-audience selection uses contextual binding (`$this->app->when(...)->needs(...)->give(...)`).

**Generated stub (`contract.stub`):**
```php
<?php

namespace {{namespace}};

interface {{name}}
{
    // Define the capability this port exposes.
    // Accept/return DTOs, Value Objects, Enums, or scalars — never framework response objects.
}
```

**CLI:** `pulsar make:contract {name} {domain}` — validates name (now wired), normalizes
suffix, errors with `FileAlreadyExistsException` on duplicate, prints the relative path.

**Event-name Contracts via per-domain backed Enums** (worked example): accepting arbitrary
`BackedEnum` is too broad (no shared behavior, no guarantee of a stable machine name). One
global "god enum" centralizes ownership of names that belong to different domains and forces
every domain to depend on it. Instead, define one stable Contract and let each domain implement
it with its own backed enum:
```php
// Domain/Shared/Contracts/DomainEventName.php  (or per-domain)
interface DomainEventName { public function eventName(): string; }

// Domain/Orders/Enums/OrderEventName.php
enum OrderEventName: string implements DomainEventName {
    case Placed   = 'orders.order_placed';   // persisted/protocol identifier — IMMUTABLE
    case Cancelled = 'orders.order_cancelled';
    public function eventName(): string { return $this->value; }
    public function label(): string { return match($this) {  // human-facing, may change freely
        self::Placed => 'Order placed', self::Cancelled => 'Order cancelled' }; }
}
```
The **`->value`** strings are persisted/protocol identifiers and must never be renamed casually;
**`label()`** is separate and free to change. Payload **schema version** belongs on the Event
class as a `public const int VERSION = 1;` (bumped on payload shape changes), not in the enum.

**Test doubles:** ship in-memory fakes in `tests/.../Fakes/` implementing the Contract; bind
them in the test's container setup (`$this->app->bind(Contract::class, FakeImpl::class)`).
Contracts make this trivial — a key reason a port earns its place only when a fake or second
impl is real.

### 6.2 Jobs and other non-HTTP entrypoints

Call graphs (behavioral edges only; `→` = "calls"):
```
HTTP:      Request   → Controller → UseCase → {Actions, Operations, Queries, Events}
Artisan:   Console   → Command    → UseCase → ...        (Command in Services/{S}/Modules/{M}/Commands)
Queue:     Worker    → Job        → UseCase → ...        (Job in Services/{S}/Modules/{M}/Jobs)
Scheduler: Schedule  → Command|Job → UseCase → ...        (defined in routes/console.php)
Event:     UseCase   → dispatch Event → Listener → {Contract side effect | Notification/Job | (queued) UseCase}
```

For **every** inbound adapter (Controller, Command, Job, sync Listener):
- **Location:** in a Service module (audience-scoped). Machine/queue/scheduler entrypoints
  default to the **`Internal`** service unless a specific audience owns them.
- **Allowed deps:** the relevant UseCase only (read-only endpoints may call a Query).
- **Validation/authorization:** performed in the adapter *before* the UseCase call — Form
  Request for HTTP; explicit `Gate`/policy check for Command/Job/Listener (they have no Form
  Request, so **authorization must not live only in Form Requests**; see §6.4).
- **Transaction:** never owned by the adapter — the UseCase owns it.
- **Returns:** HTTP → Resource/response; Command → exit code; Job/Listener → void.
- **Context (tenant/actor/correlation/locale):** re-established explicitly at the adapter
  boundary. A Job/Command has no HTTP request, so it must carry the actor/tenant id in its
  payload and re-authenticate/`setContext` before calling the UseCase — never rely on ambient
  request state (Octane hazard).
- **Retry/idempotency/timeout:** Jobs set `$tries`/`$backoff`/`$timeout`; because queues are
  at-least-once, the UseCase or Job must be idempotent (guard on a natural key or a processed-
  marker). `SerializesModels` passes only the model key — the worker re-fetches current state.
- **Runtime data:** passed as constructor args to the Job/Command and forwarded to the
  UseCase's `execute(...)` (constructor = dependencies, `execute` = runtime data — Octane-safe).
- **Anti-accumulation:** because adapters may only call a UseCase and hold no branching logic,
  business logic cannot silently accumulate in Jobs/Commands/Listeners.

**Job taxonomy** (different placement + rules, not one bucket):
1. **Workflow-entrypoint Job** → Service module `Jobs/`; calls a UseCase. Async twin of a
   Controller. `make:job`.
2. **Outbound side-effect Job** → `Infrastructure/{Area}/Jobs/`; performs one side effect via a
   Contract/SDK; calls no UseCase. Hand-written (no generator; no workflow invariant).
3. **Queued Listener** → `Domain/{D}/Listeners/` via `make:listener --queued`; reacts to a domain
   event; may call a UseCase.
4. **Scheduled Job/Command** → same placement as (1)/(Command); scheduled in `routes/console.php`.
5. **Batch/chain coordinator** → coordinated inside a UseCase; the batched units are Jobs.

### 6.3 Events, Listeners, Subscribers, and the four-tier transaction rule

**Taxonomy & authorship:**
- **Domain Events** — immutable facts about something that *already happened* in this app.
  First-class. **Created and dispatched by UseCases only** (fixes D5). `readonly`, carrying
  IDs/scalars/DTOs/Value Objects — **never Eloquent models across a queued/durable boundary**
  (a serialized model is just its key re-fetched fresh on the worker → staleness; snapshot the
  data you need into the event instead).
- **Integration Events** — facts published to *other systems*. Documented extension; use the
  outbox tier when guaranteed delivery matters; carry a stable machine name + `VERSION`.
- **Framework Events / Eloquent lifecycle Events** — consumed, not authored; observers
  discouraged (business logic belongs in UseCases).

**Listeners** live at `Domain/{D}/Listeners/`. Rules:
- **Synchronous listeners** may perform read-only invariant checks and fire-and-forget side
  effects but **must not call a UseCase** and must not assume durability.
- **Queued listeners** (`--queued`, implement `ShouldQueue` + `ShouldQueueAfterCommit`) **may
  call a UseCase** as an async workflow entrypoint — must be idempotent, and must avoid
  re-dispatching the same event (reentrancy/loop guard documented).
- **Subscribers** (multiple handlers in one class) are a rare documented variant in the same
  directory, registered via `Event::subscribe`.

**Discovery/registration:** `bootstrap/app.php` gets (patched once by `pulsar install`):
```php
->withEvents(discover: [ app_path('Pulsar/Domain/*/Listeners') ])   // glob covers all domains
```
Production: `php artisan event:cache`. Explicit `Event::listen` in `PulsarServiceProvider::boot()`
is the documented fallback when a team disables discovery.

**The four-tier honesty rule** (Pulsar states exactly what each guarantees):

| Tier | Mechanism | Guarantee | Use when |
|---|---|---|---|
| 1. Sync in-process | plain `event()` inside handler | Runs in the same request; **fires before commit if inside a transaction** (danger) | Never for committed-fact side effects; only pure in-memory reactions |
| 2. After-commit | Event `implements ShouldDispatchAfterCommit` | Runs only after commit; **discarded on rollback**; **lost if process crashes post-commit** (in-memory) | **Default** for domain events |
| 3. Queued (after-commit) | Listener `ShouldQueue`+`ShouldQueueAfterCommit` / Job `->afterCommit()` | After commit, on a worker, **at-least-once** (idempotency required); still not atomic with the DB write | Cross-aggregate work, external calls, expensive side effects |
| 4. Outbox/inbox | event row written in the same transaction; relay publishes | **Durable** — survives crashes; exactly-effectively-once at the consumer | Integration events needing guaranteed external delivery |

**Pulsar rule:** UseCases emit domain events; the Event stub implements
`ShouldDispatchAfterCommit` (tier 2) by default; side effects that must survive a crash use
tier 3 with idempotency; tier 4 is opt-in for integration events and never prescribed by
default. **All published examples that dispatch inside `DB::transaction` (D6) and the Action-
emits-event example (D5) are corrected** to this rule.

**Event stub (`event.stub`, rewritten):**
```php
<?php

namespace {{namespace}};

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class {{name}} implements ShouldDispatchAfterCommit
{
    public const int VERSION = 1;

    public function __construct(
        // Immutable facts only — IDs, scalars, DTOs, Value Objects. Never Eloquent models.
    ) {}
}
```

### 6.4 Policies, Gates, and authorization

- **Placement:** `Domain/{D}/Policies/{Model}Policy.php` (unchanged).
- **Discovery:** convention breaks under `App\Pulsar` (§4). `PulsarServiceProvider::boot()`
  registers a deterministic resolver:
  ```php
  Gate::guessPolicyNamesUsing(function (string $modelClass) {
      // App\Pulsar\Domain\{D}\Models\{Model}  →  App\Pulsar\Domain\{D}\Policies\{Model}Policy
      return preg_replace(['#\\\\Models\\\\#'], ['\\Policies\\'], $modelClass).'Policy';
  });
  ```
  Per-model `#[UsePolicy(...)]` is the documented override for irregular cases.
- **Policy stub (rewritten), model-aware** via a new `--model` option:
  ```php
  <?php
  namespace {{namespace}};

  use {{userModel}};
  use {{modelNamespace}}\{{model}};

  class {{name}}
  {
      public function before(User $user, string $ability): ?bool
      {
          return $user->isAdmin() ? true : null;   // admin bypass; null defers
      }
      public function view(User $user, {{model}} ${{modelVar}}): bool { return false; }
      public function create(User $user): bool { return false; }
      public function update(User $user, {{model}} ${{modelVar}}): bool { return false; }
      public function delete(User $user, {{model}} ${{modelVar}}): bool { return false; }
  }
  ```
  Methods return `bool` (default-deny). Where a denial reason is needed, the docs show returning
  an authorization `Response` instead. Without `--model`, a bare stub with a `before` hook is
  generated.
- **Dependencies in Policies:** may read via Queries or receive required data as arguments;
  never start transactions, never mutate.
- **Gates (non-resource):** defined in `PulsarServiceProvider::boot()` via `Gate::define`;
  `before`/`after` hooks there too.
- **Ability names:** string-backed **per-domain enums** (`{Domain}Ability`), passed to Gate as
  `->value`; no free-floating magic strings.
- **Authorization vs invariants:** authorization = "may this actor do this?" (Policies/Gates,
  enforced at the adapter boundary for *every* audience). Domain invariants = "is this
  operation valid?" (enforced inside Actions/UseCases regardless of who calls). **Tenant
  isolation** is enforced independently (global query scope / tenant-aware Contract), never
  conflated with authorization.
- **Non-HTTP enforcement:** because Commands/Jobs/Listeners have no Form Request, the RFC
  mandates the authorization check happen in the adapter (or first line of the UseCase),
  proving a Form Request is *not* the only guard.

### 6.5 Infrastructure / outbound adapters (the third layer)

- **Path:** `app/Pulsar/Infrastructure/{Area}/{Name}.php`, namespace
  `App\Pulsar\Infrastructure\{Area}` (Area = capability grouping: `Payments`, `Search`,
  `Storage`, `Messaging`, `Time`, ...).
- **Allowed deps:** Domain Contracts (implements them), DTOs, Enums, Value Objects, Domain
  Exceptions, Laravel framework, third-party SDKs.
- **Forbidden deps:** Services layer, UseCases, Actions, Operations, Controllers, other domains'
  internals. Infrastructure is a leaf — nothing in Domain/Services imports it directly; they
  depend on the Contract, the container supplies the implementation.
- **Errors:** adapters translate vendor exceptions into Domain exceptions at the boundary.
- **Binding:** `PulsarServiceProvider::register()`.
- **Generator `make:adapter {name} {area}`** with `--contract={FQCN|name} --domain={D}` to
  generate `implements` + a `// bind in PulsarServiceProvider` reminder (and, optionally, print
  the exact bind line to paste).
- **Octane safety:** adapters that hold no per-request state are `singleton`-safe; anything
  tenant/request-scoped is bound `scoped()` and resolves context via closures, never
  constructor capture.

### 6.6 Remaining Laravel concerns

- **Value Objects:** `Domain/{D}/ValueObjects/`, `final readonly class` with validation in the
  constructor and named constructors; `make:value-object`.
- **Notifications / Mailables:** `Domain/{D}/Notifications/`, `Domain/{D}/Mail/`; invoked by
  Listeners; carry DTO/VO data. `make:notification`, `make:mailable`.
- **Middleware:** `Services/{S}/Middleware/`; registered as aliases in `bootstrap/app.php`; no
  generator.
- **API Resources:** `Services/{S}/Modules/{M}/Resources/`; `make:resource`; enforces that
  response shaping never leaks into Domain.
- **Casts/Observers:** `Domain/{D}/Casts`, `Domain/{D}/Observers` (observers discouraged, only
  persistence concerns; registered via `Model::observe` in the provider).
- **Factories/Migrations/Seeders:** stay in `database/*` (framework tooling); factories
  reference Pulsar model FQCNs.
- **Config/Rules:** stock `config/`, `app/Rules/`.

---

## 7. Proposed directory tree

```
app/
├── Providers/
│   ├── AppServiceProvider.php                 [framework-owned]
│   └── PulsarServiceProvider.php              [REQUIRED — generated by `pulsar install`]
└── Pulsar/
    ├── Services/                              [REQUIRED]
    │   └── {Service}/                         (Admin | Client | Internal | ...)
    │       ├── Providers/                     (ServiceProvider + RouteServiceProvider)
    │       ├── Routes/api.php
    │       ├── Middleware/                     [optional, no generator]
    │       └── Modules/{Module}/
    │           ├── Controllers/                [optional]
    │           ├── Requests/                   [optional]
    │           ├── Resources/                  [optional — NEW]
    │           ├── UseCases/                   [optional]
    │           ├── Operations/                 [optional]
    │           ├── Jobs/                        [optional — NEW]
    │           └── Commands/                    [optional — NEW]
    ├── Domain/                                [REQUIRED]
    │   └── {Domain}/
    │       ├── Contracts/                       [optional — NEW first-class]
    │       ├── Models/                          [optional]
    │       ├── Actions/                         [optional]
    │       ├── Queries/                         [optional]
    │       ├── DTOs/                            [optional]
    │       ├── ValueObjects/                    [optional — NEW]
    │       ├── Enums/                           [optional]
    │       ├── Events/                          [optional]
    │       ├── Listeners/                        [optional — NEW]
    │       ├── Notifications/                    [optional — NEW]
    │       ├── Mail/                             [optional — NEW]
    │       ├── Policies/                         [optional]
    │       ├── Exceptions/                       [optional]
    │       ├── Casts/                            [optional, no generator]
    │       └── Observers/                        [optional, discouraged]
    └── Infrastructure/                        [optional — NEW third layer]
        └── {Area}/                            (Payments | Search | Storage | ...)
            ├── {Adapter}.php                    (implements a Domain Contract)
            └── Jobs/                            (outbound side-effect jobs, no generator)

bootstrap/
├── app.php            [framework-owned; patched once by `pulsar install`: withEvents/withCommands]
└── providers.php      [framework-owned; PulsarServiceProvider appended]
database/{migrations,factories,seeders}   [framework-owned; governed by Pulsar dependency rules]
config/, routes/{console.php,channels.php} [framework-owned]
```

---

## 8. Magic strings, Enums, and stable identifiers

| Category | Recommendation | Why |
|---|---|---|
| Persisted business states | **Per-domain string-backed Enum** | Closed set, domain-owned, type-safe; `->value` persisted |
| Persisted event names | **Per-domain backed Enum implementing an event-name Contract** | Stable machine id, domain ownership, no god enum (§6.1) |
| Event categories | Enum | Closed set |
| Authorization abilities | **Per-domain string-backed Enum**, passed to Gate as `->value` | Closed, domain-owned; avoids magic strings at boundary |
| Queue connection / queue names | **Config** (`config/queue.php` keys), referenced by constant | Operational, environment-varying; Laravel API takes strings |
| Event listener names | n/a (class-based) | Discovery/`Event::listen` uses class names |
| Artisan command signatures | **String in the command class** (`$signature`) | Framework requires string; single source on the class |
| Configuration keys | **Config files**; access via typed accessor if hot | Framework convention |
| Route names | **String**, namespaced by service (`{slug}.*`) | Framework API; already generated by RouteServiceProvider |
| External protocol identifiers | **Constant or backed Enum**, immutable | Protocol stability; never rename casually |

Cross-cutting: closed sets → Enums; open/environment sets → config; framework-string APIs →
convert at the boundary (`Enum::from()`/`->value`); persisted/protocol values are immutable and
carry a comment saying so; human labels live in `label()` methods, never reused as machine ids;
prefer per-domain enums over a global enum to keep ownership local.

---

## 9. Bootstrap and discovery design

`pulsar install` (new command, or a documented manual step) performs one-time wiring:

1. Generate `app/Providers/PulsarServiceProvider.php` and append to `bootstrap/providers.php`.
2. Patch `bootstrap/app.php`:
   ```php
   ->withEvents(discover: [ app_path('Pulsar/Domain/*/Listeners') ])
   ->withCommands([ app_path('Pulsar/Services/*/Modules/*/Commands') ])
   ```
3. `PulsarServiceProvider`:
   - `register()`: Domain Contract → Infrastructure bindings; contextual bindings per audience.
   - `boot()`: `Gate::guessPolicyNamesUsing(...)`; `Gate::define(...)` for non-resource gates;
     `Gate::before/after` admin hooks; optional `Model::observe(...)`.
4. Production caching notes: `event:cache`, `optimize` (config/route/event caches) are
   compatible because all registration is explicit/discoverable by path glob.

Idempotency: `pulsar install` detects prior wiring and is safe to re-run; `--force` re-applies.

---

## 10. CLI and generated-stub specification

**New commands** (register in `bin/pulsar`):

| Command | Args | Options | Path | Stub |
|---|---|---|---|---|
| `make:contract` | `name domain` | — | `Domain/{D}/Contracts/{N}.php` | `contract` |
| `make:value-object` | `name domain` | — | `Domain/{D}/ValueObjects/{N}.php` | `value-object` |
| `make:listener` | `name domain` | `--event=`, `--queued` | `Domain/{D}/Listeners/{N}.php` | `listener` / `listener-queued` |
| `make:notification` | `name domain` | — | `Domain/{D}/Notifications/{N}.php` | `notification` |
| `make:mailable` | `name domain` | — | `Domain/{D}/Mail/{N}.php` | `mailable` |
| `make:job` | `name module service` | — | `Services/{S}/Modules/{M}/Jobs/{N}.php` | `job` |
| `make:command` | `name module service` | `--signature=` | `Services/{S}/Modules/{M}/Commands/{N}.php` | `command` |
| `make:resource` | `name module service` | `--collection` | `Services/{S}/Modules/{M}/Resources/{N}.php` | `resource` / `resource-collection` |
| `make:adapter` | `name area` | `--contract=`, `--domain=` | `Infrastructure/{Area}/{N}.php` | `adapter` |
| `make:domain` | `name` | — | `Domain/{D}/.gitkeep` | — |
| `install` | — | `--force` | provider + bootstrap patch | `pulsar-service-provider`, patches |

**Common CLI behavior (all generators):** wire `validateName()` on `name` and
`sanitizeDirectoryName()` on every path segment (`domain`, `service`, `module`, `area`) —
closing D1; validate the parent scope exists (service via `serviceExists`; **new**
`domainExists` for domain generators — D9); duplicate → `FileAlreadyExistsException`; success
prints the relative path via `getRelativePath()`.

**Stub fixes (existing):** `operation.stub` `handle()`→`execute()` (D2); `event.stub` → readonly
+ `ShouldDispatchAfterCommit` + `VERSION` (D3); `policy.stub` → model-aware `bool` methods +
`before` (D4); fix `ServiceGenerator` stub path so `service-provider.stub`/
`route-service-provider.stub`/`routes-api.stub` are actually used (D8); align Controller CLI arg
order with constructor (D10).

**New stubs** listed above; representative shapes:
- `job.stub`: `implements ShouldQueue`, `use Queueable`, constructor holds ids/DTOs, `handle()`
  resolves + calls the UseCase, `$tries`/`$backoff` present, idempotency comment, `failed()`.
- `command.stub`: `$signature`/`$description`, `handle(): int`, authorization check, calls UseCase.
- `listener-queued.stub`: `implements ShouldQueue, ShouldQueueAfterCommit`, `handle(Event $e)`.
- `adapter.stub`: `implements {Contract}`, translates vendor errors to Domain exceptions.
- `value-object.stub`: `final readonly class` with validating constructor + named constructor.

---

## 11. Documentation consistency plan

Single-source-of-truth actions:

1. **AGENTS.md** → replace the duplicated body with a one-line pointer to CLAUDE.md (or generate
   both from one source). Fixes D12.
2. **Version** → read from a single `Pulsar::VERSION` constant (or `composer.json`), consumed by
   `bin/pulsar`; remove the hardcoded literal. Fixes D13.
3. **CLAUDE.md / AGENTS.md** → fix `make:usecase`→`make:use-case`; add `publish:*`, `ping`,
   and all new commands; add Infrastructure layer + Contracts + adapter/entrypoint rules;
   correct the "Key Methods" list to note validators are now wired.
4. **README.md** → add the third layer, the inside/outside decision rule, the adapter rule, the
   four-tier event rule, Contracts, and the full command reference (incl. `publish:*`, `install`).
5. **TESTING.md** → fix "Pulse"→"Pulsar"; remove contradictory coverage claims (one figure);
   correct generator count; either add the CI it describes or mark it "planned"; document the new
   real-Laravel integration tier.
6. **`context.stub` / `skill.stub`** → correct D5 (Action-emits-event example), D6 (in-transaction
   dispatch), D2/D3 method/readonly examples; add Contracts, Infrastructure, Listeners, Jobs,
   Commands, the four-tier rule, and the magic-string policy. These are the most authoritative
   docs and must match the new stubs exactly.
7. **Automated consistency test** (new): a test asserting that command names in `bin/pulsar`, the
   README command table, and the SKILL/context command lists are identical sets; and that every
   generator's method/stub matches the documented convention (e.g. Operation defines `execute`).

---

## 12. Testing strategy

**A. Package unit/feature tests (Pest, string + `php -l` assertions)** — add dedicated feature
tests for **every** generator currently untested (Action, Controller, Dto, Enum, Event,
Exception, Model, Policy, Query, Request, Service, UseCase) **and** all new generators. Each
covers: success (file created, correct path returned), correct namespace, no `{{placeholder}}`
remnants, valid PHP (`toBeValidPhp`), duplicate → error, invalid name / reserved keyword / path
traversal → error (now meaningful because validators are wired), and missing parent scope
(service/domain) → error. Use the now-unused `toHaveMethod` expectation to lock method names
(e.g. Operation `execute`, Job `handle`, Policy `view`).

**B. CLI-level tests** — exercise `Make*Command` via Symfony's `CommandTester` (not just the
generator classes) to cover argument wiring (catches D10-class bugs) and output strings.

**C. Real-Laravel integration matrix (NEW tier)** — a `tests/Integration` suite using
`orchestra/testbench` (dev-only dependency) with a fixture app, run against **Laravel 12 and
13**, proving what string assertions cannot:
- policy resolution via the generated `guessPolicyNamesUsing` resolver actually authorizes;
- listener discovery via the `withEvents(discover:)` glob actually fires a generated listener;
- a generated Command is found by `withCommands` and runs;
- a Contract→adapter binding resolves; a generated Job dispatches and calls its UseCase;
- an `ShouldDispatchAfterCommit` event does **not** fire on rollback and **does** after commit.

**D. Architecture tests** — Pest `arch()` presets on the *package* (e.g. generators extend
`Generator`, commands extend `PulsarCommand`) and shipped as an optional preset consumers can
enable on generated code (Domain must not import Services; Infrastructure must not import
UseCases; Controllers depend only on UseCases).

**E. CI + tooling (NEW)** — split GitHub Actions by audience. The test-and-quality job runs
the full Pest suite with `pcov` coverage, PHPStan, and Pint on the PHP 8.4 dev-toolchain floor.
The runtime compatibility job runs on PHP 8.2 and 8.3, resolves lowest supported runtime
dependencies with `composer update --prefer-lowest --prefer-stable --no-dev`, parses every
source file with the target PHP, and smoke-tests the CLI. Make TESTING.md match what exists.

Acceptance: full suite green on 12 and 13; coverage driver present so `--min` is enforced;
consistency test (§11.7) green.

---

## 13. Release and adoption plan

- **Version: `v0.3.0`** (semver 0.x — additive features plus a breaking convention change are
  permitted in a 0.x minor). Breaking pieces: Operation `handle()`→`execute()`; introduction of
  the required `PulsarServiceProvider` + `bootstrap/app.php` wiring; Contract implementations
  expected in the new Infrastructure layer.
- **Upgrade impact on existing consumers:** (1) run `pulsar install` to generate the provider
  and patch `bootstrap/app.php`; (2) rename existing Operation `handle()`→`execute()` (ship a
  codemod note / grep recipe); (3) move any concrete Contract implementations into
  `Infrastructure/` and add bindings; (4) existing Event classes keep working but should adopt
  `readonly` + `ShouldDispatchAfterCommit`. Old generated apps run unchanged until they adopt
  the new types — only the Operation rename is strictly required for consistency.
- **Phased, safe order:** foundation fixes (non-breaking) → doc corrections → Contracts +
  Infrastructure → entrypoints → events/authorization wiring → install command → CI. Each phase
  is independently shippable; the only hard-breaking step (Operation rename) is isolated and
  documented.

---

## 14. Implementation plan (phase by phase, file by file)

Each phase ships tests + docs in the same phase. TDD per the project's skills.

**Phase 0 — Foundation & safety (non-breaking).**
- Wire `validateName()` into every generator's `name`; `sanitizeDirectoryName()` into every path
  segment. Files: all `src/Generators/*Generator.php` (add a shared `validateInputs()` in
  `Generator.php` and call it first in each `generate()`).
- Add `domainExists()` to `Finder` + `validateDomainExists()` to domain generators (D9).
- Fix `ServiceGenerator` stub path (D8); delete dead code (`createRecursiveDirectories`,
  `relativeFromReal`) or cover it.
- Single-source the version (D13).
- Tests: input-validation + missing-domain cases for all generators; ServiceGenerator feature
  test asserting the stub files (not heredocs) are used.
- Acceptance: 249 existing tests still green + new validation/domain tests green.

**Phase 1 — Backfill generator tests + CLI tests + CI.**
- Add feature tests for the 12 untested generators; add `CommandTester` CLI tests.
- Add the split test-and-quality/runtime-compatibility GitHub Actions jobs, `pcov`, PHPStan,
  and Pint; fix TESTING.md to match.
- Acceptance: coverage driver works, `--min=85` passes, the test-and-quality job is green on
  PHP 8.4, and the runtime compatibility job is green on PHP 8.2 and 8.3.

**Phase 2 — Stub corrections (the breaking Operation rename lives here).**
- `operation.stub` `execute()`; `event.stub` readonly + `ShouldDispatchAfterCommit` + `VERSION`;
  `policy.stub` model-aware (+ `--model` option in `MakePolicyCommand`/`PolicyGenerator`);
  Controller arg-order fix (D10).
- Correct `context.stub` (D5 Action example, D6 in-transaction dispatch) and `skill.stub`
  (D2/D3, four-tier rule).
- Consistency test (§11.7).
- Acceptance: stubs match docs; consistency test green; migration note written.

**Phase 3 — Contracts + Infrastructure layer.**
- `contract.stub`; `ContractGenerator` (Domain, `Contracts/`, suffix normalization); `MakeContractCommand`; register in `bin/pulsar`.
- `Finder` additions: `findInfrastructureRootPath()`, `findInfrastructureNamespace($area)`.
- `adapter.stub`; `AdapterGenerator` (Infrastructure, `--contract/--domain`); `MakeAdapterCommand`.
- `make:domain` (explicit domain creation).
- Docs: README/context/skill gain Contracts + Infrastructure + port-ownership + binding.
- Tests: contract/adapter feature tests (success, dup, invalid, missing scope, namespace).

**Phase 4 — Non-HTTP entrypoints + outbound.**
- Generators + stubs + commands + tests for: `make:job`, `make:command`, `make:listener`
  (`--event/--queued`), `make:notification`, `make:mailable`, `make:resource`,
  `make:value-object`.
- Docs: adapter rule, call graphs, four-tier rule finalized in README/context/skill.

**Phase 5 — Bootstrap wiring (`pulsar install`) + real-Laravel integration.**
- `pulsar-service-provider.stub`; `InstallCommand`/`InstallGenerator`: generate provider,
  append to `bootstrap/providers.php`, idempotently patch `bootstrap/app.php`
  (`withEvents(discover:)`, `withCommands`).
- Add `orchestra/testbench` (dev) + `tests/Integration` matrix (§12.C) on Laravel 12 & 13.
- Optional shippable `arch()` preset for consumers.
- Acceptance: integration suite proves policy/listener/command discovery, bindings, job dispatch,
  after-commit/rollback behavior on both Laravel versions.

**Phase 6 — Release.**
- Finalize migration/upgrade guide; bump to `v0.3.0`; tag; update README command reference.

---

## 15. Open questions (with recommended defaults)

1. **`pulsar install` patching `bootstrap/app.php` automatically vs. printing manual
   instructions.** *Default: implement idempotent auto-patch with a `--dry-run` that prints the
   diff.* Consequence of manual-only: less invasive but higher chance consumers skip wiring and
   hit silent discovery failures. Consequence of auto-patch: must parse/modify a user file
   safely (mitigated by idempotency + backup + dry-run).
2. **Machine/queue/scheduler audience: a conventional `Internal` service vs. a dedicated
   top-level area.** *Default: use an `Internal` Service (no new top-level concept) — Jobs and
   Commands live in its modules.* Alternative (dedicated area) adds a fourth placement concept
   for little gain.
3. **Notifications/Mailables in Domain vs. Infrastructure.** *Default: Domain* (Laravel-first;
   they carry domain data and are triggered by Listeners). Alternative (Infrastructure) treats
   them as pure outbound adapters but then needs Contracts for every message — more ceremony
   than the benefit.
4. **Ship a required `arch()` dependency-rule preset for generated apps, or keep it optional.**
   *Default: optional but documented and recommended.* Making it required couples Pulsar's test
   posture onto consumers who may have their own.
5. **`make:job` for outbound side-effect jobs too, or only workflow-entrypoint jobs.** *Default:
   generator only for workflow-entrypoint jobs (Services); outbound infra jobs are hand-written
   in `Infrastructure/{Area}/Jobs`* — because a generator there would enforce no workflow
   invariant.
6. **Domain Services / Specifications — keep as documented extensions or promote to generators
   later.** *Default: documented extensions now; revisit if real usage shows they are common
   enough to warrant an enforced placement.*

---

## 16. PRD roadmap (this RFC → 7 sequenced PRDs)

This RFC is the **umbrella architecture reference**. Implementation is split into 7 focused,
dependency-ordered PRDs — each a self-contained, decision-complete spec that Claude Code/Codex
can convert into a plan and implement with tests + prod-grade hardening, and each one PR.

PRD files live in this directory (`docs/prd/`). This file is the umbrella
(`00-architecture-rfc.md`); each numbered PRD is a self-contained spec that cites it:

| PRD | File | Scope (RFC phase) | Breaking | Depends on |
|---|---|---|---|---|
| 1 | `01-test-harness.md` | Test harness & CI (Phase 1) | No | — |
| 2 | `02-foundation-safety.md` | Foundation & safety, wire validators (Phase 0) | No | 1 |
| 3 | `03-stub-doc-corrections.md` | Stub + doc contradiction fixes, Operation rename (Phase 2) | **Yes** | 2 |
| 4 | `04-contracts-infrastructure.md` | Contracts + Infrastructure layer (Phase 3) | No | 3 |
| 5 | `05-entrypoint-suite.md` | Non-HTTP entrypoint suite (Phase 4) | No | 4 |
| 6 | `06-bootstrap-integration.md` | `pulsar install` + real-Laravel integration (Phase 5) | No | 5 |
| 7 | `07-release.md` | Migration guide + `v0.3.0` release (Phase 6) | No | 6 |

Note the deliberate ordering swap vs. §14: **PRD 1 (test harness + CI) lands before PRD 2
(foundation fixes)** so the safety net exists before any generator behavior changes. Each PRD
cites the relevant RFC sections as its source of truth rather than re-deriving decisions.

## Verification (for pass 2)

- `vendor/bin/pest` green (baseline 249 → grows); `composer test:coverage` runs (pcov) and meets
  `--min`.
- New consistency test proves `bin/pulsar` ↔ README ↔ SKILL/context command sets match and stub
  method names match docs.
- `tests/Integration` (Testbench) green on Laravel 12 **and** 13, proving discovery/binding/
  after-commit behavior end-to-end.
- Manual smoke: in a fresh Laravel 12 app, `composer require faran/pulsar --dev`,
  `pulsar make:service Admin`, `pulsar install`, `pulsar make:contract PaymentGateway Billing`,
  `pulsar make:adapter StripePaymentGateway Payments --contract=PaymentGateway --domain=Billing`,
  `pulsar make:listener SendReceipt Billing --event=OrderPaid --queued`, then verify the binding
  resolves and the listener fires after commit.
