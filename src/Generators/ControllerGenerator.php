<?php

namespace Faran\Pulsar\Generators;

use Exception;
use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;

class ControllerGenerator extends Generator
{
    /**
     * Create a new ControllerGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $module,
        protected string $service,
        protected bool $resource = false,
    ) {}

    /**
     * Generate the controller file.
     *
     * @throws FileAlreadyExistsException
     * @throws ServiceDoesNotExistException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->service, $this->module]);
        $this->validateServiceExists();

        $filePath = $this->getControllerPath();

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Controller', $this->name, "{$this->service}/{$this->module}");
        }

        $this->createModuleDirectories();

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
        $controllersPath = $modulePath.DIRECTORY_SEPARATOR.'Controllers';

        $this->createDirectory($modulePath);
        $this->createDirectory($controllersPath);
    }

    /**
     * Get the controller file path.
     */
    protected function getControllerPath(): string
    {
        return $this->getModulePath().DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.$this->name.'.php';
    }

    /**
     * Get the module path.
     */
    protected function getModulePath(): string
    {
        return $this->findServicesRootPath().DIRECTORY_SEPARATOR.$this->service.DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.$this->module;
    }

    /**
     * Get the controller content from stub.
     *
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
     * @throws Exception
     */
    protected function getNamespace(): string
    {
        return $this->findServiceNamespace($this->service)."\\Modules\\{$this->module}\\Controllers";
    }
}
