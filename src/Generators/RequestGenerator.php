<?php

namespace Faran\Pulsar\Generators;

use Exception;
use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;

class RequestGenerator extends Generator
{
    /**
     * The name of the request to generate.
     */
    protected string $name;

    /**
     * The name of the module.
     */
    protected string $module;

    /**
     * The name of the service.
     */
    protected string $service;

    /**
     * Create a new RequestGenerator instance.
     *
     * @param  string  $name
     * @param  string  $module
     * @param  string  $service
     */
    public function __construct(string $name, string $module, string $service)
    {
        $this->name = $name;
        $this->module = $module;
        $this->service = $service;
    }

    /**
     * Generate the request file.
     *
     * @throws Exception
     */
    public function generate(): string
    {
        $this->validateServiceExists();
        $this->createModuleDirectories();

        $filePath = $this->getRequestPath();

        if ($this->fileExists($filePath)) {
            throw new Exception("Request [{$this->name}] already exists in {$this->service}/{$this->module}!");
        }

        $content = $this->getRequestContent();
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
        $requestsPath = $modulePath . DIRECTORY_SEPARATOR . 'Requests';

        $this->createDirectory($modulePath);
        $this->createDirectory($requestsPath);
    }

    /**
     * Get the request file path.
     */
    protected function getRequestPath(): string
    {
        return $this->getModulePath() . DIRECTORY_SEPARATOR . 'Requests' . DIRECTORY_SEPARATOR . $this->name . '.php';
    }

    /**
     * Get the module path.
     */
    protected function getModulePath(): string
    {
        return $this->findServicesRootPath() . DIRECTORY_SEPARATOR . $this->service . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $this->module;
    }

    /**
     * Get the request content from stub.
     *
     * @return string
     * @throws Exception
     */
    protected function getRequestContent(): string
    {
        $stubPath = $this->getStubPath('request');

        if ($this->fileExists($stubPath)) {
            $stub = $this->loadStub($stubPath);
            return $this->replaceStubPlaceholders($stub, [
                'namespace' => $this->getNamespace(),
                'name' => $this->name,
                'service' => $this->service,
                'module' => $this->module,
            ]);
        }

        throw new Exception('Request stub not found');
    }

    /**
     * Get the request namespace.
     *
     * @return string
     * @throws Exception
     */
    protected function getNamespace(): string
    {
        $rootNamespace = $this->findRootNamespace();
        return "{$rootNamespace}\\Services\\{$this->service}\\Modules\\{$this->module}\\Requests";
    }

}

