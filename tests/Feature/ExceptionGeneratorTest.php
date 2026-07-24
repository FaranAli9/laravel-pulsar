<?php

use Faran\Pulsar\Generators\ExceptionGenerator;

describe('Exception Generator', function () {
    it('generates the current exception contract', function () {
        $generator = new ExceptionGenerator('OrderNotFound', 'Orders');
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Orders', 'Exceptions', 'OrderNotFound.php',
        ]);

        expect($relativePath)->toBe($expectedPath);

        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\Exceptions')
            ->toHaveClass('OrderNotFound')
            ->toContain('extends Exception')
            ->not->toContain('{{');
    });

    it('rejects a duplicate exception', function () {
        (new ExceptionGenerator('OrderNotFound', 'Orders'))->generate();

        expect(fn () => (new ExceptionGenerator('OrderNotFound', 'Orders'))->generate())
            ->toThrow(Exception::class, 'already exists');
    });
});
