<?php

namespace Faran\Pulse\Generators;

use Exception;

class ExceptionGenerator extends Generator
{
    /**
     * The name of the exception to generate.
     */
    protected string $name;

    /**
     * The name of the domain.
     */
    protected string $domain;

    /**
     * Create a new ExceptionGenerator instance.
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
     * Generate the exception file.
     *
     * @throws Exception
     */
    public function generate(): string
    {
        $this->createDomainDirectories();

        $filePath = $this->getExceptionPath();

        if ($this->fileExists($filePath)) {
            throw new Exception("Exception [{$this->name}] already exists in {$this->domain}!");
        }

        $content = $this->getExceptionContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Create domain directories if they don't exist.
     */
    protected function createDomainDirectories(): void
    {
        $domainPath = $this->getDomainPath();
        $exceptionsPath = $domainPath . DIRECTORY_SEPARATOR . 'Exceptions';

        $this->createDirectory($domainPath);
        $this->createDirectory($exceptionsPath);
    }

    /**
     * Get the exception file path.
     */
    protected function getExceptionPath(): string
    {
        return $this->getDomainPath() . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . $this->name . '.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath() . DIRECTORY_SEPARATOR . $this->domain;
    }

    /**
     * Get the exception content.
     */
    protected function getExceptionContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain) . "\\Exceptions";
        $stubPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'exception.stub';
        $stub = $this->loadStub($stubPath);

        return $this->replaceStubPlaceholders($stub, [
            'namespace' => $namespace,
            'name' => $this->name,
        ]);
    }

    /**
     * Get the relative path for display.
     */
    protected function getRelativePath(string $filePath): string
    {
        return str_replace($this->findLaravelRoot() . DIRECTORY_SEPARATOR, '', $filePath);
    }
}
