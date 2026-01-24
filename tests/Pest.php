<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

// Custom expectations for code validation
expect()->extend('toBeValidPhp', function () {
    $temp = tempnam(sys_get_temp_dir(), 'php');
    file_put_contents($temp, $this->value);
    exec("php -l {$temp} 2>&1", $output, $code);
    unlink($temp);
    
    expect($code)->toBe(0, "PHP syntax error: " . implode("\n", $output));
    
    return $this;
});

expect()->extend('toHaveNamespace', function (string $namespace) {
    expect($this->value)->toContain("namespace {$namespace};");
    return $this;
});

expect()->extend('toHaveClass', function (string $class) {
    expect($this->value)->toContain("class {$class}");
    return $this;
});

expect()->extend('toHaveMethod', function (string $method) {
    expect($this->value)->toMatch("/function\s+{$method}\s*\(/");
    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createMockLaravelApp(string $root): void
{
    // Create composer.json
    file_put_contents($root . DIRECTORY_SEPARATOR . 'composer.json', json_encode([
        'name' => 'laravel/laravel',
        'type' => 'project',
        'autoload' => [
            'psr-4' => [
                'App\\' => 'app/',
            ]
        ]
    ], JSON_PRETTY_PRINT));
    
    // Create artisan
    file_put_contents($root . DIRECTORY_SEPARATOR . 'artisan', "<?php\n// Laravel artisan\n");
    
    // Create directory structure
    mkdir($root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Services', 0755, true);
    mkdir($root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Domain', 0755, true);
}

function createService(string $root, string $name): void
{
    $path = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . $name;
    mkdir($path . DIRECTORY_SEPARATOR . 'Providers', 0755, true);
    mkdir($path . DIRECTORY_SEPARATOR . 'Routes', 0755, true);
    
    // Create basic service provider
    $providerContent = <<<PHP
<?php

namespace App\\Services\\{$name}\\Providers;

use Illuminate\\Support\\ServiceProvider;

class {$name}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
PHP;
    
    file_put_contents(
        $path . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . $name . 'ServiceProvider.php',
        $providerContent
    );
}

function deleteDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    
    rmdir($dir);
}

/*
|--------------------------------------------------------------------------
| Shared Setup/Teardown
|--------------------------------------------------------------------------
|
| Setup and teardown for tests that need filesystem isolation
|
*/

uses()->beforeEach(function () {
    // Set up temp directory for all tests
    $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pulse-tests-' . uniqid();
    mkdir($this->tempDir, 0755, true);
    
    // Resolve symlinks (important for macOS where /var -> /private/var)
    $this->tempDir = realpath($this->tempDir);
    
    // Save original working directory
    $this->originalCwd = getcwd();
    
    // Create mock Laravel structure
    createMockLaravelApp($this->tempDir);
    
    // Change to temp directory
    chdir($this->tempDir);
})->afterEach(function () {
    // Clean up temp directory
    if (isset($this->tempDir) && is_dir($this->tempDir)) {
        // Restore original working directory
        chdir($this->originalCwd);
        
        // Clean up temp directory
        deleteDirectory($this->tempDir);
    }
})->in('Feature', 'Unit');
