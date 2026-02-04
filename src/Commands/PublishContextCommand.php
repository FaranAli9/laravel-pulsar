<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Generators\ContextGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'publish:context',
    description: 'Publish architecture context file for AI assistants',
)]
class PublishContextCommand extends PulsarCommand
{
    /**
     * Handle the command execution.
     *
     * @return int
     */
    public function handle(): int
    {
        $force = $this->option('force');

        try {
            $generator = new ContextGenerator($force);
            $filePath = $generator->generate();

            $this->line();
            $this->success("Context file published successfully");
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
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing context file');
    }
}
