<?php

use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;
use Faran\Pulsar\Generators\RequestGenerator;

describe('Request Generator', function () {
    beforeEach(function () {
        createService($this->tempDir, 'Admin');
    });

    it('generates the current request contract', function () {
        $generator = new RequestGenerator('StoreOrderRequest', 'Orders', 'Admin');
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Services', 'Admin', 'Modules', 'Orders', 'Requests', 'StoreOrderRequest.php',
        ]);

        expect($relativePath)->toBe($expectedPath);

        $fullPath = $this->tempDir.DIRECTORY_SEPARATOR.$relativePath;
        $content = file_get_contents($fullPath);
        require $fullPath;

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Services\Admin\Modules\Orders\Requests')
            ->toHaveClass('StoreOrderRequest')
            ->not->toContain('{{');

        expect('App\Pulsar\Services\Admin\Modules\Orders\Requests\StoreOrderRequest')
            ->toHaveMethod('authorize')
            ->toHaveMethod('rules')
            ->toHaveMethod('messages');
    });

    it('rejects a duplicate request', function () {
        (new RequestGenerator('StoreOrderRequest', 'Orders', 'Admin'))->generate();

        expect(fn () => (new RequestGenerator('StoreOrderRequest', 'Orders', 'Admin'))->generate())
            ->toThrow(Exception::class, 'already exists');
    });

    it('rejects a missing service', function () {
        expect(fn () => (new RequestGenerator('StoreOrderRequest', 'Orders', 'Missing'))->generate())
            ->toThrow(ServiceDoesNotExistException::class);
    });
});
