<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Generators\ValueObjectGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:value-object',
    description: 'Create a new domain Value Object',
)]
class MakeValueObjectCommand extends PulsarCommand
{
    /**
     * Handle the command execution.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $domain = $this->argument('domain');

        try {
            $filePath = (new ValueObjectGenerator($name, $domain))->generate();

            $this->line();
            $this->success('Value Object created successfully');
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
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the Value Object');
        $this->addArgument('domain', InputArgument::REQUIRED, 'The name of the domain');
    }
}
