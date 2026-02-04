<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Generators\SkillGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'publish:skill',
    description: 'Publish Claude Code skill for Pulsar architecture enforcement',
)]
class PublishSkillCommand extends PulsarCommand
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
            $generator = new SkillGenerator($force, $path);
            $filePath = $generator->generate();

            $this->success("Pulsar skill published to {$filePath}!");
            $this->info("Claude Code will auto-detect this skill and enforce Pulsar architecture rules");

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
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing skill file');
        $this->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Custom output path (default: .claude/skills/pulsar/SKILL.md)');
    }
}
