<?php

namespace Faran\Pulsar\Generators;

use Exception;

class EventGenerator extends Generator
{
    /**
     * The name of the event to generate.
     */
    protected string $name;

    /**
     * The name of the domain.
     */
    protected string $domain;

    /**
     * Create a new EventGenerator instance.
     *
     * @param  string  $name
     * @param  string  $domain
     */
    public function __construct(string $name, string $domain)
    {
        $this->name = $name;
        $this->domain = $domain;
    }

    /**
     * Generate the event file.
     *
     * @throws Exception
     */
    public function generate(): string
    {
        $this->createDomainDirectories();

        $filePath = $this->getEventPath();

        if ($this->fileExists($filePath)) {
            throw new Exception("Event [{$this->name}] already exists in {$this->domain}!");
        }

        $content = $this->getEventContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Create domain directories if they don't exist.
     */
    protected function createDomainDirectories(): void
    {
        $domainPath = $this->getDomainPath();
        $eventsPath = $domainPath . DIRECTORY_SEPARATOR . 'Events';

        $this->createDirectory($domainPath);
        $this->createDirectory($eventsPath);
    }

    /**
     * Get the event file path.
     */
    protected function getEventPath(): string
    {
        return $this->getDomainPath() . DIRECTORY_SEPARATOR . 'Events' . DIRECTORY_SEPARATOR . $this->name . '.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath() . DIRECTORY_SEPARATOR . $this->domain;
    }

    /**
     * Get the event content.
     */
    protected function getEventContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain) . "\\Events";
        $stubPath = $this->getStubPath('event');
        $stub = $this->loadStub($stubPath);

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }

}
