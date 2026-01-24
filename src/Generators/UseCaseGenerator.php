<?php

namespace Faran\Pulse\Generators;

use Exception;
use Faran\Pulse\Exceptions\ServiceDoesNotExistException;

class UseCaseGenerator extends Generator
{
    /**
     * The name of the use case to generate.
     */
    protected string $name;

    /**
     * The name of the module.
     */
    protected string $module;

    /**
     * The name of the service.
     */
    protected string $service;

    /**
     * Create a new UseCaseGenerator instance.
     *
     * @param  string  $name
     * @param  string  $module
     * @param  string  $service
     */
    public function __construct(string $name, string $module, string $service)
    {
        $this->name = $name;
        $this->module = $module;
        $this->service = $service;
    }

    /**
     * Generate the use case file.
     *
     * @throws Exception
     */
    public function generate(): string
    {
        $this->validateServiceExists();
        $this->createModuleDirectories();

        $filePath = $this->getUseCasePath();

        if ($this->fileExists($filePath)) {
            throw new Exception("UseCase [{$this->name}] already exists in {$this->service}/{$this->module}!");
        }

        $content = $this->getUseCaseContent();
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Validate that the service exists.
     *
     * @throws ServiceDoesNotExistException
     */
    protected function validateServiceExists(): void
    {
        if (!$this->serviceExists($this->service)) {
            throw ServiceDoesNotExistException::make($this->service);
        }
    }

    /**
     * Create module directories if they don't exist.
     */
    protected function createModuleDirectories(): void
    {
        $modulePath = $this->getModulePath();
        $useCasesPath = $modulePath . DIRECTORY_SEPARATOR . 'UseCases';

        $this->createDirectory($modulePath);
        $this->createDirectory($useCasesPath);
    }

    /**
     * Get the use case file path.
     */
    protected function getUseCasePath(): string
    {
        return $this->getModulePath() . DIRECTORY_SEPARATOR . 'UseCases' . DIRECTORY_SEPARATOR . $this->name . '.php';
    }

    /**
     * Get the module path.
     */
    protected function getModulePath(): string
    {
        return $this->findServicesRootPath() . DIRECTORY_SEPARATOR . $this->service . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $this->module;
    }

    /**
     * Get the use case content from stub.
     *
     * @return string
     * @throws Exception
     */
    protected function getUseCaseContent(): string
    {
        $stubPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'use-case.stub';

        if ($this->fileExists($stubPath)) {
            $stub = $this->loadStub($stubPath);
            return $this->replaceStubPlaceholders($stub, [
                'namespace' => $this->getNamespace(),
                'name' => $this->name,
                'service' => $this->service,
                'module' => $this->module,
            ]);
        }

        throw new Exception('UseCase stub not found');
    }

    /**
     * Get the use case namespace.
     *
     * @return string
     * @throws Exception
     */
    protected function getNamespace(): string
    {
        $rootNamespace = $this->findRootNamespace();
        return "{$rootNamespace}\\Services\\{$this->service}\\Modules\\{$this->module}\\UseCases";
    }

    /**
     * Get the relative path from the Laravel root.
     */
    protected function getRelativePath(string $fullPath): string
    {
        $laravelRoot = $this->findLaravelRoot();
        return str_replace($laravelRoot . DIRECTORY_SEPARATOR, '', $fullPath);
    }
}
