<?php

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\InvalidNameException;
use Faran\Pulsar\Generators\ValueObjectGenerator;

beforeEach(function () {
    createDomain($this->tempDir, 'Orders');
});

describe('Value Object Generator', function () {
    it('generates an immutable, validating value object with value semantics', function () {
        $relativePath = (new ValueObjectGenerator('OrderNumberGenerated', 'Orders'))->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Orders', 'ValueObjects', 'OrderNumberGenerated.php',
        ]);
        $fullPath = $this->tempDir.DIRECTORY_SEPARATOR.$relativePath;
        $content = file_get_contents($fullPath);

        require $fullPath;

        $class = 'App\Pulsar\Domain\Orders\ValueObjects\OrderNumberGenerated';
        $one = $class::fromString('ORD-1');
        $same = $class::fromString('ORD-1');
        $other = $class::fromString('ORD-2');

        expect($relativePath)->toBe($expectedPath)
            ->and($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\ValueObjects')
            ->toContain('final readonly class OrderNumberGenerated')
            ->toContain('throw new DomainException(')
            ->not->toContain('{{')
            ->and($class)
            ->toHaveMethod('__construct')
            ->toHaveMethod('fromString')
            ->toHaveMethod('equals')
            ->toHaveMethod('__toString')
            ->and($one->equals($same))->toBeTrue()
            ->and($one->equals($other))->toBeFalse()
            ->and((string) $one)->toBe('ORD-1')
            ->and(fn () => $class::fromString('  '))
            ->toThrow(DomainException::class, 'cannot be empty');
    });

    it('rejects invalid input, traversal, and a missing domain', function (
        string $name,
        string $domain,
        string $exception,
    ) {
        expect(fn () => (new ValueObjectGenerator($name, $domain))->generate())
            ->toThrow($exception);
    })->with([
        'reserved name' => ['class', 'Orders', InvalidNameException::class],
        'invalid name' => ['Bad-Value', 'Orders', InvalidNameException::class],
        'traversing name' => ['../Value', 'Orders', InvalidNameException::class],
        'traversing domain' => ['OrderNumber', '../Orders', InvalidNameException::class],
        'invalid domain' => ['OrderNumber', 'Bad|Domain', InvalidNameException::class],
        'missing domain' => ['OrderNumber', 'Missing', Exception::class],
    ]);

    it('rejects a duplicate Value Object', function () {
        (new ValueObjectGenerator('OrderNumber', 'Orders'))->generate();

        expect(fn () => (new ValueObjectGenerator('OrderNumber', 'Orders'))->generate())
            ->toThrow(FileAlreadyExistsException::class, 'already exists');
    });
});
