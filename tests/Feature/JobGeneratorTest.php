<?php

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\InvalidNameException;
use Faran\Pulsar\Exceptions\ServiceDoesNotExistException;
use Faran\Pulsar\Generators\JobGenerator;

beforeEach(function () {
    createService($this->tempDir, 'Internal');
});

describe('Job Generator', function () {
    it('generates a thin queued workflow entrypoint without model serialization', function () {
        $relativePath = (new JobGenerator('ProcessOrderJob', 'Orders', 'Internal'))->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Services', 'Internal', 'Modules', 'Orders', 'Jobs', 'ProcessOrderJob.php',
        ]);
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($relativePath)->toBe($expectedPath)
            ->and($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Services\Internal\Modules\Orders\Jobs')
            ->toHaveClass('ProcessOrderJob')
            ->toContain('implements ShouldQueue')
            ->toContain('use Illuminate\Foundation\Queue\Queueable;')
            ->toContain('use Queueable;')
            ->toContain('public int $tries = 3;')
            ->toContain('public function backoff(): array')
            ->toContain('public function handle(ProcessOrderUseCase $useCase): void')
            ->toContain('Gate::authorize(')
            ->toContain('$useCase->execute(')
            ->toContain('idempotent')
            ->toContain('Never Eloquent models')
            ->toContain('Do not add SerializesModels')
            ->toContain('public function failed(Throwable $exception): void')
            ->not->toContain('{{');
    });

    it('rejects duplicates and a missing service', function () {
        (new JobGenerator('ProcessOrderJob', 'Orders', 'Internal'))->generate();

        expect(fn () => (new JobGenerator('ProcessOrderJob', 'Orders', 'Internal'))->generate())
            ->toThrow(FileAlreadyExistsException::class, 'already exists')
            ->and(fn () => (new JobGenerator('ProcessOrderJob', 'Orders', 'Missing'))->generate())
            ->toThrow(ServiceDoesNotExistException::class, 'Service [Missing] does not exist');
    });

    it('rejects invalid names and every traversing segment before writing', function (
        string $name,
        string $module,
        string $service,
    ) {
        expect(fn () => (new JobGenerator($name, $module, $service))->generate())
            ->toThrow(InvalidNameException::class);
    })->with([
        'reserved name' => ['class', 'Orders', 'Internal'],
        'invalid name' => ['Bad-Job', 'Orders', 'Internal'],
        'traversing name' => ['../Job', 'Orders', 'Internal'],
        'traversing module' => ['ProcessOrderJob', '../Orders', 'Internal'],
        'invalid module' => ['ProcessOrderJob', 'Bad|Module', 'Internal'],
        'traversing service' => ['ProcessOrderJob', 'Orders', '../Internal'],
        'invalid service' => ['ProcessOrderJob', 'Orders', 'Bad|Service'],
    ]);
});
