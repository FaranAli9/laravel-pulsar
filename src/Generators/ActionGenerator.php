<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\DomainDoesNotExistException;
use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

class ActionGenerator extends Generator
{
    /**
     * Create a new ActionGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $domain,
    ) {}

    /**
     * Generate the action file.
     *
     * @throws FileAlreadyExistsException
     * @throws DomainDoesNotExistException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->domain]);
        $this->validateDomainExists($this->domain);

        $filePath = $this->getActionPath();

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Action', $this->name, $this->domain);
        }

        $this->createDomainDirectories();

        $content = $this->getActionContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Create domain directories if they don't exist.
     */
    protected function createDomainDirectories(): void
    {
        $domainPath = $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain;
        $actionsPath = $domainPath.DIRECTORY_SEPARATOR.'Actions';

        $this->createDirectory($actionsPath);
    }

    /**
     * Get the action file path.
     */
    protected function getActionPath(): string
    {
        return $this->getDomainPath().DIRECTORY_SEPARATOR.'Actions'.DIRECTORY_SEPARATOR.$this->name.'.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain;
    }

    /**
     * Get the action content.
     */
    protected function getActionContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain).'\\Actions';
        $stubPath = $this->getStubPath('action');
        $stub = $this->loadStub($stubPath);

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }
}
