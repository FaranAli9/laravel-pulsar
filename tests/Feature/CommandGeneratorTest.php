<?php

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\InvalidNameException;
use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;
use Faran\Pulsar\Generators\CommandGenerator;

beforeEach(function () {
    createService($this->tempDir, 'Internal');
});

describe('Command Generator', function () {
    it('generates an authorized console adapter with a custom signature', function () {
        $relativePath = (new CommandGenerator(
            'ReconcileLedgerCommandGenerated',
            'Billing',
            'Internal',
            'billing:reconcile {--dry-run}',
        ))->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Services', 'Internal', 'Modules', 'Billing', 'Commands',
            'ReconcileLedgerCommandGenerated.php',
        ]);
        $fullPath = $this->tempDir.DIRECTORY_SEPARATOR.$relativePath;
        $content = file_get_contents($fullPath);

        require $fullPath;

        expect($relativePath)->toBe($expectedPath)
            ->and($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Services\Internal\Modules\Billing\Commands')
            ->toHaveClass('ReconcileLedgerCommandGenerated')
            ->toContain("protected \$signature = 'billing:reconcile {--dry-run}';")
            ->toContain('Gate::authorize(')
            ->toContain('$useCase->execute();')
            ->toContain('return self::SUCCESS;')
            ->not->toContain('{{')
            ->and('App\Pulsar\Services\Internal\Modules\Billing\Commands\ReconcileLedgerCommandGenerated')
            ->toHaveMethod('handle');
    });

    it('derives a stable default signature', function () {
        $relativePath = (new CommandGenerator('RebuildIndexCommand', 'SearchIndex', 'Internal'))->generate();
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($content)->toContain("protected \$signature = 'search-index:rebuild-index';");
    });

    it('rejects duplicates and a missing service', function () {
        (new CommandGenerator('ReconcileLedger', 'Billing', 'Internal'))->generate();

        expect(fn () => (new CommandGenerator('ReconcileLedger', 'Billing', 'Internal'))->generate())
            ->toThrow(FileAlreadyExistsException::class, 'already exists')
            ->and(fn () => (new CommandGenerator('ReconcileLedger', 'Billing', 'Missing'))->generate())
            ->toThrow(ServiceDoesNotExistException::class, 'Service [Missing] does not exist');
    });

    it('rejects invalid names and every traversing segment', function (
        string $name,
        string $module,
        string $service,
    ) {
        expect(fn () => (new CommandGenerator($name, $module, $service))->generate())
            ->toThrow(InvalidNameException::class);
    })->with([
        'reserved name' => ['class', 'Billing', 'Internal'],
        'invalid name' => ['Bad-Command', 'Billing', 'Internal'],
        'traversing name' => ['../Command', 'Billing', 'Internal'],
        'traversing module' => ['ReconcileLedger', '../Billing', 'Internal'],
        'invalid module' => ['ReconcileLedger', 'Bad|Module', 'Internal'],
        'traversing service' => ['ReconcileLedger', 'Billing', '../Internal'],
        'invalid service' => ['ReconcileLedger', 'Billing', 'Bad|Service'],
    ]);
});
