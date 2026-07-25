<?php

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Generators\ContractGenerator;

beforeEach(function () {
    createDomain($this->tempDir, 'Billing');
});

describe('Contract Generator', function () {
    it('generates a domain-owned capability interface', function () {
        $relativePath = (new ContractGenerator('PaymentGateway', 'Billing'))->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Billing', 'Contracts', 'PaymentGateway.php',
        ]);
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($relativePath)->toBe($expectedPath)
            ->and($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Billing\Contracts')
            ->toContain('interface PaymentGateway')
            ->not->toContain('{{');
    });

    it('normalizes redundant Contract and Interface suffixes', function (string $input) {
        $relativePath = (new ContractGenerator($input, 'Billing'))->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Billing', 'Contracts', 'PaymentGateway.php',
        ]);
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($relativePath)->toBe($expectedPath)
            ->and($content)->toContain('interface PaymentGateway')
            ->not->toContain("interface {$input}");
    })->with([
        'Contract suffix' => 'PaymentGatewayContract',
        'Interface suffix' => 'PaymentGatewayInterface',
    ]);

    it('rejects a duplicate contract', function () {
        (new ContractGenerator('PaymentGateway', 'Billing'))->generate();

        expect(fn () => (new ContractGenerator('PaymentGateway', 'Billing'))->generate())
            ->toThrow(FileAlreadyExistsException::class, 'already exists');
    });

    it('rejects a missing domain without creating it', function () {
        expect(fn () => (new ContractGenerator('PaymentGateway', 'Missing'))->generate())
            ->toThrow(Exception::class, 'Domain [Missing] does not exist');

        expect(is_dir($this->tempDir.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Pulsar'
            .DIRECTORY_SEPARATOR.'Domain'.DIRECTORY_SEPARATOR.'Missing'))->toBeFalse();
    });

    it('rejects invalid, reserved, and traversing contract names', function (string $name) {
        expect(fn () => (new ContractGenerator($name, 'Billing'))->generate())
            ->toThrow(Exception::class, $name);
    })->with([
        'reserved keyword' => 'class',
        'invalid characters' => 'Payment-Gateway',
        'path traversal' => '../PaymentGateway',
    ]);

    it('rejects invalid and traversing domain segments', function (string $domain) {
        expect(fn () => (new ContractGenerator('PaymentGateway', $domain))->generate())
            ->toThrow(Exception::class, $domain);
    })->with([
        'forbidden character' => 'Bad|Domain',
        'path traversal' => '../Billing',
    ]);
});
