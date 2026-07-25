<?php

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\InvalidNameException;
use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;
use Faran\Pulsar\Generators\ResourceGenerator;

beforeEach(function () {
    createService($this->tempDir, 'Admin');
});

describe('Resource Generator', function () {
    it('generates a service-layer JSON resource and reflects its method', function () {
        $relativePath = (new ResourceGenerator('OrderResourceGenerated', 'Orders', 'Admin'))->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Services', 'Admin', 'Modules', 'Orders', 'Resources',
            'OrderResourceGenerated.php',
        ]);
        $fullPath = $this->tempDir.DIRECTORY_SEPARATOR.$relativePath;
        $content = file_get_contents($fullPath);

        require $fullPath;

        expect($relativePath)->toBe($expectedPath)
            ->and($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Services\Admin\Modules\Orders\Resources')
            ->toHaveClass('OrderResourceGenerated')
            ->toContain('extends JsonResource')
            ->toContain('response')
            ->toContain('Never shape HTTP responses in Domain')
            ->not->toContain('{{')
            ->and('App\Pulsar\Services\Admin\Modules\Orders\Resources\OrderResourceGenerated')
            ->toHaveMethod('toArray');
    });

    it('generates the collection resource variant', function () {
        $relativePath = (new ResourceGenerator('OrderCollection', 'Orders', 'Admin', true))->generate();
        $fullPath = $this->tempDir.DIRECTORY_SEPARATOR.$relativePath;
        $content = file_get_contents($fullPath);

        require $fullPath;

        expect($content)
            ->toBeValidPhp()
            ->toContain('extends ResourceCollection')
            ->toContain("'data' => \$this->collection")
            ->not->toContain('{{')
            ->and('App\Pulsar\Services\Admin\Modules\Orders\Resources\OrderCollection')
            ->toHaveMethod('toArray');
    });

    it('rejects duplicates and a missing service', function () {
        (new ResourceGenerator('OrderResource', 'Orders', 'Admin'))->generate();

        expect(fn () => (new ResourceGenerator('OrderResource', 'Orders', 'Admin'))->generate())
            ->toThrow(FileAlreadyExistsException::class, 'already exists')
            ->and(fn () => (new ResourceGenerator('OrderResource', 'Orders', 'Missing'))->generate())
            ->toThrow(ServiceDoesNotExistException::class, 'Service [Missing] does not exist');
    });

    it('rejects invalid names and every traversing segment', function (
        string $name,
        string $module,
        string $service,
    ) {
        expect(fn () => (new ResourceGenerator($name, $module, $service))->generate())
            ->toThrow(InvalidNameException::class);
    })->with([
        'reserved name' => ['class', 'Orders', 'Admin'],
        'invalid name' => ['Bad-Resource', 'Orders', 'Admin'],
        'traversing name' => ['../Resource', 'Orders', 'Admin'],
        'traversing module' => ['OrderResource', '../Orders', 'Admin'],
        'invalid module' => ['OrderResource', 'Bad|Module', 'Admin'],
        'traversing service' => ['OrderResource', 'Orders', '../Admin'],
        'invalid service' => ['OrderResource', 'Orders', 'Bad|Service'],
    ]);
});
