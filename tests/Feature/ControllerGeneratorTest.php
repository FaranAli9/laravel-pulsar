<?php

use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;
use Faran\Pulsar\Generators\ControllerGenerator;

describe('Controller Generator', function () {
    beforeEach(function () {
        createService($this->tempDir, 'Admin');
    });

    it('generates the current resource controller contract', function () {
        $generator = new ControllerGenerator('OrderController', 'Admin', 'Orders', true);
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Services', 'Admin', 'Modules', 'Orders', 'Controllers', 'OrderController.php',
        ]);

        expect($relativePath)->toBe($expectedPath);

        $fullPath = $this->tempDir.DIRECTORY_SEPARATOR.$relativePath;
        $content = file_get_contents($fullPath);
        require $fullPath;

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Services\Admin\Modules\Orders\Controllers')
            ->toHaveClass('OrderController')
            ->not->toContain('{{');

        expect('App\Pulsar\Services\Admin\Modules\Orders\Controllers\OrderController')
            ->toHaveMethod('index')
            ->toHaveMethod('store')
            ->toHaveMethod('show')
            ->toHaveMethod('update')
            ->toHaveMethod('destroy');
    });

    it('generates the current plain controller contract', function () {
        $relativePath = (new ControllerGenerator('HealthController', 'Admin', 'System'))->generate();
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Services\Admin\Modules\System\Controllers')
            ->toHaveClass('HealthController')
            ->not->toContain('{{');
    });

    it('rejects a duplicate controller', function () {
        (new ControllerGenerator('OrderController', 'Admin', 'Orders'))->generate();

        expect(fn () => (new ControllerGenerator('OrderController', 'Admin', 'Orders'))->generate())
            ->toThrow(Exception::class, 'already exists');
    });

    it('rejects a missing service', function () {
        expect(fn () => (new ControllerGenerator('OrderController', 'Missing', 'Orders'))->generate())
            ->toThrow(ServiceDoesNotExistException::class);
    });
});
