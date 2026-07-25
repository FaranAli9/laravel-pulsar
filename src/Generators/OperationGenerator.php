<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;

class OperationGenerator extends Generator
{
    /**
     * Create a new OperationGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $module,
        protected string $service,
    ) {}

    /**
     * Generate the operation file.
     *
     * @throws FileAlreadyExistsException
     * @throws ServiceDoesNotExistException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->service, $this->module]);
        $this->validateServiceExists();

        $filePath = $this->getOperationPath();

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Operation', $this->name, "{$this->service}/{$this->module}");
        }

        $this->createModuleDirectories();

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
        if (! $this->serviceExists($this->service)) {
            throw ServiceDoesNotExistException::make($this->service);
        }
    }

    /**
     * Create module directories if they don't exist.
     */
    protected function createModuleDirectories(): void
    {
        $modulePath = $this->getModulePath();
        $operationsPath = $modulePath.DIRECTORY_SEPARATOR.'Operations';

        $this->createDirectory($modulePath);
        $this->createDirectory($operationsPath);
    }

    /**
     * Get the operation file path.
     */
    protected function getOperationPath(): string
    {
        return $this->getModulePath().DIRECTORY_SEPARATOR.'Operations'.DIRECTORY_SEPARATOR.$this->name.'.php';
    }

    /**
     * Get the module path.
     */
    protected function getModulePath(): string
    {
        return $this->findServicesRootPath().DIRECTORY_SEPARATOR.$this->service.DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.$this->module;
    }

    /**
     * Get the operation content.
     */
    protected function getOperationContent(): string
    {
        $namespace = $this->findServiceNamespace($this->service)."\\Modules\\{$this->module}\\Operations";
        $stubPath = $this->getStubPath('operation');
        $stub = $this->loadStub($stubPath);

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }
}
