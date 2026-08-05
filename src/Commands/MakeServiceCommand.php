<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Generators\ServiceGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'make:service',
    description: 'Create a new Service'
)]
class MakeServiceCommand extends PulsarCommand
{
    /**
     * Handle the command execution.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $web = (bool) $this->option('web');

        try {
            $generator = new ServiceGenerator($name, $web);
            $generator->generate();

            $this->line();
            $this->success("{$name} Service created successfully!");
            $this->info("Location: app/Pulsar/Services/{$name}");
            $this->info("Register: App\\Pulsar\\Services\\{$name}\\Providers\\{$name}ServiceProvider::class");
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
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the service')
            ->addOption('web', null, InputOption::VALUE_NONE, 'Generate session-backed browser routes');
    }
}
