<?php

namespace Faran\Pulsar\Generators;

use Exception;

class DtoGenerator extends Generator
{
    /**
     * The name of the DTO to generate.
     */
    protected string $name;

    /**
     * The name of the domain.
     */
    protected string $domain;

    /**
     * Create a new DtoGenerator instance.
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
     * Generate the DTO file.
     *
     * @throws Exception
     */
    public function generate(): string
    {
        $this->createDomainDirectories();

        $filePath = $this->getDtoPath();

        if ($this->fileExists($filePath)) {
            throw new Exception("DTO [{$this->name}] already exists in {$this->domain}!");
        }

        $content = $this->getDtoContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Create domain directories if they don't exist.
     */
    protected function createDomainDirectories(): void
    {
        $domainPath = $this->getDomainPath();
        $dtosPath = $domainPath . DIRECTORY_SEPARATOR . 'DTOs';

        $this->createDirectory($domainPath);
        $this->createDirectory($dtosPath);
    }

    /**
     * Get the DTO file path.
     */
    protected function getDtoPath(): string
    {
        return $this->getDomainPath() . DIRECTORY_SEPARATOR . 'DTOs' . DIRECTORY_SEPARATOR . $this->name . '.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath() . DIRECTORY_SEPARATOR . $this->domain;
    }

    /**
     * Get the DTO content.
     */
    protected function getDtoContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain) . "\\DTOs";
        $stubPath = $this->getStubPath('dto');
        $stub = $this->loadStub($stubPath);

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }

}

