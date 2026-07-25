<?php

use Faran\Pulsar\Generators\EventGenerator;

beforeEach(function () {
    createDomain($this->tempDir, 'Orders');
});

describe('Event Generator', function () {
    it('generates the current event contract', function () {
        $generator = new EventGenerator('OrderPlaced', 'Orders');
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Orders', 'Events', 'OrderPlaced.php',
        ]);

        expect($relativePath)->toBe($expectedPath);

        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\Events')
            ->toHaveClass('OrderPlaced')
            ->toContain('implements ShouldDispatchAfterCommit')
            ->toContain('public const int VERSION = 1;')
            ->toContain('public function __construct(')
            ->not->toContain('{{');
    });

    it('rejects a duplicate event', function () {
        (new EventGenerator('OrderPlaced', 'Orders'))->generate();

        expect(fn () => (new EventGenerator('OrderPlaced', 'Orders'))->generate())
            ->toThrow(Exception::class, 'already exists');
    });

    it('rejects a missing domain', function () {
        expect(fn () => (new EventGenerator('OrderPlaced', 'Missing'))->generate())
            ->toThrow(Exception::class, 'Domain [Missing] does not exist');
    });
});
