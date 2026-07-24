<?php

use Faran\Pulsar\Generators\ModelGenerator;

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
});
