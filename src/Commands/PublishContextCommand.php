<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
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
        $path = $this->option('path');

        try {
            $generator = new ContextGenerator($force, $path);
            $filePath = $generator->generate();

            $this->success("Pulsar context published to {$filePath}!");
            $this->info("💡 Tip: Merge into your CLAUDE.md or .cursorrules for AI awareness");

            return Command::SUCCESS;
        } catch (FileAlreadyExistsException $e) {
            $this->error($e->getMessage());
            $this->info("  Use --force to overwrite or --path to specify a different location");

            return Command::FAILURE;
        } catch (Exception $e) {
            $this->error($e->getMessage());

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
        $this->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Custom output path (default: PULSAR.md)');
    }
}
