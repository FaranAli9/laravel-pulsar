<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Generators\AdapterGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'make:adapter',
    description: 'Create a new infrastructure adapter',
)]
class MakeAdapterCommand extends PulsarCommand
{
    /**
     * Handle the command execution.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $area = $this->argument('area');
        $contractOption = $this->option('contract');
        $domainOption = $this->option('domain');
        $contract = is_string($contractOption) ? $contractOption : null;
        $domain = is_string($domainOption) ? $domainOption : null;

        try {
            $generator = new AdapterGenerator($name, $area, $contract, $domain);
            $filePath = $generator->generate();

            $this->line();
            $this->success('Adapter created successfully');
            $this->line();
            $this->info("Location: {$filePath}");

            if (($bindingLine = $generator->bindingLine()) !== null) {
                $this->info("Bind in PulsarServiceProvider: {$bindingLine}");
            }

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
     * Configure the command arguments and options.
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the adapter');
        $this->addArgument('area', InputArgument::REQUIRED, 'The infrastructure capability area');
        $this->addOption('contract', null, InputOption::VALUE_REQUIRED, 'The Contract FQCN or capability name');
        $this->addOption('domain', null, InputOption::VALUE_REQUIRED, 'The domain that owns the Contract');
    }
}
