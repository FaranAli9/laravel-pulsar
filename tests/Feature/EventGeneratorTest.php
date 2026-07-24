<?php

use Faran\Pulsar\Generators\EventGenerator;

describe('Event Generator', function () {
    it('generates the current event contract', function () {
        $generator = new EventGenerator('OrderPlaced', 'Orders');
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Orders', 'Events', 'OrderPlaced.php',
        ]);

        expect($relativePath)->toBe($expectedPath);

        $fullPath = $this->tempDir.DIRECTORY_SEPARATOR.$relativePath;
        $content = file_get_contents($fullPath);
        require $fullPath;

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\Events')
            ->toHaveClass('OrderPlaced')
            ->not->toContain('{{');

        expect('App\Pulsar\Domain\Orders\Events\OrderPlaced')->toHaveMethod('__construct');
    });

    it('rejects a duplicate event', function () {
        (new EventGenerator('OrderPlaced', 'Orders'))->generate();

        expect(fn () => (new EventGenerator('OrderPlaced', 'Orders'))->generate())
            ->toThrow(Exception::class, 'already exists');
    });
});
