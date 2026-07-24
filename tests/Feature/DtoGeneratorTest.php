<?php

use Faran\Pulsar\Generators\DtoGenerator;

describe('DTO Generator', function () {
    it('generates the current DTO contract', function () {
        $generator = new DtoGenerator('OrderData', 'Orders');
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Orders', 'DTOs', 'OrderData.php',
        ]);

        expect($relativePath)->toBe($expectedPath);

        $fullPath = $this->tempDir.DIRECTORY_SEPARATOR.$relativePath;
        $content = file_get_contents($fullPath);
        require $fullPath;

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\DTOs')
            ->toHaveClass('OrderData')
            ->not->toContain('{{');

        expect('App\Pulsar\Domain\Orders\DTOs\OrderData')
            ->toHaveMethod('__construct')
            ->toHaveMethod('from');
    });

    it('rejects a duplicate DTO', function () {
        (new DtoGenerator('OrderData', 'Orders'))->generate();

        expect(fn () => (new DtoGenerator('OrderData', 'Orders'))->generate())
            ->toThrow(Exception::class, 'already exists');
    });
});
