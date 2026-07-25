<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\DomainDoesNotExistException;
use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

class EventGenerator extends Generator
{
    /**
     * Create a new EventGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $domain,
    ) {}

    /**
     * Generate the event file.
     *
     * @throws FileAlreadyExistsException
     * @throws DomainDoesNotExistException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->domain]);
        $this->validateDomainExists($this->domain);

        $filePath = $this->getEventPath();

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Event', $this->name, $this->domain);
        }

        $this->createDomainDirectories();

        $content = $this->getEventContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Create domain directories if they don't exist.
     */
    protected function createDomainDirectories(): void
    {
        $domainPath = $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain;
        $eventsPath = $domainPath.DIRECTORY_SEPARATOR.'Events';

        $this->createDirectory($eventsPath);
    }

    /**
     * Get the event file path.
     */
    protected function getEventPath(): string
    {
        return $this->getDomainPath().DIRECTORY_SEPARATOR.'Events'.DIRECTORY_SEPARATOR.$this->name.'.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain;
    }

    /**
     * Get the event content.
     */
    protected function getEventContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain).'\\Events';
        $stubPath = $this->getStubPath('event');
        $stub = $this->loadStub($stubPath);

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }
}
