<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Generators\ContractGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:contract',
    description: 'Create a new domain contract interface',
)]
class MakeContractCommand extends PulsarCommand
{
    /**
     * Handle the command execution.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $domain = $this->argument('domain');

        try {
            $generator = new ContractGenerator($name, $domain);
            $filePath = $generator->generate();

            $this->line();
            $this->success('Contract created successfully');
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
        $this->addArgument('name', InputArgument::REQUIRED, 'The capability name of the contract');
        $this->addArgument('domain', InputArgument::REQUIRED, 'The name of the owning domain');
    }
}
