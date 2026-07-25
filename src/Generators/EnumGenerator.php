<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\DomainDoesNotExistException;
use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

class EnumGenerator extends Generator
{
    /**
     * Create a new EnumGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $domain,
    ) {}

    /**
     * Generate the enum file.
     *
     * @throws FileAlreadyExistsException
     * @throws DomainDoesNotExistException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->domain]);
        $this->validateDomainExists($this->domain);

        $filePath = $this->getEnumPath();

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Enum', $this->name, $this->domain);
        }

        $this->createDomainDirectories();

        $content = $this->getEnumContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Create domain directories if they don't exist.
     */
    protected function createDomainDirectories(): void
    {
        $domainPath = $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain;
        $enumsPath = $domainPath.DIRECTORY_SEPARATOR.'Enums';

        $this->createDirectory($enumsPath);
    }

    /**
     * Get the enum file path.
     */
    protected function getEnumPath(): string
    {
        return $this->getDomainPath().DIRECTORY_SEPARATOR.'Enums'.DIRECTORY_SEPARATOR.$this->name.'.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain;
    }

    /**
     * Get the enum content.
     */
    protected function getEnumContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain).'\\Enums';
        $stubPath = $this->getStubPath('enum');
        $stub = $this->loadStub($stubPath);

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }
}
