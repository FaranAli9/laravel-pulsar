<?php

namespace Faran\Pulse\Generators;

use Exception;

class PolicyGenerator extends Generator
{
    /**
     * The name of the policy to generate.
     */
    protected string $name;

    /**
     * The name of the domain.
     */
    protected string $domain;

    /**
     * Create a new PolicyGenerator instance.
     *
     * @param  string  $name
     * @param  string  $domain
     */
    public function __construct(string $name, string $domain)
    {
        $this->name = $this->ensurePolicySuffix($name);
        $this->domain = $domain;
    }

    /**
     * Generate the policy file.
     *
     * @throws Exception
     */
    public function generate(): string
    {
        $this->createDomainDirectories();

        $filePath = $this->getPolicyPath();

        if ($this->fileExists($filePath)) {
            throw new Exception("Policy [{$this->name}] already exists in {$this->domain}!");
        }

        $content = $this->getPolicyContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Create domain directories if they don't exist.
     */
    protected function createDomainDirectories(): void
    {
        $domainPath = $this->getDomainPath();
        $policiesPath = $domainPath . DIRECTORY_SEPARATOR . 'Policies';

        $this->createDirectory($domainPath);
        $this->createDirectory($policiesPath);
    }

    /**
     * Get the policy file path.
     */
    protected function getPolicyPath(): string
    {
        return $this->getDomainPath() . DIRECTORY_SEPARATOR . 'Policies' . DIRECTORY_SEPARATOR . $this->name . '.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath() . DIRECTORY_SEPARATOR . $this->domain;
    }

    /**
     * Get the policy content.
     */
    protected function getPolicyContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain) . "\\Policies";
        $stubPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'policy.stub';
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

    /**
     * Ensure policy suffix.
     */
    private function ensurePolicySuffix(string $name): string
    {
        return str_ends_with($name, 'Policy') ? $name : $name . 'Policy';
    }
}
