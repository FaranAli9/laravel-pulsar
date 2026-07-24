<?php

use Faran\Pulsar\Exceptions\ServiceAlreadyExistsException;
use Faran\Pulsar\Generators\ServiceGenerator;

describe('Service Generator', function () {
    it('generates the current service structure and inline provider content', function () {
        $result = (new ServiceGenerator('Admin'))->generate();
        $servicePath = $this->tempDir.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Services', 'Admin',
        ]);
        $providerPath = $servicePath.DIRECTORY_SEPARATOR.'Providers'.DIRECTORY_SEPARATOR.'AdminServiceProvider.php';
        $routeProviderPath = $servicePath.DIRECTORY_SEPARATOR.'Providers'.DIRECTORY_SEPARATOR.'RouteServiceProvider.php';
        $routesPath = $servicePath.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'api.php';

        expect($result)->toBeNull()
            ->and(is_dir($servicePath.DIRECTORY_SEPARATOR.'Providers'))->toBeTrue()
            ->and(is_dir($servicePath.DIRECTORY_SEPARATOR.'Routes'))->toBeTrue()
            ->and(is_dir($servicePath.DIRECTORY_SEPARATOR.'Modules'))->toBeTrue()
            ->and(file_exists($servicePath.DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.'.gitkeep'))->toBeTrue()
            ->and(file_exists($providerPath))->toBeTrue()
            ->and(file_exists($routeProviderPath))->toBeTrue()
            ->and(file_exists($routesPath))->toBeTrue();

        $providerContent = file_get_contents($providerPath);
        $routeProviderContent = file_get_contents($routeProviderPath);
        $routesContent = file_get_contents($routesPath);
        require $providerPath;
        require $routeProviderPath;

        expect($providerContent)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Services\Admin\Providers')
            ->toHaveClass('AdminServiceProvider')
            ->toContain('$this->app->register(RouteServiceProvider::class);')
            ->not->toContain('{{');

        expect('App\Pulsar\Services\Admin\Providers\AdminServiceProvider')
            ->toHaveMethod('register')
            ->toHaveMethod('boot');

        expect($routeProviderContent)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Services\Admin\Providers')
            ->toHaveClass('RouteServiceProvider')
            ->toContain("Route::prefix('api/admin')")
            ->not->toContain('{{');

        expect('App\Pulsar\Services\Admin\Providers\RouteServiceProvider')
            ->toHaveMethod('register');

        expect($routesContent)
            ->toBeValidPhp()
            ->toContain('Admin Service Routes')
            ->toContain('Prefix: /api/admin')
            ->not->toContain('{{');
    });

    it('rejects a duplicate service', function () {
        (new ServiceGenerator('Admin'))->generate();

        expect(fn () => (new ServiceGenerator('Admin'))->generate())
            ->toThrow(ServiceAlreadyExistsException::class);
    });
});
