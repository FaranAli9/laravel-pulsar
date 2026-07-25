<?php

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\InvalidNameException;
use Faran\Pulsar\Generators\ListenerGenerator;

beforeEach(function () {
    createDomain($this->tempDir, 'Orders');
});

describe('Listener Generator', function () {
    it('generates a synchronous side-effect listener that cannot call a UseCase', function () {
        $relativePath = (new ListenerGenerator('AuditOrderPlaced', 'Orders', 'OrderPlaced'))->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Orders', 'Listeners', 'AuditOrderPlaced.php',
        ]);
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($relativePath)->toBe($expectedPath)
            ->and($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\Listeners')
            ->toHaveClass('AuditOrderPlaced')
            ->toContain('use App\Pulsar\Domain\Orders\Events\OrderPlaced;')
            ->toContain('public function handle(OrderPlaced $event): void')
            ->toContain('Gate::authorize(')
            ->toContain('Never call a UseCase')
            ->not->toContain('ShouldQueue')
            ->not->toContain('{{');
    });

    it('generates a queued after-commit listener via content assertions only', function () {
        $relativePath = (new ListenerGenerator(
            'SendOrderReceipt',
            'Orders',
            '\App\Pulsar\Domain\Orders\Events\OrderPaid',
            true,
        ))->generate();
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\Listeners')
            ->toHaveClass('SendOrderReceipt')
            ->toContain('use App\Pulsar\Domain\Orders\Events\OrderPaid;')
            ->toContain('implements ShouldQueue, ShouldQueueAfterCommit')
            ->toContain('use Illuminate\Foundation\Queue\Queueable;')
            ->toContain('use Queueable;')
            ->toContain('public function handle(OrderPaid $event): void')
            ->toContain('Gate::authorize(')
            ->toContain('idempotent')
            ->toContain('Never Eloquent models')
            ->toContain('reentrant listener loop')
            ->not->toContain('{{');
    });

    it('uses a mixed placeholder when no event is selected', function () {
        $relativePath = (new ListenerGenerator('AuditDomainReaction', 'Orders'))->generate();
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($content)
            ->toBeValidPhp()
            ->toContain('public function handle(mixed $event): void');
    });

    it('rejects duplicates and a missing domain', function () {
        (new ListenerGenerator('AuditOrderPlaced', 'Orders'))->generate();

        expect(fn () => (new ListenerGenerator('AuditOrderPlaced', 'Orders'))->generate())
            ->toThrow(FileAlreadyExistsException::class, 'already exists')
            ->and(fn () => (new ListenerGenerator('AuditOrderPlaced', 'Missing'))->generate())
            ->toThrow(Exception::class, 'Domain [Missing] does not exist');
    });

    it('rejects invalid names, event names, and traversing domains', function (
        string $name,
        string $domain,
        ?string $event,
    ) {
        expect(fn () => (new ListenerGenerator($name, $domain, $event))->generate())
            ->toThrow(InvalidNameException::class);
    })->with([
        'reserved name' => ['class', 'Orders', null],
        'invalid name' => ['Bad-Listener', 'Orders', null],
        'traversing name' => ['../Listener', 'Orders', null],
        'traversing domain' => ['AuditOrderPlaced', '../Orders', null],
        'invalid domain' => ['AuditOrderPlaced', 'Bad|Domain', null],
        'invalid event' => ['AuditOrderPlaced', 'Orders', '../OrderPlaced'],
    ]);
});
