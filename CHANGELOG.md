# Changelog

All notable changes to Pulsar are documented in this file.

## v0.3.0 - 2026-07-26

### ⚠️ Breaking

- **Renamed generated Operation entry methods from `handle()` to `execute()`.** Existing
  Operation declarations and call sites must be migrated.
- **Added required application wiring through `pulsar install`.** Applications adopting the new
  policy, Listener, Command, or Contract-to-adapter conventions must generate and register
  `PulsarServiceProvider` and patch `bootstrap/app.php`.
- **Raised the runtime floor to PHP `^8.3`.**

See [UPGRADING.md](UPGRADING.md) for the ordered migration and codemod recipes.

### Added

- A third `Infrastructure` layer for concrete implementations of Domain Contracts.
- `make:contract`, `make:adapter`, `make:domain`, `make:job`, `make:command`, `make:listener`,
  `make:notification`, `make:mailable`, `make:resource`, and `make:value-object`.
- `pulsar install` with idempotent provider generation, bootstrap discovery wiring, dry-run
  output, backups, and safe manual fallback.
- PHPStan, Pint, PCOV coverage enforcement, and CI for the PHP 8.3/8.4/8.5 unit matrix plus the
  Laravel 12/13 integration matrix.
- An optional Pest `arch()` preset for generated applications.

### Changed

- Generated Operations now expose `execute()`.
- Generated Events are `final readonly`, implement `ShouldDispatchAfterCommit`, and declare a
  payload `VERSION`.
- Generated Policies support model-aware methods through `--model`.
- Generator input validators are now enforced.
- Domain generators now fail when their target Domain does not exist.

### Fixed

- Corrected the `ServiceGenerator` stub-path bug so the shipped service stubs are used.
- Made `Faran\Pulsar\Pulsar::VERSION` the single runtime version source consumed by
  `bin/pulsar`.
- Removed published-guidance contradictions that showed Actions emitting Events or Event
  dispatch inside transactions.
- Removed dead generator and path-resolution code.
