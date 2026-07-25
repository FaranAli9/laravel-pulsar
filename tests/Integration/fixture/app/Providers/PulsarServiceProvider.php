<?php

namespace App\Providers;

use App\Pulsar\Domain\Billing\Contracts\PaymentGateway;
use App\Pulsar\Infrastructure\Payments\AdminPaymentGateway;
use App\Pulsar\Infrastructure\Payments\FakePaymentGateway;
use App\Pulsar\Infrastructure\Tenancy\ScopedTenantContext;
use App\Pulsar\Infrastructure\Tenancy\TenantContext;
use App\Pulsar\Services\Admin\Modules\Billing\UseCases\AdminPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PulsarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /** @var array<class-string, class-string> $bindings */
        $bindings = [
            PaymentGateway::class => FakePaymentGateway::class,
        ];

        foreach ($bindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }

        /** @var array<class-string, class-string> $scopedBindings */
        $scopedBindings = [
            TenantContext::class => ScopedTenantContext::class,
        ];

        foreach ($scopedBindings as $contract => $implementation) {
            $this->app->scoped($contract, $implementation);
        }

        /** @var list<array{class-string, class-string, class-string}> $contextualBindings */
        $contextualBindings = [
            [AdminPayment::class, PaymentGateway::class, AdminPaymentGateway::class],
        ];

        foreach ($contextualBindings as [$concrete, $contract, $implementation]) {
            $this->app->when($concrete)->needs($contract)->give($implementation);
        }
    }

    public function boot(): void
    {
        Gate::guessPolicyNamesUsing(static function (string $modelClass): string {
            $modelsSegment = '\\Models\\';
            $modelsPosition = strrpos($modelClass, $modelsSegment);

            if ($modelsPosition !== false) {
                return substr($modelClass, 0, $modelsPosition)
                    .'\\Policies\\'
                    .substr($modelClass, $modelsPosition + strlen($modelsSegment))
                    .'Policy';
            }

            $namespacePosition = strrpos($modelClass, '\\');

            if ($namespacePosition === false) {
                return $modelClass.'Policy';
            }

            return substr($modelClass, 0, $namespacePosition)
                .'\\Policies\\'
                .substr($modelClass, $namespacePosition + 1)
                .'Policy';
        });

        /** @var array<string, callable|string> $gates */
        $gates = [];

        foreach ($gates as $ability => $callback) {
            Gate::define($ability, $callback);
        }

        Gate::before(static function (mixed $user, string $ability): ?bool {
            return null;
        });

        Gate::after(static function (mixed $user, string $ability, ?bool $result): ?bool {
            return null;
        });

        /** @var array<class-string<Model>, class-string> $observers */
        $observers = [];

        foreach ($observers as $model => $observer) {
            $model::observe($observer);
        }
    }
}
