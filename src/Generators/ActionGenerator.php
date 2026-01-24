<?php

namespace Faran\Pulse\Generators;

use Exception;

class ActionGenerator extends Generator
{
    /**
     * The name of the action to generate.
     */
    protected string $name;

    /**
     * The name of the domain.
     */
    protected string $domain;

    /**
     * Create a new ActionGenerator instance.
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
     * Generate the action file.
     *
     * @throws Exception
     */
    public function generate(): string
    {
        $this->createDomainDirectories();

        $filePath = $this->getActionPath();

        if ($this->fileExists($filePath)) {
            throw new Exception("Action [{$this->name}] already exists in {$this->domain}!");
        }

        $content = $this->getActionContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Create domain directories if they don't exist.
     */
    protected function createDomainDirectories(): void
    {
        $domainPath = $this->getDomainPath();
        $actionsPath = $domainPath . DIRECTORY_SEPARATOR . 'Actions';

        $this->createDirectory($domainPath);
        $this->createDirectory($actionsPath);
    }

    /**
     * Get the action file path.
     */
    protected function getActionPath(): string
    {
        return $this->getDomainPath() . DIRECTORY_SEPARATOR . 'Actions' . DIRECTORY_SEPARATOR . $this->name . '.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath() . DIRECTORY_SEPARATOR . $this->domain;
    }

    /**
     * Get the action content.
     */
    protected function getActionContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain) . "\\Actions";
        $stubPath = $this->getStubPath('action');
        $stub = $this->loadStub($stubPath);

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }

}

