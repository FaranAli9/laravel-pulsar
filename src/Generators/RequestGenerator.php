<?php

namespace Faran\Pulsar\Generators;

use Exception;
use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;

class RequestGenerator extends Generator
{
    /**
     * Create a new RequestGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $module,
        protected string $service,
    ) {}

    /**
     * Generate the request file.
     *
     * @throws FileAlreadyExistsException
     * @throws ServiceDoesNotExistException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->service, $this->module]);
        $this->validateServiceExists();

        $filePath = $this->getRequestPath();

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Request', $this->name, "{$this->service}/{$this->module}");
        }

        $this->createModuleDirectories();

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
        $requestsPath = $modulePath.DIRECTORY_SEPARATOR.'Requests';

        $this->createDirectory($modulePath);
        $this->createDirectory($requestsPath);
    }

    /**
     * Get the request file path.
     */
    protected function getRequestPath(): string
    {
        return $this->getModulePath().DIRECTORY_SEPARATOR.'Requests'.DIRECTORY_SEPARATOR.$this->name.'.php';
    }

    /**
     * Get the module path.
     */
    protected function getModulePath(): string
    {
        return $this->findServicesRootPath().DIRECTORY_SEPARATOR.$this->service.DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.$this->module;
    }

    /**
     * Get the request content from stub.
     *
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
     * @throws Exception
     */
    protected function getNamespace(): string
    {
        return $this->findServiceNamespace($this->service)."\\Modules\\{$this->module}\\Requests";
    }
}
