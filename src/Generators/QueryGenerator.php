<?php

namespace Faran\Pulse\Generators;

use Exception;

class QueryGenerator extends Generator
{
    /**
     * The name of the query to generate.
     */
    protected string $name;

    /**
     * The name of the domain.
     */
    protected string $domain;

    /**
     * Create a new QueryGenerator instance.
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
     * Generate the query file.
     *
     * @throws Exception
     */
    public function generate(): string
    {
        $this->createDomainDirectories();

        $filePath = $this->getQueryPath();

        if ($this->fileExists($filePath)) {
            throw new Exception("Query [{$this->name}] already exists in {$this->domain}!");
        }

        $content = $this->getQueryContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Create domain directories if they don't exist.
     */
    protected function createDomainDirectories(): void
    {
        $domainPath = $this->getDomainPath();
        $queriesPath = $domainPath . DIRECTORY_SEPARATOR . 'Queries';

        $this->createDirectory($domainPath);
        $this->createDirectory($queriesPath);
    }

    /**
     * Get the query file path.
     */
    protected function getQueryPath(): string
    {
        return $this->getDomainPath() . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . $this->name . '.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath() . DIRECTORY_SEPARATOR . $this->domain;
    }

    /**
     * Get the query content.
     */
    protected function getQueryContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain) . "\\Queries";
        $stubPath = $this->getStubPath('query');
        $stub = $this->loadStub($stubPath);

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }

}
