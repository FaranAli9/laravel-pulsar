<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

class ValueObjectGenerator extends Generator
{
    /**
     * Create a new ValueObjectGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $domain,
    ) {}

    /**
     * Generate the domain Value Object.
     *
     * @throws FileAlreadyExistsException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->domain]);
        $this->validateDomainExists($this->domain);

        $valueObjectsPath = $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain
            .DIRECTORY_SEPARATOR.'ValueObjects';
        $filePath = $valueObjectsPath.DIRECTORY_SEPARATOR.$this->name.'.php';

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Value Object', $this->name, $this->domain);
        }

        $this->createDirectory($valueObjectsPath);
        $stub = $this->loadStub($this->getStubPath('value-object'));
        $content = $this->replaceStubPlaceholders($stub, [
            'namespace' => $this->findDomainNamespace($this->domain).'\\ValueObjects',
            'name' => $this->name,
        ]);
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }
}
