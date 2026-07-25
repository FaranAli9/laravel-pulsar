<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;

class ResourceGenerator extends Generator
{
    /**
     * Create a new ResourceGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $module,
        protected string $service,
        protected bool $collection = false,
    ) {}

    /**
     * Generate the API resource.
     *
     * @throws FileAlreadyExistsException
     * @throws ServiceDoesNotExistException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->service, $this->module]);
        $this->validateServiceExists();

        $resourcesPath = $this->getModulePath().DIRECTORY_SEPARATOR.'Resources';
        $filePath = $resourcesPath.DIRECTORY_SEPARATOR.$this->name.'.php';

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Resource', $this->name, "{$this->service}/{$this->module}");
        }

        $this->createDirectory($resourcesPath);
        $stubName = $this->collection ? 'resource-collection' : 'resource';
        $stub = $this->loadStub($this->getStubPath($stubName));
        $content = $this->replaceStubPlaceholders($stub, [
            'namespace' => $this->findServiceNamespace($this->service)."\\Modules\\{$this->module}\\Resources",
            'name' => $this->name,
        ]);
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Validate that the target service exists.
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
     * Get the target module path.
     */
    protected function getModulePath(): string
    {
        return $this->findServicesRootPath().DIRECTORY_SEPARATOR.$this->service
            .DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.$this->module;
    }
}
