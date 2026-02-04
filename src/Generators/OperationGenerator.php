<?php

namespace Faran\Pulsar\Generators;

use Exception;
use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;

class OperationGenerator extends Generator
{
    /**
     * The name of the operation to generate.
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
     * Create a new OperationGenerator instance.
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
     * Generate the operation file.
     *
     * @throws Exception
     */
    public function generate(): string
    {
        $this->validateServiceExists();
        $this->createModuleDirectories();

        $filePath = $this->getOperationPath();

        if ($this->fileExists($filePath)) {
            throw new Exception("Operation [{$this->name}] already exists in {$this->service}/{$this->module}!");
        }

        $content = $this->getOperationContent();
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
        $operationsPath = $modulePath . DIRECTORY_SEPARATOR . 'Operations';

        $this->createDirectory($modulePath);
        $this->createDirectory($operationsPath);
    }

    /**
     * Get the operation file path.
     */
    protected function getOperationPath(): string
    {
        return $this->getModulePath() . DIRECTORY_SEPARATOR . 'Operations' . DIRECTORY_SEPARATOR . $this->name . '.php';
    }

    /**
     * Get the module path.
     */
    protected function getModulePath(): string
    {
        return $this->findServicesRootPath() . DIRECTORY_SEPARATOR . $this->service . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $this->module;
    }

    /**
     * Get the operation content.
     */
    protected function getOperationContent(): string
    {
        $namespace = $this->findServiceNamespace($this->service) . "\\Modules\\{$this->module}\\Operations";
        $stubPath = $this->getStubPath('operation');
        $stub = $this->loadStub($stubPath);

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }

}

