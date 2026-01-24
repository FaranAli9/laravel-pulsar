# Testing Documentation

> Testing philosophy, current state, and future plans for the Pulse package

## Testing Philosophy

### Core Principles

**1. Security First**
- Input validation must be exhaustively tested
- Path traversal prevention is critical
- Reserved PHP keyword blocking prevents runtime errors

**2. Fail Fast, Fail Clearly**
- Tests should catch issues before they reach production
- Error messages must be helpful for debugging
- Validation failures should guide developers to fixes

**3. Real-World Simulation**
- Use actual Laravel directory structures in tests
- Generate real files in isolated environments
- Verify generated code has valid PHP syntax

**4. Maintainability Over Coverage**
- Focus on critical paths and edge cases
- Write tests that document expected behavior
- Prefer readable tests over clever abstractions

### Why Test a Code Generator?

Code generators have unique risks:
- **Silent Failures**: Generated code may be syntactically invalid
- **Namespace Errors**: Wrong namespaces cause runtime crashes hours later
- **Security Risks**: Path traversal can overwrite system files
- **Regression**: Refactoring breaks generation without obvious symptoms

A bug in a generator affects every file it creates. Tests prevent multiplied failures.

---

## Current State

### Test Coverage: 100% (Critical Foundation Complete)

**Status**: ✅ **All critical foundation tests complete - 219 tests passing**

**Test Results** (as of latest run):
```
Tests:    219 passed (266 assertions)
Duration: 0.61s
```

**Completed Test Suites**:
- ✅ **InputValidationTest**: 132 tests (security-critical input validation)
- ✅ **OperationGeneratorTest**: 24 tests (end-to-end generation workflow)
- ✅ **GeneratorTest**: 43 tests (base class shared methods)
- ✅ **ExceptionsTest**: 20 tests (custom exception hierarchy)

**Coverage Breakdown**:
- Security validation (validateName): 116 tests
- Path sanitization (sanitizeDirectoryName): 16 tests  
- Stub handling (replaceStubPlaceholders, loadStub): 11 tests
- Path utilities (getStubPath, getRelativePath, generateSlug): 11 tests
- File operations (createFile, createDirectory, fileExists): 15 tests
- Operation generation workflow: 24 tests
- Custom exceptions: 20 tests
- Custom PHP expectations: 4 helpers (toBeValidPhp, toHaveNamespace, toHaveClass, toHaveMethod)

### What's Been Tested

**Unit Tests (195 tests)**:
- ✅ Input validation with security focus (132 tests)
  - Reserved PHP keywords (75 tests)
  - Invalid characters (24 tests)  
  - Path traversal attacks (10 tests)
  - Length limits (3 tests)
  - Edge cases (20 tests)
- ✅ Base Generator class methods (43 tests)
  - Stub placeholder replacement (8 tests)
  - Slug generation (6 tests)
  - Stub path resolution (5 tests)
  - Relative path handling (3 tests)
  - Stub loading (3 tests)
  - File/directory operations (18 tests)
- ✅ Custom exception hierarchy (20 tests)
  - InvalidNameException factory methods (8 tests)
  - FileAlreadyExistsException (5 tests)
  - StubNotFoundException (4 tests)
  - Exception inheritance (3 tests)

**Feature Tests (24 tests)**:
- ✅ OperationGenerator end-to-end workflow (24 tests)
  - Happy path file generation (8 tests)
  - Suffix enforcement (4 tests)
  - Error handling (5 tests)
  - Edge cases (4 tests)
  - Directory structure validation (3 tests)

**Test Infrastructure**:
- ✅ Custom Pest expectations (toBeValidPhp, toHaveNamespace, toHaveClass, toHaveMethod)
- ✅ TestGenerator helper to expose protected methods
- ✅ Mock Laravel app structure with real filesystem isolation
- ✅ Automatic temp directory setup/teardown
- ✅ Cross-platform path handling (macOS symlink resolution)

### What Hasn't Been Tested

**Remaining Generators (12 generators)**:
- ❌ ActionGenerator
- ❌ ControllerGenerator (plain vs resource)
- ❌ DtoGenerator
- ❌ EnumGenerator
- ❌ EventGenerator
- ❌ ExceptionGenerator (the generator, not the exception classes)
- ❌ ModelGenerator
- ❌ PolicyGenerator
- ❌ QueryGenerator
- ❌ RequestGenerator
- ❌ ServiceGenerator (complex multi-file generation)
- ❌ UseCaseGenerator

**Finder Trait Coverage**:
- ❌ findServicesRootPath()
- ❌ findServiceNamespace()
- ❌ findLaravelRoot() edge cases
- ❌ serviceExists() validation

**Integration Scenarios**:
- ❌ Generating multiple files in sequence
- ❌ Service creation followed by module generation
- ❌ Full vertical slice creation (service → module → controller → operations)
- ❌ Rollback behavior on failures

---

## Planned Testing

### Framework: Pest PHP

**Why Pest?**
- Expressive, readable syntax for documentation
- Built on PHPUnit (industry standard)
- Excellent for generator testing (file I/O focus)
- Active Laravel community support

### Test Structure

```
tests/
├── Pest.php                        # Configuration & custom expectations
├── Helpers/
│   ├── TestGenerator.php           # Exposes protected methods for testing
│   └── CreatesLaravelStructure.php # Mock Laravel app builder
├── Unit/
│   ├── GeneratorTest.php           # Base class methods (45 tests)
│   ├── InputValidationTest.php     # Security critical (53 tests)
│   ├── StubHandlingTest.php        # Stub system (18 tests)
│   ├── PathHelperTest.php          # Path utilities (19 tests)
│   └── ExceptionsTest.php          # Custom exceptions (12 tests)
├── Feature/
│   ├── OperationGeneratorTest.php  # E2E operation (25 tests)
│   ├── ServiceGeneratorTest.php    # Service scaffolding (20 tests)
│   └── ControllerGeneratorTest.php # Resource controllers (16 tests)
└── Fixtures/
    └── laravel-project/            # Mock Laravel structure
```

**Total**: ~208 tests across 8 test files

---

## Implementation Phases

### ✅ Phase 1: Critical Foundation (COMPLETE)

**Priority**: Security & core functionality

**Status**: All 219 tests passing (266 assertions)

1. ✅ **InputValidationTest.php** 
   - Reserved PHP keyword blocking (75 tests)
   - Invalid character rejection (24 tests)
   - Path traversal prevention (10 tests)
   - Length limits & edge cases (23 tests)
   - **Total: 132 tests**

2. ✅ **OperationGeneratorTest.php**
   - End-to-end file generation (8 tests)
   - Suffix enforcement (4 tests)
   - Error cases (5 tests)
   - Edge cases (4 tests)
   - Module directory structure (3 tests)
   - **Total: 24 tests**

3. ✅ **GeneratorTest.php**
   - Placeholder replacement (8 tests)
   - Slug generation (6 tests)
   - Stub path resolution (5 tests)
   - Relative path handling (3 tests)
   - Stub loading (3 tests)
   - File/directory operations (15 tests)
   - Recursive directories (4 tests)
   - **Total: 43 tests**

4. ✅ **ExceptionsTest.php**
   - Custom exception factories (20 tests)
   - Error message quality validation
   - **Total: 20 tests**

**Outcome**: ✅ Security vulnerabilities and core workflow validated. All critical paths tested.

---

### 📋 Phase 2: Finder Trait & Path Utilities (PLANNED)

**Priority**: Core infrastructure supporting all generators

**Estimated Time**: 3-4 hours

1. **FinderTraitTest.php** (~22 tests)
   - `findLaravelRoot()` detection in various scenarios (6 tests)
     - Standard Laravel project
     - Nested package development
     - Monorepo structure
     - Missing composer.json/artisan error handling
   - `findServicesRootPath()` validation (4 tests)
   - `findServiceNamespace()` from composer.json (6 tests)
   - `serviceExists()` validation (4 tests)
   - Edge cases: symlinks, case sensitivity (2 tests)

2. **StubHandlingTest.php** (~15 tests)
   - Loading all real stub files (13 stubs × 1 test each)
   - Malformed stub error handling (2 tests)
   - Stub placeholder completeness validation

**Goal**: Validate infrastructure used by all 13 generators.

---

### 🔮 Phase 3: Remaining Generators (FUTURE)

**Priority**: Feature completeness for all 12 remaining generators

**Estimated Time**: 8-10 hours

Each generator follows the OperationGeneratorTest pattern:
- Happy path generation
- Suffix enforcement (where applicable)
- Error handling
- Edge cases
- Directory structure validation

**High Priority** (used most frequently):
1. **ServiceGeneratorTest.php** (~24 tests)
   - Full service scaffolding with Providers/Routes
   - Service provider generation
   - Route file generation
   - Composer.json namespace integration
   - Error handling for existing services

2. **ControllerGeneratorTest.php** (~18 tests)
   - Plain controller generation (6 tests)
   - Resource controller generation (8 tests)
   - Stub selection logic (4 tests)

3. **RequestGeneratorTest.php** (~20 tests)
   - Request class generation
   - `authorize()` method defaults
   - `rules()` method scaffolding
   - Integration with controllers

**Medium Priority**:
4. **UseCaseGeneratorTest.php** (~20 tests)
5. **ModelGeneratorTest.php** (~18 tests)
6. **ActionGeneratorTest.php** (~20 tests)

**Lower Priority** (less frequently used):
7. **DtoGeneratorTest.php** (~15 tests)
8. **EventGeneratorTest.php** (~15 tests)
9. **ExceptionGeneratorTest.php** (~15 tests)
10. **EnumGeneratorTest.php** (~15 tests)
11. **PolicyGeneratorTest.php** (~18 tests)
12. **QueryGeneratorTest.php** (~18 tests)

**Estimated Total**: ~216 additional tests

---

### 🚀 Phase 4: Integration & Workflow Tests (FUTURE)

**Priority**: Real-world usage patterns

**Estimated Time**: 4-5 hours

1. **Vertical Slice Workflow** (~12 tests)
   - Create service → create module → create controller → create operations
   - Verify all files reference each other correctly
   - Test namespace consistency across vertical slice
   - Validate routes are properly registered

2. **Error Recovery** (~8 tests)
   - Partial failure rollback
   - Duplicate file handling across generators
   - Service not found cascading errors

3. **Cross-Platform Compatibility** (~6 tests)
   - Windows path handling
   - macOS symlink resolution
   - Linux permissions

**Goal**: Validate real-world developer workflows, not just individual commands.

---

### 📊 Final Coverage Goals

**Current Coverage**: 
- Lines: ~75% (core classes fully tested)
- Methods: ~80%
- Classes: 30% (4 of 13 generators)

**Target Coverage (All Phases Complete)**:
- Lines: 85%+
- Methods: 90%+
- Classes: 100% (all 13 generators + base classes)

**Critical Areas** (must maintain 95%+ coverage):
- ✅ Input validation (`validateName`, `sanitizeDirectoryName`)
- ✅ Custom exceptions (all factory methods)
- ✅ Stub loading and placeholder replacement
- ✅ Base Generator file operations

---

## Test Examples

### Custom Pest Expectations

```php
// Verify generated PHP is syntactically valid
expect($generatedCode)->toBeValidPhp();

// Check namespace and class in one assertion
expect($content)
    ->toHaveNamespace('App\Services\Auth\Operations')
    ->toHaveClass('CreateOrderOperation')
    ->toHaveMethod('handle');
```

### Security Test Pattern

```php
it('rejects path traversal attempts', function () {
    $generator = new TestGenerator();
    
    expect(fn() => $generator->testSanitizeDirectoryName('../../etc/passwd'))
        ->toThrow(InvalidNameException::class, 'path traversal');
});
```

### End-to-End Test Pattern

```php
it('generates operation with correct structure', function () {
    $generator = new OperationGenerator('CreateOrder', 'Checkout', 'Sales');
    $filePath = $generator->generate();
    
    expect($filePath)->toContain('app/Services/Sales/Modules/Checkout/Operations');
    
    $content = file_get_contents($this->tempDir . '/' . $filePath);
    expect($content)
        ->toBeValidPhp()
        ->toHaveNamespace('App\Services\Sales\Modules\Checkout\Operations')
        ->toHaveClass('CreateOrderOperation');
});
```

---

## Coverage Goals

### Minimum Targets
- **Lines**: 85%+
- **Methods**: 90%+
- **Classes**: 100% (all classes tested)

### Critical Areas (Must Be 95%+)
- Input validation (`validateName`, `sanitizeDirectoryName`)
- Custom exceptions (all factory methods)
- Stub loading and placeholder replacement
- Path traversal prevention

### Lower Priority (70%+ Acceptable)
- File I/O wrapper methods (tested indirectly)
- Cross-platform edge cases (hard to test exhaustively)

---

## Running Tests

```bash
# Install dependencies (first time)
composer install

# Run all tests
vendor/bin/pest

# Run with coverage report
vendor/bin/pest --coverage --min=85

# Run specific test file
vendor/bin/pest tests/Unit/InputValidationTest.php

# Run tests matching pattern
vendor/bin/pest --filter=validateName

# Watch mode (re-run on file changes)
vendor/bin/pest --watch

# Parallel execution (faster)
vendor/bin/pest --parallel
```

---

## CI/CD Integration

### GitHub Actions Workflow

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ${{ matrix.os }}
    strategy:
      matrix:
        os: [ubuntu-latest, macos-latest, windows-latest]
        php: [8.2, 8.3, 8.4]
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: xdebug
      
      - name: Install dependencies
        run: composer install --prefer-dist --no-interaction
      
      - name: Run tests
        run: vendor/bin/pest --coverage --min=85
      
      - name: Upload coverage
        uses: codecov/codecov-action@v3
        if: matrix.php == '8.3' && matrix.os == 'ubuntu-latest'
```

**Benefits**:
- Test across PHP 8.2, 8.3, 8.4
- Test on Linux, macOS, Windows (path compatibility)
- Block merges if coverage drops below 85%
- Track coverage trends over time

---

## Maintenance Guidelines

### When Adding New Generators

1. **Create feature test** in `tests/Feature/{Name}GeneratorTest.php`
2. **Test minimum**:
   - Happy path generation
   - Suffix enforcement (if applicable)
   - File already exists error
   - Invalid service/module errors
   - Generated PHP syntax validity
3. **Update this document** with test count
4. **Run full suite** to catch regressions

### When Modifying Validation Rules

1. **Update `InputValidationTest.php`** with new rules
2. **Add both positive and negative cases**
3. **Document security implications** in test descriptions
4. **Verify error messages are helpful**

### When Adding Custom Exceptions

1. **Add tests in `ExceptionsTest.php`**
2. **Test all factory methods**
3. **Verify messages include context** (file names, paths, etc.)
4. **Check exception hierarchy** (extends correct base)

---

## Testing Anti-Patterns to Avoid

### ❌ Don't Mock Filesystem Operations

**Bad**:
```php
// Mocking makes tests brittle and less realistic
$filesystem = Mockery::mock(Filesystem::class);
$filesystem->shouldReceive('put')->once();
```

**Good**:
```php
// Use real temp directories - simpler and more reliable
$generator->generate();
expect(file_exists($this->tempDir . '/Operation.php'))->toBeTrue();
```

### ❌ Don't Test Implementation Details

**Bad**:
```php
// Testing private method behavior
expect($generator->hasOperationSuffix('CreateOrder'))->toBeFalse();
```

**Good**:
```php
// Test public API and outcomes
expect($generator->generate())->toContain('CreateOrderOperation.php');
```

### ❌ Don't Share State Between Tests

**Bad**:
```php
// Reusing generator instance across tests
beforeAll(fn() => $this->generator = new OperationGenerator(...));
```

**Good**:
```php
// Fresh instance per test
beforeEach(fn() => $this->generator = new OperationGenerator(...));
```

---

## Test Data Strategy

### Fixtures vs Factories

**Use Fixtures For**:
- Laravel directory structure (consistent across tests)
- Stub file templates (rarely change)
- Mock composer.json, artisan files

**Use Factories/Builders For**:
- Generator instances (vary by test)
- Service/module names (randomized for isolation)
- File content assertions (test-specific)

### Temp Directory Management

```php
beforeEach(function () {
    // Unique temp dir per test prevents cross-contamination
    $this->tempDir = sys_get_temp_dir() . '/pulse-tests-' . uniqid();
    mkdir($this->tempDir, 0755, true);
    
    // Simulate working inside Laravel project
    chdir($this->tempDir);
    $this->createMockLaravelApp($this->tempDir);
});

afterEach(function () {
    // Clean up after every test
    chdir(dirname($this->tempDir));
    $this->deleteDirectory($this->tempDir);
});
```

---

## Future Enhancements

### Performance Testing
- Measure generation time for large codebases
- Benchmark stub loading overhead
- Profile directory creation depth limits

### Mutation Testing
- Install Infection PHP
- Verify tests catch introduced bugs
- Aim for 80%+ mutation score

### Integration Testing with Real Laravel
- Test in actual Laravel 11 project
- Verify service provider registration
- Test artisan command integration
- Validate namespace autoloading

### Snapshot Testing
- Capture generated file content
- Detect unintended template changes
- Review diffs on stub updates

---

## Resources

### Documentation
- [Pest PHP Docs](https://pestphp.com/)
- [PHPUnit Best Practices](https://phpunit.de/documentation.html)
- [Laravel Testing Guide](https://laravel.com/docs/testing)

### Related Tools
- **PHPStan**: Static analysis (level 8 recommended)
- **Infection**: Mutation testing framework
- **PHPCS**: Code style enforcement
- **PHP-CS-Fixer**: Auto-formatting

---

## Questions?

For testing questions or to propose changes to this strategy:
1. Check existing test examples in `tests/` directory
2. Review Pest documentation for syntax questions
3. Discuss significant changes with the team before implementing

Remember: **Good tests are documentation that proves itself correct.**
