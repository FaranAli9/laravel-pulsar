<?php

namespace Faran\Pulsar\Generators;

use Exception;
use Faran\Pulsar\Exceptions\ServiceAlreadyExistsException;

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
        $this->validateInputs([$this->name], [$this->name]);
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
     * Pulsar services are vertically sliced with:
     * - Providers/ (service providers)
     * - Routes/ (route definitions)
     * - Modules/ (feature modules with controllers, requests, use cases, etc.)
     */
    protected function createDirectories(): void
    {
        $servicePath = $this->getServicePath();

        $directories = [
            $servicePath,
            $servicePath.DIRECTORY_SEPARATOR.'Providers',
            $servicePath.DIRECTORY_SEPARATOR.'Routes',
            $servicePath.DIRECTORY_SEPARATOR.'Modules',
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
        $providersPath = $servicePath.DIRECTORY_SEPARATOR.'Providers';

        // Create ServiceProvider
        $providerFile = $providersPath.DIRECTORY_SEPARATOR.$this->name.'ServiceProvider.php';
        $providerContent = $this->getServiceProviderContent($namespace);
        $this->createFile($providerFile, $providerContent);

        // Create RouteServiceProvider
        $routeProviderFile = $providersPath.DIRECTORY_SEPARATOR.'RouteServiceProvider.php';
        $routeProviderContent = $this->getRouteServiceProviderContent($namespace);
        $this->createFile($routeProviderFile, $routeProviderContent);
    }

    /**
     * Create the routes file.
     */
    protected function createRoutes(): void
    {
        $servicePath = $this->getServicePath();
        $routesPath = $servicePath.DIRECTORY_SEPARATOR.'Routes';
        $routeFile = $routesPath.DIRECTORY_SEPARATOR.'api.php';

        if (! $this->fileExists($routeFile)) {
            $content = $this->getRoutesContent();
            $this->createFile($routeFile, $content);
        }
    }

    /**
     * Get the service path.
     */
    protected function getServicePath(): string
    {
        return $this->findServicesRootPath().DIRECTORY_SEPARATOR.$this->name;
    }

    /**
     * Get the ServiceProvider content.
     *
     * @throws Exception
     */
    protected function getServiceProviderContent(string $namespace): string
    {
        $stub = $this->loadStub($this->getStubPath('service-provider'));

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }

    /**
     * Get the RouteServiceProvider content.
     *
     * @throws Exception
     */
    protected function getRouteServiceProviderContent(string $namespace): string
    {
        $stub = $this->loadStub($this->getStubPath('route-service-provider'));

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
            'slug' => $this->slug,
        ]);
    }

    /**
     * Get the routes file content.
     */
    protected function getRoutesContent(): string
    {
        $stub = $this->loadStub($this->getStubPath('routes-api'));

        return $this->replaceStubPlaceholders($stub, [
            'name' => $this->name,
            'slug' => $this->slug,
        ]);
    }
}
