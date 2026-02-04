<?php

use Faran\Pulsar\Generators\SkillGenerator;
use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

describe('Skill Generator', function () {

    describe('Happy Path', function () {

        it('publishes skill file', function () {
            $generator = new SkillGenerator();
            $relativePath = $generator->generate();

            $fullPath = $this->tempDir . DIRECTORY_SEPARATOR . $relativePath;
            expect(file_exists($fullPath))->toBeTrue();
        });

        it('publishes to .claude/skills/pulsar/SKILL.md', function () {
            $generator = new SkillGenerator();
            $relativePath = $generator->generate();

            expect($relativePath)->toBe(
                '.claude' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . 'pulsar' . DIRECTORY_SEPARATOR . 'SKILL.md'
            );
        });

        it('returns relative path', function () {
            $generator = new SkillGenerator();
            $relativePath = $generator->generate();

            expect($relativePath)->not->toStartWith($this->tempDir);
        });

        it('creates parent directories', function () {
            $generator = new SkillGenerator();
            $generator->generate();

            $skillDir = $this->tempDir . DIRECTORY_SEPARATOR . '.claude' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . 'pulsar';
            expect(is_dir($skillDir))->toBeTrue();
        });

        it('content contains architecture sections', function () {
            $generator = new SkillGenerator();
            $relativePath = $generator->generate();

            $content = file_get_contents($this->tempDir . DIRECTORY_SEPARATOR . $relativePath);

            expect($content)
                ->toContain('Layer Responsibilities')
                ->toContain('Dependency Rules')
                ->toContain('Transaction Ownership')
                ->toContain('Forbidden Patterns')
                ->toContain('Data Flow Rules');
        });

        it('content contains file locations', function () {
            $generator = new SkillGenerator();
            $relativePath = $generator->generate();

            $content = file_get_contents($this->tempDir . DIRECTORY_SEPARATOR . $relativePath);

            expect($content)
                ->toContain('app/Services/{Service}/')
                ->toContain('app/Domain/{Domain}/');
        });

        it('content has no stub placeholders', function () {
            $generator = new SkillGenerator();
            $relativePath = $generator->generate();

            $content = file_get_contents($this->tempDir . DIRECTORY_SEPARATOR . $relativePath);

            expect($content)->not->toMatch('/\{\{[a-zA-Z_]+\}\}/');
        });

        it('content has skill frontmatter', function () {
            $generator = new SkillGenerator();
            $relativePath = $generator->generate();

            $content = file_get_contents($this->tempDir . DIRECTORY_SEPARATOR . $relativePath);

            expect($content)
                ->toStartWith('---')
                ->toContain('description:');
        });
    });

    describe('Error Cases', function () {

        it('throws if file already exists', function () {
            $generator1 = new SkillGenerator();
            $generator1->generate();

            $generator2 = new SkillGenerator();

            expect(fn() => $generator2->generate())
                ->toThrow(FileAlreadyExistsException::class, 'already exists');
        });

        it('error message mentions SKILL.md', function () {
            $generator1 = new SkillGenerator();
            $generator1->generate();

            try {
                $generator2 = new SkillGenerator();
                $generator2->generate();
                $this->fail('Should have thrown exception');
            } catch (FileAlreadyExistsException $e) {
                expect($e->getMessage())->toContain('SKILL.md');
            }
        });
    });

    describe('Force Overwrite', function () {

        it('overwrites existing file with force flag', function () {
            // Create initial file with different content
            $skillDir = $this->tempDir . DIRECTORY_SEPARATOR . '.claude' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . 'pulsar';
            mkdir($skillDir, 0755, true);
            $filePath = $skillDir . DIRECTORY_SEPARATOR . 'SKILL.md';
            file_put_contents($filePath, 'existing content');

            $generator = new SkillGenerator(force: true);
            $relativePath = $generator->generate();

            $content = file_get_contents($this->tempDir . DIRECTORY_SEPARATOR . $relativePath);
            expect($content)
                ->not->toBe('existing content')
                ->toContain('Pulsar Architecture');
        });

        it('creates file when none exists with force flag', function () {
            $generator = new SkillGenerator(force: true);
            $relativePath = $generator->generate();

            $fullPath = $this->tempDir . DIRECTORY_SEPARATOR . $relativePath;
            expect(file_exists($fullPath))->toBeTrue();
        });
    });

    describe('Custom Path', function () {

        it('publishes to custom path', function () {
            $generator = new SkillGenerator(force: false, path: '.claude/skills/custom/SKILL.md');
            $relativePath = $generator->generate();

            expect($relativePath)->toBe('.claude' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'SKILL.md');
            expect(file_exists($this->tempDir . DIRECTORY_SEPARATOR . $relativePath))->toBeTrue();
        });

        it('creates parent directories for custom path', function () {
            $generator = new SkillGenerator(force: false, path: 'docs/skills/pulsar.md');
            $generator->generate();

            $parentDir = $this->tempDir . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'skills';
            expect(is_dir($parentDir))->toBeTrue();
        });

        it('uses default path when none provided', function () {
            $generator = new SkillGenerator();
            $relativePath = $generator->generate();

            expect($relativePath)->toBe(
                '.claude' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . 'pulsar' . DIRECTORY_SEPARATOR . 'SKILL.md'
            );
        });
    });
});
