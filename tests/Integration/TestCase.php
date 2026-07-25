<?php

namespace Tests\Integration;

use App\Pulsar\Support\ProbeState;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Use the real Laravel fixture bootstrapped through bootstrap/app.php.
     */
    public static function applicationBasePath()
    {
        return __DIR__.DIRECTORY_SEPARATOR.'fixture';
    }

    /**
     * Configure the fixture's database and queue services.
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('queue.default', 'sync');
    }

    protected function setUp(): void
    {
        parent::setUp();

        ProbeState::reset();
    }
}
