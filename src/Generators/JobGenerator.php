<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;

class JobGenerator extends Generator
{
    /**
     * Create a new JobGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $module,
        protected string $service,
    ) {}

    /**
     * Generate the workflow-entrypoint job.
     *
     * @throws FileAlreadyExistsException
     * @throws ServiceDoesNotExistException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->service, $this->module]);
        $this->validateServiceExists();

        $jobsPath = $this->getModulePath().DIRECTORY_SEPARATOR.'Jobs';
        $filePath = $jobsPath.DIRECTORY_SEPARATOR.$this->name.'.php';

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Job', $this->name, "{$this->service}/{$this->module}");
        }

        $this->createDirectory($jobsPath);
        $stub = $this->loadStub($this->getStubPath('job'));
        $content = $this->replaceStubPlaceholders($stub, [
            'namespace' => $this->findServiceNamespace($this->service)."\\Modules\\{$this->module}\\Jobs",
            'useCaseNamespace' => $this->findServiceNamespace($this->service)."\\Modules\\{$this->module}\\UseCases",
            'useCase' => $this->useCaseName(),
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

    /**
     * Derive the single UseCase dependency from the job name.
     */
    protected function useCaseName(): string
    {
        $baseName = preg_replace('/Job$/', '', $this->name) ?? $this->name;

        return $baseName.'UseCase';
    }
}
