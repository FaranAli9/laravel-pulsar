<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Generators\InstallGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'install',
    description: 'Wire Pulsar discovery and its application service provider',
)]
class InstallCommand extends PulsarCommand
{
    /**
     * Handle the command execution.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        try {
            $result = (new InstallGenerator($dryRun, $force))->generate();

            $this->line();

            if (! $result->changed()) {
                $this->success('Pulsar is already installed; no changes were needed.');
                $this->line();

                return Command::SUCCESS;
            }

            if ($result->dryRun) {
                $this->info('Dry run only; no files were changed.');
                $this->line();
                $this->output->writeln($result->diff, OutputInterface::OUTPUT_RAW);

                return Command::SUCCESS;
            }

            $this->success('Pulsar installed successfully');

            foreach ($result->changedPaths as $path) {
                $this->info("Updated: {$path}");
            }

            if ($result->backupPath !== null) {
                $this->info("Backup: {$result->backupPath}");
            }

            $this->line();

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $this->line();
            $this->error($exception->getMessage());
            $this->line();

            return Command::FAILURE;
        }
    }

    /**
     * Configure the command options.
     */
    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print the planned diff without writing files')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Re-apply the generated provider while preserving wiring');
    }
}
