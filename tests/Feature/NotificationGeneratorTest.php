<?php

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\InvalidNameException;
use Faran\Pulsar\Generators\NotificationGenerator;

beforeEach(function () {
    createDomain($this->tempDir, 'Orders');
});

describe('Notification Generator', function () {
    it('generates a queue-ready domain notification scaffold', function () {
        $relativePath = (new NotificationGenerator('OrderReceiptNotification', 'Orders'))->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Orders', 'Notifications', 'OrderReceiptNotification.php',
        ]);
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($relativePath)->toBe($expectedPath)
            ->and($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\Notifications')
            ->toHaveClass('OrderReceiptNotification')
            ->toContain('extends Notification')
            ->toContain('use Illuminate\Bus\Queueable;')
            ->toContain('use Queueable;')
            ->toContain('public function via(object $notifiable): array')
            ->toContain('public function toMail(object $notifiable): MailMessage')
            ->toContain('public function toArray(object $notifiable): array')
            ->toContain('DTOs or Value Objects')
            ->not->toContain('{{');
    });

    it('rejects duplicates, invalid input, traversal, and a missing domain', function (
        string $name,
        string $domain,
        string $exception,
    ) {
        expect(fn () => (new NotificationGenerator($name, $domain))->generate())
            ->toThrow($exception);
    })->with([
        'reserved name' => ['class', 'Orders', InvalidNameException::class],
        'invalid name' => ['Bad-Notification', 'Orders', InvalidNameException::class],
        'traversing name' => ['../Notification', 'Orders', InvalidNameException::class],
        'traversing domain' => ['OrderReceipt', '../Orders', InvalidNameException::class],
        'invalid domain' => ['OrderReceipt', 'Bad|Domain', InvalidNameException::class],
        'missing domain' => ['OrderReceipt', 'Missing', Exception::class],
    ]);

    it('rejects a duplicate notification', function () {
        (new NotificationGenerator('OrderReceipt', 'Orders'))->generate();

        expect(fn () => (new NotificationGenerator('OrderReceipt', 'Orders'))->generate())
            ->toThrow(FileAlreadyExistsException::class, 'already exists');
    });
});
