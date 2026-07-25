<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Generators\PolicyGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'make:policy',
    description: 'Create a new domain policy class',
)]
class MakePolicyCommand extends PulsarCommand
{
    /**
     * Handle the command execution.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $domain = $this->argument('domain');
        $modelOption = $this->option('model');
        $model = is_string($modelOption) ? $modelOption : null;

        try {
            $generator = new PolicyGenerator($name, $domain, $model);
            $filePath = $generator->generate();

            $this->line();
            $this->success('Policy created successfully');
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
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the policy');
        $this->addArgument('domain', InputArgument::REQUIRED, 'The name of the domain');
        $this->addOption('model', null, InputOption::VALUE_REQUIRED, 'The domain model protected by the policy');
    }
}
