<?php

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Generators\DomainGenerator;

describe('Domain Generator', function () {
    it('creates an explicit domain with a gitkeep marker', function () {
        $relativePath = (new DomainGenerator('Billing'))->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Billing', '.gitkeep',
        ]);
        $fullPath = $this->tempDir.DIRECTORY_SEPARATOR.$expectedPath;

        expect($relativePath)->toBe($expectedPath)
            ->and(is_dir(dirname($fullPath)))->toBeTrue()
            ->and(file_exists($fullPath))->toBeTrue()
            ->and(file_get_contents($fullPath))->toBe('');
    });

    it('rejects a duplicate domain', function () {
        (new DomainGenerator('Billing'))->generate();

        expect(fn () => (new DomainGenerator('Billing'))->generate())
            ->toThrow(FileAlreadyExistsException::class, 'already exists');
    });

    it('rejects invalid, reserved, and traversing domain names before writing', function (string $name) {
        expect(fn () => (new DomainGenerator($name))->generate())
            ->toThrow(Exception::class, $name);
    })->with([
        'reserved keyword' => 'class',
        'invalid characters' => 'Bad-Domain',
        'path traversal' => '../Billing',
    ]);
});
