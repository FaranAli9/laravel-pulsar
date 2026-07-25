<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\DomainDoesNotExistException;
use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

class ModelGenerator extends Generator
{
    /**
     * Create a new ModelGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $domain,
    ) {}

    /**
     * Generate the model file.
     *
     * @throws FileAlreadyExistsException
     * @throws DomainDoesNotExistException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->domain]);
        $this->validateDomainExists($this->domain);

        $filePath = $this->getModelPath();

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Model', $this->name, $this->domain);
        }

        $this->createDomainDirectories();

        $content = $this->getModelContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Create domain directories if they don't exist.
     */
    protected function createDomainDirectories(): void
    {
        $domainPath = $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain;
        $modelsPath = $domainPath.DIRECTORY_SEPARATOR.'Models';

        $this->createDirectory($modelsPath);
    }

    /**
     * Get the model file path.
     */
    protected function getModelPath(): string
    {
        return $this->getDomainPath().DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR.$this->name.'.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain;
    }

    /**
     * Get the model content.
     */
    protected function getModelContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain).'\\Models';
        $stubPath = $this->getStubPath('model');
        $stub = $this->loadStub($stubPath);

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }
}
