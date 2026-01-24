<?php

namespace Faran\Pulse\Generators;

use Exception;
use Faran\Pulse\Exceptions\ServiceDoesNotExistException;

class ControllerGenerator extends Generator
{
    /**
     * The name of the controller to generate.
     */
    protected string $name;

    /**
     * The name of the service.
     */
    protected string $service;

    /**
     * The name of the module.
     */
    protected string $module;

    /**
     * Whether to generate a resourceful controller.
     */
    protected bool $resource;

    /**
     * Create a new ControllerGenerator instance.
     *
     * @param  string  $name
     * @param  string  $service
     * @param  string  $module
     * @param  bool  $resource
     */
    public function __construct(string $name, string $service, string $module, bool $resource = false)
    {
        $this->name = $this->ensureControllerSuffix($name);
        $this->service = $service;
        $this->module = $module;        $this->resource = $resource;    }

    /**
     * Generate the controller file.
     *
     * @throws Exception
     */
    public function generate(): string
    {
        $this->validateServiceExists();
        $this->createModuleDirectories();
        
        $filePath = $this->getControllerPath();
        
        if ($this->fileExists($filePath)) {
            throw new Exception("Controller [{$this->name}] already exists in {$this->service}/{$this->module}!");
        }

        $content = $this->getControllerContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Validate that the service exists.
     *
     * @throws ServiceDoesNotExistException
     */
    protected function validateServiceExists(): void
    {
        if (!$this->serviceExists($this->service)) {
            throw ServiceDoesNotExistException::make($this->service);
        }
    }

    /**
     * Create module directories if they don't exist.
     */
    protected function createModuleDirectories(): void
    {
        $modulePath = $this->getModulePath();
        $controllersPath = $modulePath . DIRECTORY_SEPARATOR . 'Controllers';

        $this->createDirectory($modulePath);
        $this->createDirectory($controllersPath);
    }

    /**
     * Get the controller file path.
     */
    protected function getControllerPath(): string
    {
        return $this->getModulePath() . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . $this->name . '.php';
    }

    /**
     * Get the module path.
     */
    protected function getModulePath(): string
    {
        return $this->findServicesRootPath() . DIRECTORY_SEPARATOR . $this->service . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $this->module;
    }

    /**
     * Get the controller content from stub.
     *
     * @return string
     * @throws Exception
     */
    protected function getControllerContent(): string
    {
        $stubName = $this->resource ? 'controller-resource' : 'controller-plain';
        $stubPath = $this->getStubPath($stubName);

        if ($this->fileExists($stubPath)) {
            $stub = $this->loadStub($stubPath);
            return $this->replaceStubPlaceholders($stub, [
                'namespace' => $this->getNamespace(),
                'name' => $this->name,
                'service' => $this->service,
                'module' => $this->module,
            ]);
        }

        throw new Exception('Controller stub not found');
    }

    /**
     * Get the controller namespace.
     *
     * @return string
     * @throws Exception
     */
    protected function getNamespace(): string
    {
        $rootNamespace = $this->findRootNamespace();
        return "{$rootNamespace}\\Services\\{$this->service}\\Modules\\{$this->module}\\Controllers";
    }

    /**
     * Ensure the controller name has the "Controller" suffix.
     */
    protected function ensureControllerSuffix(string $name): string
    {
        if (!str_ends_with($name, 'Controller')) {
            return $name . 'Controller';
        }

        return $name;
    }
}
