<?php

use Faran\Pulsar\Commands\InstallCommand;
use Faran\Pulsar\Exceptions\UnexpectedBootstrapFileException;
use Faran\Pulsar\Generators\InstallGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

function freshApplicationBootstrap(): string
{
    return <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
PHP;
}

function writeBootstrapFixture(string $root, ?string $application = null, ?string $providers = null): void
{
    $bootstrap = $root.DIRECTORY_SEPARATOR.'bootstrap';

    foreach ([
        $bootstrap,
        $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Providers',
        $root.DIRECTORY_SEPARATOR.'routes',
    ] as $directory) {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    file_put_contents(
        $bootstrap.DIRECTORY_SEPARATOR.'app.php',
        $application ?? freshApplicationBootstrap(),
    );
    file_put_contents(
        $bootstrap.DIRECTORY_SEPARATOR.'providers.php',
        $providers ?? "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n",
    );
}

beforeEach(function () {
    writeBootstrapFixture($this->tempDir);
});

describe('Install Generator', function () {
    it('prints a complete dry-run diff without writing any file', function () {
        $applicationPath = $this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php';
        $providersPath = $this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'providers.php';
        $providerPath = $this->tempDir.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Providers'
            .DIRECTORY_SEPARATOR.'PulsarServiceProvider.php';
        $applicationBefore = file_get_contents($applicationPath);
        $providersBefore = file_get_contents($providersPath);

        $result = (new InstallGenerator(dryRun: true))->generate();

        expect($result->dryRun)->toBeTrue()
            ->and($result->changedPaths)->toHaveCount(3)
            ->and($result->diff)
            ->toContain('--- /dev/null')
            ->toContain('+++ b/app/Providers/PulsarServiceProvider.php')
            ->toContain('->withEvents(discover: [')
            ->toContain("app_path('Pulsar/Domain/*/Listeners')")
            ->toContain("app_path('Pulsar/Services/*/Modules/*/Commands')")
            ->and(file_exists($providerPath))->toBeFalse()
            ->and(file_get_contents($applicationPath))->toBe($applicationBefore)
            ->and(file_get_contents($providersPath))->toBe($providersBefore)
            ->and(glob($applicationPath.'.pulsar.bak*'))->toBe([]);
    });

    it('installs the provider and wiring with a backup, then becomes a no-op', function () {
        $applicationPath = $this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php';
        $providersPath = $this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'providers.php';
        $providerPath = $this->tempDir.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Providers'
            .DIRECTORY_SEPARATOR.'PulsarServiceProvider.php';
        $applicationBefore = file_get_contents($applicationPath);

        $result = (new InstallGenerator)->generate();
        $application = file_get_contents($applicationPath);
        $providers = file_get_contents($providersPath);
        $provider = file_get_contents($providerPath);

        expect($result->changed())->toBeTrue()
            ->and($result->backupPath)->toBe('bootstrap'.DIRECTORY_SEPARATOR.'app.php.pulsar.bak')
            ->and(file_get_contents($applicationPath.'.pulsar.bak'))->toBe($applicationBefore)
            ->and($application)
            ->toContain('->withEvents(discover: [')
            ->toContain("app_path('Pulsar/Domain/*/Listeners')")
            ->toContain('->withCommands([')
            ->toContain("app_path('Pulsar/Services/*/Modules/*/Commands')")
            ->toContain('GLOB_ONLYDIR')
            ->and(substr_count($application, 'Pulsar/Domain/*/Listeners'))->toBe(1)
            ->and(substr_count($application, 'Pulsar/Services/*/Modules/*/Commands'))->toBe(1)
            ->and($providers)
            ->toContain('App\Providers\PulsarServiceProvider::class,')
            ->and(substr_count($providers, 'PulsarServiceProvider::class'))->toBe(1)
            ->and($provider)
            ->toBeValidPhp()
            ->toContain('$this->app->bind(')
            ->toContain('$this->app->scoped(')
            ->toContain('$this->app->when(')
            ->toContain('Gate::guessPolicyNamesUsing(')
            ->toContain('Gate::define(')
            ->toContain('Gate::before(')
            ->toContain('Gate::after(')
            ->toContain('$model::observe(');

        $second = (new InstallGenerator)->generate();

        expect($second->changed())->toBeFalse()
            ->and($second->changedPaths)->toBe([])
            ->and($second->backupPath)->toBeNull()
            ->and(glob($applicationPath.'.pulsar.bak*'))->toHaveCount(1);
    });

    it('merges existing event and command arrays without discarding custom paths', function () {
        $application = <<<'PHP'
<?php

use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withEvents(discover: [
        app_path('Domain/Listeners'),
    ])
    ->withCommands([app_path('Console/Commands')])
    ->create();
PHP;

        writeBootstrapFixture($this->tempDir, $application);

        (new InstallGenerator)->generate();

        $patched = file_get_contents(
            $this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php',
        );

        expect($patched)
            ->toContain("app_path('Domain/Listeners'),")
            ->toContain("app_path('Console/Commands'),")
            ->toContain("app_path('Pulsar/Domain/*/Listeners'),")
            ->toContain("app_path('Pulsar/Services/*/Modules/*/Commands'),")
            ->and(substr_count($patched, 'Pulsar/Domain/*/Listeners'))->toBe(1)
            ->and(substr_count($patched, 'Pulsar/Services/*/Modules/*/Commands'))->toBe(1)
            ->and($patched)->toBeValidPhp();
    });

    it('recognizes existing functional wiring with either quote style', function () {
        $application = <<<'PHP'
<?php

use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withEvents(discover: [app_path("Pulsar/Domain/*/Listeners")])
    ->withCommands([...(glob(app_path("Pulsar/Services/*/Modules/*/Commands"), GLOB_ONLYDIR) ?: [])])
    ->create();
PHP;

        writeBootstrapFixture($this->tempDir, $application);
        $result = (new InstallGenerator)->generate();

        expect($result->changedPaths)
            ->toBe([
                'app'.DIRECTORY_SEPARATOR.'Providers'.DIRECTORY_SEPARATOR.'PulsarServiceProvider.php',
                'bootstrap'.DIRECTORY_SEPARATOR.'providers.php',
            ])
            ->and(file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php'))
            ->toBe($application);
    });

    it('force restores only the generated provider and never duplicates wiring', function () {
        (new InstallGenerator)->generate();

        $applicationPath = $this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php';
        $providersPath = $this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'providers.php';
        $providerPath = $this->tempDir.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Providers'
            .DIRECTORY_SEPARATOR.'PulsarServiceProvider.php';
        file_put_contents($providerPath, "<?php\n// locally changed\n");

        $result = (new InstallGenerator(force: true))->generate();
        $application = file_get_contents($applicationPath);
        $providers = file_get_contents($providersPath);

        expect($result->changedPaths)->toBe([
            'app'.DIRECTORY_SEPARATOR.'Providers'.DIRECTORY_SEPARATOR.'PulsarServiceProvider.php',
        ])
            ->and($result->backupPath)->toBeNull()
            ->and(file_get_contents($providerPath))->toContain('class PulsarServiceProvider extends ServiceProvider')
            ->and(substr_count($application, 'Pulsar/Domain/*/Listeners'))->toBe(1)
            ->and(substr_count($application, 'Pulsar/Services/*/Modules/*/Commands'))->toBe(1)
            ->and(substr_count($providers, 'PulsarServiceProvider::class'))->toBe(1);
    });

    it('preserves an existing backup by selecting a new suffix', function () {
        $applicationPath = $this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php';
        file_put_contents($applicationPath.'.pulsar.bak', 'original backup');

        $result = (new InstallGenerator)->generate();

        expect($result->backupPath)->toBe('bootstrap'.DIRECTORY_SEPARATOR.'app.php.pulsar.bak.1')
            ->and(file_get_contents($applicationPath.'.pulsar.bak'))->toBe('original backup')
            ->and(file_get_contents($applicationPath.'.pulsar.bak.1'))->toBe(freshApplicationBootstrap());
    });

    it('fails safely with manual instructions for an unexpected application shape', function () {
        $unexpected = "<?php\n\nuse Illuminate\\Foundation\\Application;\n\nreturn new Application;\n";
        writeBootstrapFixture($this->tempDir, $unexpected);
        $before = [
            file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php'),
            file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'providers.php'),
        ];

        expect(fn () => (new InstallGenerator)->generate())
            ->toThrow(UnexpectedBootstrapFileException::class, 'No files were changed');

        expect(file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php'))
            ->toBe($before[0])
            ->and(file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'providers.php'))
            ->toBe($before[1])
            ->and(file_exists(
                $this->tempDir.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Providers'
                .DIRECTORY_SEPARATOR.'PulsarServiceProvider.php',
            ))->toBeFalse()
            ->and(glob(
                $this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php.pulsar.bak*',
            ))->toBe([]);
    });

    it('fails safely when an existing wiring method cannot be merged', function () {
        $application = <<<'PHP'
<?php

use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withEvents(discover: true)
    ->create();
PHP;
        writeBootstrapFixture($this->tempDir, $application);

        expect(fn () => (new InstallGenerator)->generate())
            ->toThrow(UnexpectedBootstrapFileException::class, 'could not safely merge');

        expect(file_get_contents(
            $this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php',
        ))->toBe($application);
    });

    it('upgrades a literal command glob to directories Laravel can discover', function () {
        $application = <<<'PHP'
<?php

use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([app_path('Pulsar/Services/*/Modules/*/Commands')])
    ->create();
PHP;
        writeBootstrapFixture($this->tempDir, $application);

        (new InstallGenerator)->generate();

        $patched = file_get_contents(
            $this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php',
        );

        expect($patched)
            ->toContain("app_path('Pulsar/Services/*/Modules/*/Commands')")
            ->toContain("...(glob(app_path('Pulsar/Services/*/Modules/*/Commands'), GLOB_ONLYDIR) ?: [])")
            ->and(substr_count($patched, 'GLOB_ONLYDIR'))->toBe(1);
    });

    it('turns malformed delimiters into the same safe manual fallback', function () {
        $application = "<?php\n\nuse Illuminate\\Foundation\\Application;\n\nreturn Application::configure(\n";
        writeBootstrapFixture($this->tempDir, $application);

        expect(fn () => (new InstallGenerator)->generate())
            ->toThrow(UnexpectedBootstrapFileException::class, 'Unbalanced () delimiters');

        expect(file_get_contents(
            $this->tempDir.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php',
        ))->toBe($application);
    });
});

describe('Install Command', function () {
    it('installs a fresh application and reports an idempotent rerun', function () {
        $tester = new CommandTester(new InstallCommand);

        $exitCode = $tester->execute([]);

        expect($exitCode)->toBe(Command::SUCCESS)
            ->and($tester->getDisplay())
            ->toContain('Pulsar installed successfully')
            ->toContain('Updated: app'.DIRECTORY_SEPARATOR.'Providers'.DIRECTORY_SEPARATOR.'PulsarServiceProvider.php')
            ->toContain('Updated: bootstrap'.DIRECTORY_SEPARATOR.'providers.php')
            ->toContain('Updated: bootstrap'.DIRECTORY_SEPARATOR.'app.php')
            ->toContain('Backup: bootstrap'.DIRECTORY_SEPARATOR.'app.php.pulsar.bak');

        $rerun = new CommandTester(new InstallCommand);

        expect($rerun->execute([]))->toBe(Command::SUCCESS)
            ->and($rerun->getDisplay())->toContain('Pulsar is already installed; no changes were needed.');
    });

    it('shows a dry-run diff and leaves the fixture untouched', function () {
        $tester = new CommandTester(new InstallCommand);

        $exitCode = $tester->execute(['--dry-run' => true]);

        expect($exitCode)->toBe(Command::SUCCESS)
            ->and($tester->getDisplay())
            ->toContain('Dry run only; no files were changed.')
            ->toContain('+++ b/bootstrap/app.php')
            ->toContain("app_path('Pulsar/Domain/*/Listeners')")
            ->and(file_exists(
                $this->tempDir.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Providers'
                .DIRECTORY_SEPARATOR.'PulsarServiceProvider.php',
            ))->toBeFalse();
    });

    it('prints manual instructions and returns failure for an unexpected shape', function () {
        writeBootstrapFixture($this->tempDir, "<?php\n\nreturn ['custom-bootstrap'];\n");
        $tester = new CommandTester(new InstallCommand);

        $exitCode = $tester->execute([]);

        expect($exitCode)->toBe(Command::FAILURE)
            ->and($tester->getDisplay())
            ->toContain('Cannot safely patch [bootstrap/app.php]')
            ->toContain('No files were changed.')
            ->toContain("->withEvents(discover: [ app_path('Pulsar/Domain/*/Listeners') ])")
            ->toContain("glob(app_path('Pulsar/Services/*/Modules/*/Commands'), GLOB_ONLYDIR)");
    });
});
