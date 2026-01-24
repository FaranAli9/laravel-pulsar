<?php

namespace Faran\Pulse\Generators;

use Exception;
use Faran\Pulse\Exceptions\ServiceAlreadyExistsException;

class ServiceGenerator extends Generator
{
    /**
     * The name of the service to generate.
     */
    protected string $name;

    /**
     * The slug version of the service name.
     */
    protected string $slug;

    /**
     * Create a new ServiceGenerator instance.
     *
     * @param  string  $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->slug = $this->generateSlug($name);
    }

    /**
     * Generate the service structure (directories, providers, and routes).
     *
     * @throws Exception
     */
    public function generate(): void
    {
        $this->validateServiceDoesNotExist();
        $this->createDirectories();
        $this->createProviders();
        $this->createRoutes();
    }

    /**
     * Validate that the service doesn't already exist.
     *
     * @throws ServiceAlreadyExistsException
     */
    protected function validateServiceDoesNotExist(): void
    {
        if ($this->serviceExists($this->name)) {
            throw ServiceAlreadyExistsException::make($this->name);
        }
    }

    /**
     * Create the service directories.
     * 
     * Pulse services are vertically sliced with:
     * - Providers/ (service providers)
     * - Routes/ (route definitions)
     * - Modules/ (feature modules with controllers, requests, use cases, etc.)
     */
    protected function createDirectories(): void
    {
        $servicePath = $this->getServicePath();

        $directories = [
            $servicePath,
            $servicePath . DIRECTORY_SEPARATOR . 'Providers',
            $servicePath . DIRECTORY_SEPARATOR . 'Routes',
            $servicePath . DIRECTORY_SEPARATOR . 'Modules',
        ];

        foreach ($directories as $directory) {
            $this->createDirectory($directory);
            
            // Add .gitkeep to Modules directory to keep it in git
            if (basename($directory) === 'Modules') {
                $this->createGitkeep($directory);
            }
        }
    }

    /**
     * Create the service provider files.
     *
     * @throws Exception
     */
    protected function createProviders(): void
    {
        $servicePath = $this->getServicePath();
        $namespace = $this->findServiceNamespace($this->name);
        $providersPath = $servicePath . DIRECTORY_SEPARATOR . 'Providers';

        // Create ServiceProvider
        $providerFile = $providersPath . DIRECTORY_SEPARATOR . $this->name . 'ServiceProvider.php';
        $providerContent = $this->getServiceProviderContent($namespace);
        $this->createFile($providerFile, $providerContent);

        // Create RouteServiceProvider
        $routeProviderFile = $providersPath . DIRECTORY_SEPARATOR . 'RouteServiceProvider.php';
        $routeProviderContent = $this->getRouteServiceProviderContent($namespace);
        $this->createFile($routeProviderFile, $routeProviderContent);
    }

    /**
     * Create the routes file.
     */
    protected function createRoutes(): void
    {
        $servicePath = $this->getServicePath();
        $routesPath = $servicePath . DIRECTORY_SEPARATOR . 'Routes';
        $routeFile = $routesPath . DIRECTORY_SEPARATOR . 'api.php';

        if (!$this->fileExists($routeFile)) {
            $content = $this->getRoutesContent();
            $this->createFile($routeFile, $content);
        }
    }

    /**
     * Get the service path.
     */
    protected function getServicePath(): string
    {
        return $this->findServicesRootPath() . DIRECTORY_SEPARATOR . $this->name;
    }

    /**
     * Get the ServiceProvider content.
     *
     * @param  string  $namespace
     * @return string
     * @throws Exception
     */
    protected function getServiceProviderContent(string $namespace): string
    {
        $stubPath = __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'service-provider.stub';

        if ($this->fileExists($stubPath)) {
            $stub = $this->loadStub($stubPath);
            return $this->replaceStubPlaceholders($stub, [
                'namespace' => $namespace,
                'name' => $this->name,
            ]);
        }

        return $this->generateServiceProviderStub($namespace);
    }

    /**
     * Get the RouteServiceProvider content.
     *
     * @param  string  $namespace
     * @return string
     * @throws Exception
     */
    protected function getRouteServiceProviderContent(string $namespace): string
    {
        $stubPath = __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'route-service-provider.stub';

        if ($this->fileExists($stubPath)) {
            $stub = $this->loadStub($stubPath);
            return $this->replaceStubPlaceholders($stub, [
                'namespace' => $namespace,
                'name' => $this->name,
                'slug' => $this->slug,
            ]);
        }

        return $this->generateRouteServiceProviderStub($namespace);
    }

    /**
     * Get the routes file content.
     *
     * @return string
     */
    protected function getRoutesContent(): string
    {
        $stubPath = __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'routes-api.stub';

        if ($this->fileExists($stubPath)) {
            $stub = $this->loadStub($stubPath);
            return $this->replaceStubPlaceholders($stub, [
                'name' => $this->name,
                'slug' => $this->slug,
            ]);
        }

        return "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n/*\n|--------------------------------------------------------------------------\n| {$this->name} Service Routes\n|--------------------------------------------------------------------------\n| Prefix: /api/{$this->slug}\n|\n*/\n";
    }

    /**
     * Generate a default ServiceProvider stub.
     *
     * @param  string  $namespace
     * @return string
     */
    protected function generateServiceProviderStub(string $namespace): string
    {
        return <<<PHP
<?php

namespace {$namespace}\\Providers;

use Illuminate\\Support\\ServiceProvider;

class {$this->name}ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        \$this->app->register(RouteServiceProvider::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
PHP;
    }

    /**
     * Generate a default RouteServiceProvider stub.
     *
     * @param  string  $namespace
     * @return string
     */
    protected function generateRouteServiceProviderStub(string $namespace): string
    {
        return <<<PHP
<?php

namespace {$namespace}\\Providers;

use Illuminate\\Support\\Facades\\Route;
use Illuminate\\Support\\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        Route::prefix('api/{$this->slug}')
            ->as('{$this->slug}.')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
PHP;
    }
}
