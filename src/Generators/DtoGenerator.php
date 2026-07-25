<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\DomainDoesNotExistException;
use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

class DtoGenerator extends Generator
{
    /**
     * Create a new DtoGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $domain,
    ) {}

    /**
     * Generate the DTO file.
     *
     * @throws FileAlreadyExistsException
     * @throws DomainDoesNotExistException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->domain]);
        $this->validateDomainExists($this->domain);

        $filePath = $this->getDtoPath();

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('DTO', $this->name, $this->domain);
        }

        $this->createDomainDirectories();

        $content = $this->getDtoContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Create domain directories if they don't exist.
     */
    protected function createDomainDirectories(): void
    {
        $domainPath = $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain;
        $dtosPath = $domainPath.DIRECTORY_SEPARATOR.'DTOs';

        $this->createDirectory($dtosPath);
    }

    /**
     * Get the DTO file path.
     */
    protected function getDtoPath(): string
    {
        return $this->getDomainPath().DIRECTORY_SEPARATOR.'DTOs'.DIRECTORY_SEPARATOR.$this->name.'.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain;
    }

    /**
     * Get the DTO content.
     */
    protected function getDtoContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain).'\\DTOs';
        $stubPath = $this->getStubPath('dto');
        $stub = $this->loadStub($stubPath);

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }
}
