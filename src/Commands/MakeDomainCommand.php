<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Generators\DomainGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:domain',
    description: 'Create a new domain',
)]
class MakeDomainCommand extends PulsarCommand
{
    /**
     * Handle the command execution.
     */
    public function handle(): int
    {
        $name = $this->argument('name');

        try {
            $generator = new DomainGenerator($name);
            $filePath = $generator->generate();

            $this->line();
            $this->success('Domain created successfully');
            $this->line();
            $this->info("Location: {$filePath}");
            $this->line();

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->line();
            $this->error($e->getMessage());
            $this->line();

            return Command::FAILURE;
        }
    }

    /**
     * Configure the command arguments.
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the domain');
    }
}
