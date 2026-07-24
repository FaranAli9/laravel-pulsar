<?php

use Faran\Pulsar\Generators\PolicyGenerator;

describe('Policy Generator', function () {
    it('generates the current policy contract', function () {
        $generator = new PolicyGenerator('OrderPolicy', 'Orders');
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Orders', 'Policies', 'OrderPolicy.php',
        ]);

        expect($relativePath)->toBe($expectedPath);

        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\Policies')
            ->toHaveClass('OrderPolicy')
            ->not->toContain('{{');
    });

    it('rejects a duplicate policy', function () {
        (new PolicyGenerator('OrderPolicy', 'Orders'))->generate();

        expect(fn () => (new PolicyGenerator('OrderPolicy', 'Orders'))->generate())
            ->toThrow(Exception::class, 'already exists');
    });
});
