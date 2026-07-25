<?php

use Faran\Pulsar\Generators\ModelGenerator;

beforeEach(function () {
    createDomain($this->tempDir, 'Orders');
});

describe('Model Generator', function () {
    it('generates the current model contract', function () {
        $generator = new ModelGenerator('Order', 'Orders');
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Orders', 'Models', 'Order.php',
        ]);

        expect($relativePath)->toBe($expectedPath);

        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\Models')
            ->toHaveClass('Order')
            ->toContain('extends Model')
            ->not->toContain('{{');
    });

    it('rejects a duplicate model', function () {
        (new ModelGenerator('Order', 'Orders'))->generate();

        expect(fn () => (new ModelGenerator('Order', 'Orders'))->generate())
            ->toThrow(Exception::class, 'already exists');
    });

    it('rejects a missing domain', function () {
        expect(fn () => (new ModelGenerator('Order', 'Missing'))->generate())
            ->toThrow(Exception::class, 'Domain [Missing] does not exist');
    });
});
