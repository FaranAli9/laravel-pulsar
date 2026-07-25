<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\DomainDoesNotExistException;
use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

class QueryGenerator extends Generator
{
    /**
     * Create a new QueryGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $domain,
    ) {}

    /**
     * Generate the query file.
     *
     * @throws FileAlreadyExistsException
     * @throws DomainDoesNotExistException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->domain]);
        $this->validateDomainExists($this->domain);

        $filePath = $this->getQueryPath();

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Query', $this->name, $this->domain);
        }

        $this->createDomainDirectories();

        $content = $this->getQueryContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Create domain directories if they don't exist.
     */
    protected function createDomainDirectories(): void
    {
        $domainPath = $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain;
        $queriesPath = $domainPath.DIRECTORY_SEPARATOR.'Queries';

        $this->createDirectory($queriesPath);
    }

    /**
     * Get the query file path.
     */
    protected function getQueryPath(): string
    {
        return $this->getDomainPath().DIRECTORY_SEPARATOR.'Queries'.DIRECTORY_SEPARATOR.$this->name.'.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain;
    }

    /**
     * Get the query content.
     */
    protected function getQueryContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain).'\\Queries';
        $stubPath = $this->getStubPath('query');
        $stub = $this->loadStub($stubPath);

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }
}
