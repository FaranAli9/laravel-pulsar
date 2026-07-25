<?php

namespace Faran\Pulsar\Generators;

final readonly class InstallResult
{
    /**
     * Create a new install result.
     *
     * @param  list<string>  $changedPaths
     */
    public function __construct(
        public bool $dryRun,
        public array $changedPaths,
        public string $diff,
        public ?string $backupPath = null,
    ) {}

    /**
     * Determine whether installation planned or wrote any changes.
     */
    public function changed(): bool
    {
        return $this->changedPaths !== [];
    }
}
