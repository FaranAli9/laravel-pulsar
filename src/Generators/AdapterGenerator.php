<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use InvalidArgumentException;

class AdapterGenerator extends Generator
{
    protected ?string $resolvedContract = null;

    protected ?string $contractName = null;

    /**
     * Create a new AdapterGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $area,
        protected ?string $contract = null,
        protected ?string $domain = null,
    ) {}

    /**
     * Generate the infrastructure adapter.
     *
     * @throws FileAlreadyExistsException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->area]);
        $this->resolveContract();

        $areaPath = $this->findInfrastructureRootPath().DIRECTORY_SEPARATOR.$this->area;
        $filePath = $areaPath.DIRECTORY_SEPARATOR.$this->name.'.php';

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Adapter', $this->name, $this->area);
        }

        $this->createDirectory($areaPath);
        $stub = $this->loadStub($this->getStubPath('adapter'));
        $content = $this->replaceStubPlaceholders($stub, [
            'namespace' => $this->findInfrastructureNamespace($this->area),
            'contractImport' => $this->resolvedContract === null ? '' : "use {$this->resolvedContract};",
            'implements' => $this->contractName === null ? '' : "implements {$this->contractName}",
            'contract' => $this->contractName ?? 'Contract',
            'name' => $this->name,
        ]);
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Get the container binding line for a contract-backed adapter.
     */
    public function bindingLine(): ?string
    {
        if ($this->contractName === null) {
            return null;
        }

        return '$this->app->bind('.$this->contractName.'::class, '.$this->name.'::class);';
    }

    /**
     * Resolve and validate the optional contract selection.
     */
    protected function resolveContract(): void
    {
        if (($this->contract === null) !== ($this->domain === null)) {
            throw new InvalidArgumentException('Options [--contract] and [--domain] must be provided together.');
        }

        if ($this->contract === null || $this->domain === null) {
            return;
        }

        $contract = ltrim($this->contract, '\\');

        $this->validateInputs([$contract], [$this->domain]);
        $this->validateDomainExists($this->domain);

        $separatorPosition = strrpos($contract, '\\');
        $this->contractName = $separatorPosition === false
            ? $contract
            : substr($contract, $separatorPosition + 1);
        $this->resolvedContract = $separatorPosition === false
            ? $this->findDomainNamespace($this->domain).'\\Contracts\\'.$contract
            : $contract;
    }
}
