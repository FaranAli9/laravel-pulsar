<?php

use Faran\Pulsar\Generators\ContextGenerator;
use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

describe('Context Generator', function () {

    describe('Happy Path', function () {

        it('publishes context file', function () {
            $generator = new ContextGenerator();
            $relativePath = $generator->generate();

            $fullPath = $this->tempDir . DIRECTORY_SEPARATOR . $relativePath;
            expect(file_exists($fullPath))->toBeTrue();
        });

        it('publishes to project root', function () {
            $generator = new ContextGenerator();
            $relativePath = $generator->generate();

            expect($relativePath)->toBe('PULSAR.md');
        });

        it('returns relative path', function () {
            $generator = new ContextGenerator();
            $relativePath = $generator->generate();

            expect($relativePath)->not->toStartWith($this->tempDir);
        });

        it('content contains architecture sections', function () {
            $generator = new ContextGenerator();
            $relativePath = $generator->generate();

            $content = file_get_contents($this->tempDir . DIRECTORY_SEPARATOR . $relativePath);

            expect($content)
                ->toContain('Service Layer')
                ->toContain('Domain Layer')
                ->toContain('Layer Responsibilities')
                ->toContain('Dependency Rules')
                ->toContain('Transaction Boundaries');
        });

        it('content has no stub placeholders', function () {
            $generator = new ContextGenerator();
            $relativePath = $generator->generate();

            $content = file_get_contents($this->tempDir . DIRECTORY_SEPARATOR . $relativePath);

            expect($content)->not->toMatch('/\{\{[a-zA-Z_]+\}\}/');
        });
    });

    describe('Error Cases', function () {

        it('throws if file already exists', function () {
            $generator1 = new ContextGenerator();
            $generator1->generate();

            $generator2 = new ContextGenerator();

            expect(fn() => $generator2->generate())
                ->toThrow(FileAlreadyExistsException::class, 'already exists');
        });

        it('error message mentions context file', function () {
            $generator1 = new ContextGenerator();
            $generator1->generate();

            try {
                $generator2 = new ContextGenerator();
                $generator2->generate();
                $this->fail('Should have thrown exception');
            } catch (FileAlreadyExistsException $e) {
                expect($e->getMessage())->toContain('PULSAR.md');
            }
        });
    });

    describe('Force Overwrite', function () {

        it('overwrites existing file with force flag', function () {
            // Create initial file with different content
            $filePath = $this->tempDir . DIRECTORY_SEPARATOR . 'PULSAR.md';
            file_put_contents($filePath, 'existing content');

            $generator = new ContextGenerator(force: true);
            $relativePath = $generator->generate();

            $content = file_get_contents($this->tempDir . DIRECTORY_SEPARATOR . $relativePath);
            expect($content)
                ->not->toBe('existing content')
                ->toContain('Pulsar Architecture');
        });

        it('creates file when none exists with force flag', function () {
            $generator = new ContextGenerator(force: true);
            $relativePath = $generator->generate();

            $fullPath = $this->tempDir . DIRECTORY_SEPARATOR . $relativePath;
            expect(file_exists($fullPath))->toBeTrue();
        });
    });
});
