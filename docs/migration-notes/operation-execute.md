# Operation method rename: `handle()` to `execute()`

Pulsar 0.3 changes the generated Operation entry method from `handle()` to `execute()`.
Existing generated applications must rename Operation declarations and every call site that
invokes them.

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

For codebases that consistently suffix injected variables with `Operation`, this codemod can
handle the common call-site form:

```bash
rg -l -0 -- '\$[A-Za-z_][A-Za-z0-9_]*Operation->handle\(' app/Pulsar tests \
  | xargs -0 perl -pi -e 's/(\$[A-Za-z_][A-Za-z0-9_]*Operation)->handle\(/$1->execute(/g'
```

Review the diff after either codemod, run the application test suite, and verify no Operation
declarations remain:

```bash
rg -n 'public function handle\(' app/Pulsar/Services --glob '**/Operations/*.php'
```
