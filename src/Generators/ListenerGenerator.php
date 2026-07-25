<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

class ListenerGenerator extends Generator
{
    /**
     * Create a new ListenerGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $domain,
        protected ?string $event = null,
        protected bool $queued = false,
    ) {}

    /**
     * Generate the domain event listener.
     *
     * @throws FileAlreadyExistsException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->domain]);
        $this->validateEvent();
        $this->validateDomainExists($this->domain);

        $listenersPath = $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain
            .DIRECTORY_SEPARATOR.'Listeners';
        $filePath = $listenersPath.DIRECTORY_SEPARATOR.$this->name.'.php';

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Listener', $this->name, $this->domain);
        }

        $this->createDirectory($listenersPath);
        $stubName = $this->queued ? 'listener-queued' : 'listener';
        $stub = $this->loadStub($this->getStubPath($stubName));
        $eventClass = $this->eventClass();
        $content = $this->replaceStubPlaceholders($stub, [
            'namespace' => $this->findDomainNamespace($this->domain).'\\Listeners',
            'eventImport' => $this->eventImport(),
            'event' => $eventClass ?? 'mixed',
            'name' => $this->name,
        ]);
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Validate the optional event class name.
     */
    protected function validateEvent(): void
    {
        if ($this->event !== null) {
            $this->validateName(ltrim($this->event, '\\'), 'event');
        }
    }

    /**
     * Resolve an event name to its import.
     */
    protected function eventImport(): string
    {
        if ($this->event === null) {
            return '';
        }

        $event = ltrim($this->event, '\\');
        $fqcn = str_contains($event, '\\')
            ? $event
            : $this->findDomainNamespace($this->domain).'\\Events\\'.$event;

        return "use {$fqcn};";
    }

    /**
     * Get the short event class name.
     */
    protected function eventClass(): ?string
    {
        if ($this->event === null) {
            return null;
        }

        $event = ltrim($this->event, '\\');
        $separatorPosition = strrpos($event, '\\');

        return $separatorPosition === false ? $event : substr($event, $separatorPosition + 1);
    }
}
