<?php

use Faran\Pulsar\Generators\ActionGenerator;

beforeEach(function () {
    createDomain($this->tempDir, 'Orders');
});

describe('Action Generator', function () {
    it('generates the current action contract', function () {
        $generator = new ActionGenerator('CreateOrder', 'Orders');
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Orders', 'Actions', 'CreateOrder.php',
        ]);

        expect($relativePath)->toBe($expectedPath);

        $fullPath = $this->tempDir.DIRECTORY_SEPARATOR.$relativePath;
        $content = file_get_contents($fullPath);
        require $fullPath;

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\Actions')
            ->toHaveClass('CreateOrder')
            ->not->toContain('{{');

        expect('App\Pulsar\Domain\Orders\Actions\CreateOrder')->toHaveMethod('execute');
    });

    it('rejects a duplicate action', function () {
        (new ActionGenerator('CreateOrder', 'Orders'))->generate();

        expect(fn () => (new ActionGenerator('CreateOrder', 'Orders'))->generate())
            ->toThrow(Exception::class, 'already exists');
    });

    it('rejects a missing domain', function () {
        expect(fn () => (new ActionGenerator('CreateOrder', 'Missing'))->generate())
            ->toThrow(Exception::class, 'Domain [Missing] does not exist');
    });
});
