<?php

namespace Faran\Pulse\Generators;

use Exception;

class EnumGenerator extends Generator
{
    /**
     * The name of the enum to generate.
     */
    protected string $name;

    /**
     * The name of the domain.
     */
    protected string $domain;

    /**
     * Create a new EnumGenerator instance.
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
     * Generate the enum file.
     *
     * @throws Exception
     */
    public function generate(): string
    {
        $this->createDomainDirectories();

        $filePath = $this->getEnumPath();

        if ($this->fileExists($filePath)) {
            throw new Exception("Enum [{$this->name}] already exists in {$this->domain}!");
        }

        $content = $this->getEnumContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Create domain directories if they don't exist.
     */
    protected function createDomainDirectories(): void
    {
        $domainPath = $this->getDomainPath();
        $enumsPath = $domainPath . DIRECTORY_SEPARATOR . 'Enums';

        $this->createDirectory($domainPath);
        $this->createDirectory($enumsPath);
    }

    /**
     * Get the enum file path.
     */
    protected function getEnumPath(): string
    {
        return $this->getDomainPath() . DIRECTORY_SEPARATOR . 'Enums' . DIRECTORY_SEPARATOR . $this->name . '.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath() . DIRECTORY_SEPARATOR . $this->domain;
    }

    /**
     * Get the enum content.
     */
    protected function getEnumContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain) . "\\Enums";
        $stubPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'enum.stub';
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
