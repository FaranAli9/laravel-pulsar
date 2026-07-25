<?php

use Faran\Pulsar\Generators\PolicyGenerator;

describe('Policy Generator', function () {
    it('generates a bare policy with an admin bypass hook', function () {
        $generator = new PolicyGenerator('OrderPolicy', 'Orders');
        $relativePath = $generator->generate();
        $expectedPath = implode(DIRECTORY_SEPARATOR, [
            'app', 'Pulsar', 'Domain', 'Orders', 'Policies', 'OrderPolicy.php',
        ]);

        expect($relativePath)->toBe($expectedPath);

        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($content)
            ->toBeValidPhp()
            ->toHaveNamespace('App\Pulsar\Domain\Orders\Policies')
            ->toHaveClass('OrderPolicy')
            ->toContain('use App\Models\User;')
            ->toContain('public function before(User $user, string $ability): ?bool')
            ->toContain('return $user->isAdmin() ? true : null;')
            ->not->toContain('public function view(')
            ->not->toContain('{{');
    });

    it('generates default-deny methods typed to the selected model', function () {
        $generator = new PolicyGenerator('OrderPolicy', 'Orders', 'Order');
        $relativePath = $generator->generate();
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($content)
            ->toBeValidPhp()
            ->toContain('use App\Models\User as AuthUser;')
            ->toContain('use App\Pulsar\Domain\Orders\Models\Order;')
            ->toContain('public function before(AuthUser $user, string $ability): ?bool')
            ->toContain('public function view(AuthUser $user, Order $model): bool')
            ->toContain('public function create(AuthUser $user): bool')
            ->toContain('public function update(AuthUser $user, Order $model): bool')
            ->toContain('public function delete(AuthUser $user, Order $model): bool')
            ->not->toContain('{{');

        expect(substr_count($content, 'return false;'))->toBe(4);
    });

    it('keeps auth and protected User model types unambiguous', function () {
        $generator = new PolicyGenerator('UserPolicy', 'Accounts', 'User');
        $relativePath = $generator->generate();
        $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

        expect($content)
            ->toBeValidPhp()
            ->toContain('use App\Models\User as AuthUser;')
            ->toContain('use App\Pulsar\Domain\Accounts\Models\User;')
            ->toContain('public function view(AuthUser $user, User $model): bool');
    });

    it('rejects an invalid model name before writing', function () {
        expect(fn () => (new PolicyGenerator('OrderPolicy', 'Orders', '../Order'))->generate())
            ->toThrow(Exception::class, '../Order');
    });

    it('rejects a duplicate policy', function () {
        (new PolicyGenerator('OrderPolicy', 'Orders'))->generate();

        expect(fn () => (new PolicyGenerator('OrderPolicy', 'Orders'))->generate())
            ->toThrow(Exception::class, 'already exists');
    });
});
