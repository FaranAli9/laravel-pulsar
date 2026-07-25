<?php

use Faran\Pulsar\Generators\EnumGenerator;

beforeEach(function () {
    createDomain($this->tempDir, 'Orders');
});

describe('Enum Generator', function () {
    it('generates the current enum contract', function () {
        $generator = new EnumGenerator('OrderStatus', 'Orders');
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Orders', 'Enums', 'OrderStatus.php',
        ]);

        expect($relativePath)->toBe($expectedPath);

        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\Enums')
            ->toContain('enum OrderStatus: string')
            ->not->toContain('{{');
    });

    it('rejects a duplicate enum', function () {
        (new EnumGenerator('OrderStatus', 'Orders'))->generate();

        expect(fn () => (new EnumGenerator('OrderStatus', 'Orders'))->generate())
            ->toThrow(Exception::class, 'already exists');
    });

    it('rejects a missing domain', function () {
        expect(fn () => (new EnumGenerator('OrderStatus', 'Missing'))->generate())
            ->toThrow(Exception::class, 'Domain [Missing] does not exist');
    });
});
