<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;

class CommandGenerator extends Generator
{
    /**
     * Create a new CommandGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $module,
        protected string $service,
        protected ?string $signature = null,
    ) {}

    /**
     * Generate the application console command.
     *
     * @throws FileAlreadyExistsException
     * @throws ServiceDoesNotExistException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->service, $this->module]);
        $this->validateServiceExists();

        $commandsPath = $this->getModulePath().DIRECTORY_SEPARATOR.'Commands';
        $filePath = $commandsPath.DIRECTORY_SEPARATOR.$this->name.'.php';

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Command', $this->name, "{$this->service}/{$this->module}");
        }

        $this->createDirectory($commandsPath);
        $stub = $this->loadStub($this->getStubPath('command'));
        $content = $this->replaceStubPlaceholders($stub, [
            'namespace' => $this->findServiceNamespace($this->service)."\\Modules\\{$this->module}\\Commands",
            'useCaseNamespace' => $this->findServiceNamespace($this->service)."\\Modules\\{$this->module}\\UseCases",
            'useCase' => $this->useCaseName(),
            'signature' => var_export($this->signature ?? $this->defaultSignature(), true),
            'name' => $this->name,
        ]);
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Validate that the target service exists.
     *
     * @throws ServiceDoesNotExistException
     */
    protected function validateServiceExists(): void
    {
        if (! $this->serviceExists($this->service)) {
            throw ServiceDoesNotExistException::make($this->service);
        }
    }

    /**
     * Get the target module path.
     */
    protected function getModulePath(): string
    {
        return $this->findServicesRootPath().DIRECTORY_SEPARATOR.$this->service
            .DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.$this->module;
    }

    /**
     * Derive the single UseCase dependency from the command name.
     */
    protected function useCaseName(): string
    {
        $baseName = preg_replace('/Command$/', '', $this->name) ?? $this->name;

        return $baseName.'UseCase';
    }

    /**
     * Build the default Artisan signature.
     */
    protected function defaultSignature(): string
    {
        return $this->generateSlug($this->module).':'.$this->generateSlug(
            preg_replace('/Command$/', '', $this->name) ?? $this->name,
        );
    }
}
