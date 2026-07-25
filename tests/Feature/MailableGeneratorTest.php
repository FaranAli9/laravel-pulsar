<?php

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\InvalidNameException;
use Faran\Pulsar\Generators\MailableGenerator;

beforeEach(function () {
    createDomain($this->tempDir, 'Orders');
});

describe('Mailable Generator', function () {
    it('generates a Laravel 12 and 13 shaped domain mailable', function () {
        $relativePath = (new MailableGenerator('OrderReceiptMail', 'Orders'))->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Orders', 'Mail', 'OrderReceiptMail.php',
        ]);
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($relativePath)->toBe($expectedPath)
            ->and($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\Mail')
            ->toHaveClass('OrderReceiptMail')
            ->toContain('extends Mailable')
            ->toContain('use Illuminate\Bus\Queueable;')
            ->toContain('use Queueable;')
            ->toContain('public function envelope(): Envelope')
            ->toContain('public function content(): Content')
            ->toContain("view: 'mail.order-receipt-mail'")
            ->toContain('public function attachments(): array')
            ->toContain('DTOs or Value Objects')
            ->not->toContain('{{');
    });

    it('rejects invalid input, traversal, and a missing domain', function (
        string $name,
        string $domain,
        string $exception,
    ) {
        expect(fn () => (new MailableGenerator($name, $domain))->generate())
            ->toThrow($exception);
    })->with([
        'reserved name' => ['class', 'Orders', InvalidNameException::class],
        'invalid name' => ['Bad-Mail', 'Orders', InvalidNameException::class],
        'traversing name' => ['../Mail', 'Orders', InvalidNameException::class],
        'traversing domain' => ['OrderReceipt', '../Orders', InvalidNameException::class],
        'invalid domain' => ['OrderReceipt', 'Bad|Domain', InvalidNameException::class],
        'missing domain' => ['OrderReceipt', 'Missing', Exception::class],
    ]);

    it('rejects a duplicate mailable', function () {
        (new MailableGenerator('OrderReceipt', 'Orders'))->generate();

        expect(fn () => (new MailableGenerator('OrderReceipt', 'Orders'))->generate())
            ->toThrow(FileAlreadyExistsException::class, 'already exists');
    });
});
