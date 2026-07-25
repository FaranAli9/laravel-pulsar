<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

class DomainGenerator extends Generator
{
    /**
     * Create a new DomainGenerator instance.
     */
    public function __construct(protected string $name) {}

    /**
     * Generate the domain directory and marker file.
     *
     * @throws FileAlreadyExistsException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->name]);

        if ($this->domainExists($this->name)) {
            throw FileAlreadyExistsException::make('Domain', $this->name, 'app/Pulsar/Domain');
        }

        $domainPath = $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->name;

        $this->createDirectory($domainPath);
        $this->createGitkeep($domainPath);

        return $this->getRelativePath($domainPath.DIRECTORY_SEPARATOR.'.gitkeep');
    }
}
