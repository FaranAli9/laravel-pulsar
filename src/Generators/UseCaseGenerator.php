<?php

namespace Faran\Pulsar\Generators;

use Exception;
use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;

class UseCaseGenerator extends Generator
{
    /**
     * Create a new UseCaseGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $module,
        protected string $service,
    ) {}

    /**
     * Generate the use case file.
     *
     * @throws FileAlreadyExistsException
     * @throws ServiceDoesNotExistException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->service, $this->module]);
        $this->validateServiceExists();

        $filePath = $this->getUseCasePath();

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('UseCase', $this->name, "{$this->service}/{$this->module}");
        }

        $this->createModuleDirectories();

        $content = $this->getUseCaseContent();
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
        $useCasesPath = $modulePath.DIRECTORY_SEPARATOR.'UseCases';

        $this->createDirectory($modulePath);
        $this->createDirectory($useCasesPath);
    }

    /**
     * Get the use case file path.
     */
    protected function getUseCasePath(): string
    {
        return $this->getModulePath().DIRECTORY_SEPARATOR.'UseCases'.DIRECTORY_SEPARATOR.$this->name.'.php';
    }

    /**
     * Get the module path.
     */
    protected function getModulePath(): string
    {
        return $this->findServicesRootPath().DIRECTORY_SEPARATOR.$this->service.DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.$this->module;
    }

    /**
     * Get the use case content from stub.
     *
     * @throws Exception
     */
    protected function getUseCaseContent(): string
    {
        $stubPath = $this->getStubPath('use-case');

        if ($this->fileExists($stubPath)) {
            $stub = $this->loadStub($stubPath);

            return $this->replaceStubPlaceholders($stub, [
                'namespace' => $this->getNamespace(),
                'name' => $this->name,
                'service' => $this->service,
                'module' => $this->module,
            ]);
        }

        throw new Exception('UseCase stub not found');
    }

    /**
     * Get the use case namespace.
     *
     * @throws Exception
     */
    protected function getNamespace(): string
    {
        return $this->findServiceNamespace($this->service)."\\Modules\\{$this->module}\\UseCases";
    }
}
