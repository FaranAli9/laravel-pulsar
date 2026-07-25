<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

class ContractGenerator extends Generator
{
    /**
     * Create a new ContractGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $domain,
    ) {}

    /**
     * Generate the domain contract.
     *
     * @throws FileAlreadyExistsException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->domain]);
        $this->name = $this->normalizeName($this->name);
        $this->validateName($this->name);
        $this->validateDomainExists($this->domain);

        $contractsPath = $this->findDomainRootPath()
            .DIRECTORY_SEPARATOR.$this->domain
            .DIRECTORY_SEPARATOR.'Contracts';
        $filePath = $contractsPath.DIRECTORY_SEPARATOR.$this->name.'.php';

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Contract', $this->name, $this->domain);
        }

        $this->createDirectory($contractsPath);
        $stub = $this->loadStub($this->getStubPath('contract'));
        $content = $this->replaceStubPlaceholders($stub, [
            'namespace' => $this->findDomainNamespace($this->domain).'\\Contracts',
            'name' => $this->name,
        ]);
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Remove redundant contract suffixes from capability names.
     */
    protected function normalizeName(string $name): string
    {
        return preg_replace('/(?:Contract|Interface)$/', '', $name) ?? $name;
    }
}
