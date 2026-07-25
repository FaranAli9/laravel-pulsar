<?php

namespace Faran\Pulsar\Generators;

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
     * The optional domain model protected by the policy.
     */
    protected ?string $model;

    /**
     * Create a new PolicyGenerator instance.
     */
    public function __construct(string $name, string $domain, ?string $model = null)
    {
        $this->name = $name;
        $this->domain = $domain;
        $this->model = $model;
    }

    /**
     * Generate the policy file.
     *
     * @throws Exception
     */
    public function generate(): string
    {
        $names = [$this->name];

        if ($this->model !== null) {
            $names[] = $this->model;
        }

        $this->validateInputs($names, [$this->domain]);
        $this->validateDomainExists($this->domain);
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
        $domainPath = $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain;
        $policiesPath = $domainPath.DIRECTORY_SEPARATOR.'Policies';

        $this->createDirectory($policiesPath);
    }

    /**
     * Get the policy file path.
     */
    protected function getPolicyPath(): string
    {
        return $this->getDomainPath().DIRECTORY_SEPARATOR.'Policies'.DIRECTORY_SEPARATOR.$this->name.'.php';
    }

    /**
     * Get the domain path.
     */
    protected function getDomainPath(): string
    {
        return $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain;
    }

    /**
     * Get the policy content.
     */
    protected function getPolicyContent(): string
    {
        $namespace = $this->findDomainNamespace($this->domain).'\\Policies';
        $stubPath = $this->getStubPath($this->model === null ? 'policy' : 'policy-model');
        $stub = $this->loadStub($stubPath);
        $replacements = [
            'namespace' => $namespace,
            'name' => $this->name,
        ];

        if ($this->model !== null) {
            $replacements['model'] = $this->model;
            $replacements['modelFqcn'] = $this->findDomainNamespace($this->domain).'\\Models\\'.$this->model;
        }

        return $this->replaceStubPlaceholders($stub, $replacements);
    }
}
