<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Generators\CommandGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'make:command',
    description: 'Create a new application console command',
)]
class MakeCommandCommand extends PulsarCommand
{
    /**
     * Handle the command execution.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $module = $this->argument('module');
        $service = $this->argument('service');
        $signatureOption = $this->option('signature');
        $signature = is_string($signatureOption) ? $signatureOption : null;

        try {
            $filePath = (new CommandGenerator($name, $module, $service, $signature))->generate();

            $this->line();
            $this->success('Command created successfully');
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
     * Configure the command arguments and options.
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the command');
        $this->addArgument('module', InputArgument::REQUIRED, 'The name of the module');
        $this->addArgument('service', InputArgument::REQUIRED, 'The name of the service');
        $this->addOption('signature', null, InputOption::VALUE_REQUIRED, 'The Artisan command signature');
    }
}
