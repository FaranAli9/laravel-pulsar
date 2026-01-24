<?php

namespace Faran\Pulse\Generators;

use Exception;

class ModelGenerator extends Generator
{
    /**
     * The name of the model to generate.
     */
    protected string $name;

    /**
     * The name of the domain.
     */
    protected string $domain;

    /**
     * Create a new ModelGenerator instance.
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
     * Generate the model file.
     *
     * @throws Exception
     */
    public function generate(): string
    {
        $this->createDomainDirectories();

        $filePath = $this->getModelPath();

        if ($this->fileExists($filePath)) {
            throw new Exception("Model [{$this->name}] already exists in {$this->domain}!");
        }

        $content = $this->getModelContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Create domain directories if they don't exist.
     */
    protected function createDomainDirectories(): void
    {
        $domainPath = $this->getDomainPath();
        $modelsPath = $domainPath . DIRECTORY_SEPARATOR . 'Models';

        $this->createDirectory($domainPath);
        $this->createDirectory($modelsPath);
    }

    /**
     * Get the model file path.
     */
    protected function getModelPath(): string
    {
        return $this->getDomainPath() . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . $this->name . '.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath() . DIRECTORY_SEPARATOR . $this->domain;
    }

    /**
     * Get the model content.
     */
    protected function getModelContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain) . "\\Models";
        $stubPath = $this->getStubPath('model');
        $stub = $this->loadStub($stubPath);

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }

}
