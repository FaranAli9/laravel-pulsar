<?php

use Faran\Pulsar\Generators\QueryGenerator;

describe('Query Generator', function () {
    it('generates the current query contract', function () {
        $generator = new QueryGenerator('FindOrder', 'Orders');
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Orders', 'Queries', 'FindOrder.php',
        ]);

        expect($relativePath)->toBe($expectedPath);

        $fullPath = $this->tempDir.DIRECTORY_SEPARATOR.$relativePath;
        $content = file_get_contents($fullPath);
        require $fullPath;

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\Queries')
            ->toHaveClass('FindOrder')
            ->not->toContain('{{');

        expect('App\Pulsar\Domain\Orders\Queries\FindOrder')->toHaveMethod('execute');
    });

    it('rejects a duplicate query', function () {
        (new QueryGenerator('FindOrder', 'Orders'))->generate();

        expect(fn () => (new QueryGenerator('FindOrder', 'Orders'))->generate())
            ->toThrow(Exception::class, 'already exists');
    });
});
