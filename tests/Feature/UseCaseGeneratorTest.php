<?php

use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;
use Faran\Pulsar\Generators\UseCaseGenerator;

describe('UseCase Generator', function () {
    beforeEach(function () {
        createService($this->tempDir, 'Admin');
    });

    it('generates the current use case contract', function () {
        $generator = new UseCaseGenerator('CreateOrder', 'Orders', 'Admin');
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Services', 'Admin', 'Modules', 'Orders', 'UseCases', 'CreateOrder.php',
        ]);

        expect($relativePath)->toBe($expectedPath);

        $fullPath = $this->tempDir.DIRECTORY_SEPARATOR.$relativePath;
        $content = file_get_contents($fullPath);
        require $fullPath;

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Services\Admin\Modules\Orders\UseCases')
            ->toHaveClass('CreateOrder')
            ->not->toContain('{{');

        expect('App\Pulsar\Services\Admin\Modules\Orders\UseCases\CreateOrder')
            ->toHaveMethod('execute');
    });

    it('rejects a duplicate use case', function () {
        (new UseCaseGenerator('CreateOrder', 'Orders', 'Admin'))->generate();

        expect(fn () => (new UseCaseGenerator('CreateOrder', 'Orders', 'Admin'))->generate())
            ->toThrow(Exception::class, 'already exists');
    });

    it('rejects a missing service', function () {
        expect(fn () => (new UseCaseGenerator('CreateOrder', 'Orders', 'Missing'))->generate())
            ->toThrow(ServiceDoesNotExistException::class);
    });
});
