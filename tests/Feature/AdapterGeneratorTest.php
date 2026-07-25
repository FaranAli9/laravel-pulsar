<?php

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Generators\AdapterGenerator;

beforeEach(function () {
    createDomain($this->tempDir, 'Billing');
});

describe('Adapter Generator', function () {
    it('generates an adapter that implements a named domain contract', function () {
        $generator = new AdapterGenerator(
            'StripePaymentGateway',
            'Payments',
            'PaymentGateway',
            'Billing',
        );
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Infrastructure', 'Payments', 'StripePaymentGateway.php',
        ]);
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($relativePath)->toBe($expectedPath)
            ->and($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Infrastructure\Payments')
            ->toHaveClass('StripePaymentGateway')
            ->toContain('use App\Pulsar\Domain\Billing\Contracts\PaymentGateway;')
            ->toContain('implements PaymentGateway')
            ->toContain('Make retryable side effects idempotent.')
            ->toContain('$this->app->bind(PaymentGateway::class, StripePaymentGateway::class);')
            ->not->toContain('{{')
            ->and($generator->bindingLine())
            ->toBe('$this->app->bind(PaymentGateway::class, StripePaymentGateway::class);');
    });

    it('accepts a fully qualified contract name', function () {
        $generator = new AdapterGenerator(
            'StripePaymentGateway',
            'Payments',
            '\App\Pulsar\Domain\Billing\Contracts\PaymentGateway',
            'Billing',
        );
        $relativePath = $generator->generate();
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($content)
            ->toContain('use App\Pulsar\Domain\Billing\Contracts\PaymentGateway;')
            ->toContain('implements PaymentGateway');
    });

    it('generates a plain adapter without contract options', function () {
        $generator = new AdapterGenerator('SystemClock', 'Time');
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Infrastructure', 'Time', 'SystemClock.php',
        ]);
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($relativePath)->toBe($expectedPath)
            ->and($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Infrastructure\Time')
            ->toHaveClass('SystemClock')
            ->not->toContain('implements')
            ->not->toContain('use App\Pulsar\Domain')
            ->not->toContain('{{')
            ->and($generator->bindingLine())->toBeNull();
    });

    it('rejects a duplicate adapter', function () {
        (new AdapterGenerator('SystemClock', 'Time'))->generate();

        expect(fn () => (new AdapterGenerator('SystemClock', 'Time'))->generate())
            ->toThrow(FileAlreadyExistsException::class, 'already exists');
    });

    it('requires contract and domain options together', function (?string $contract, ?string $domain) {
        expect(fn () => (new AdapterGenerator('StripePaymentGateway', 'Payments', $contract, $domain))->generate())
            ->toThrow(InvalidArgumentException::class, 'must be provided together');
    })->with([
        'contract only' => ['PaymentGateway', null],
        'domain only' => [null, 'Billing'],
    ]);

    it('rejects a missing contract-owning domain', function () {
        expect(fn () => (new AdapterGenerator(
            'StripePaymentGateway',
            'Payments',
            'PaymentGateway',
            'Missing',
        ))->generate())->toThrow(Exception::class, 'Domain [Missing] does not exist');
    });

    it('rejects invalid adapter names', function (string $name) {
        expect(fn () => (new AdapterGenerator($name, 'Payments'))->generate())
            ->toThrow(Exception::class, $name);
    })->with([
        'reserved keyword' => 'class',
        'invalid characters' => 'Stripe-Gateway',
        'path traversal' => '../StripeGateway',
    ]);

    it('rejects invalid and traversing area segments', function (string $area) {
        expect(fn () => (new AdapterGenerator('StripePaymentGateway', $area))->generate())
            ->toThrow(Exception::class, $area);
    })->with([
        'forbidden character' => 'Bad|Area',
        'path traversal' => '../Payments',
    ]);

    it('rejects invalid and traversing contract domain segments', function (string $domain) {
        expect(fn () => (new AdapterGenerator(
            'StripePaymentGateway',
            'Payments',
            'PaymentGateway',
            $domain,
        ))->generate())->toThrow(Exception::class, $domain);
    })->with([
        'forbidden character' => 'Bad|Domain',
        'path traversal' => '../Billing',
    ]);
});
