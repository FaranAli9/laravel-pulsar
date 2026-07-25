<?php

use App\Pulsar\Domain\Billing\Contracts\PaymentGateway;
use App\Pulsar\Domain\Orders\Events\OrderCommitted;
use App\Pulsar\Domain\Orders\Events\OrderPlaced;
use App\Pulsar\Domain\Orders\Models\Order;
use App\Pulsar\Domain\Orders\Policies\OrderPolicy;
use App\Pulsar\Infrastructure\Payments\AdminPaymentGateway;
use App\Pulsar\Infrastructure\Payments\FakePaymentGateway;
use App\Pulsar\Infrastructure\Tenancy\ScopedTenantContext;
use App\Pulsar\Infrastructure\Tenancy\TenantContext;
use App\Pulsar\Services\Admin\Modules\Billing\UseCases\AdminPayment;
use App\Pulsar\Services\Internal\Modules\Orders\Jobs\ProcessOrderJob;
use App\Pulsar\Support\ProbeState;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;

it('authorizes through Pulsar policy-name guessing', function () {
    $user = new User;
    $order = new Order;

    expect(Gate::getPolicyFor(Order::class))->toBeInstanceOf(OrderPolicy::class)
        ->and(Gate::forUser($user)->allows('view', $order))->toBeTrue();
});

it('discovers and fires a Pulsar listener from the bootstrap glob', function () {
    event(new OrderPlaced('order-1'));

    expect(ProbeState::$placedEvents)->toBe(1);
});

it('discovers and runs a Pulsar command from the bootstrap glob', function () {
    $this->artisan('pulsar:fixture')->assertSuccessful();

    expect(ProbeState::$commands)->toBe(1);
});

it('resolves regular, scoped, and audience-contextual provider bindings', function () {
    expect($this->app->make(PaymentGateway::class))->toBeInstanceOf(FakePaymentGateway::class)
        ->and($this->app->make(AdminPayment::class)->gateway)->toBeInstanceOf(AdminPaymentGateway::class);

    $first = $this->app->make(TenantContext::class);
    $second = $this->app->make(TenantContext::class);
    $this->app->forgetScopedInstances();
    $third = $this->app->make(TenantContext::class);

    expect($first)->toBeInstanceOf(ScopedTenantContext::class)
        ->and($second)->toBe($first)
        ->and($third)->not->toBe($first);
});

it('dispatches a generated-style job and makes repeated delivery explicit', function () {
    Queue::fake();

    ProcessOrderJob::dispatch('order-2');

    $job = null;
    Queue::assertPushed(ProcessOrderJob::class, function (ProcessOrderJob $pushed) use (&$job): bool {
        $job = $pushed;

        return $pushed->orderId === 'order-2';
    });

    expect($job)->toBeInstanceOf(ProcessOrderJob::class)
        ->and(ProbeState::$jobs)->toBe(0);

    $this->app->call([$job, 'handle']);
    $this->app->call([$job, 'handle']);

    // Queue delivery is at-least-once: the UseCase must make repeated handling idempotent.
    expect(ProbeState::$jobs)->toBe(2);
});

it('discards after-commit events on rollback and fires them after commit', function () {
    DB::beginTransaction();
    event(new OrderCommitted('rolled-back'));

    expect(ProbeState::$committedEvents)->toBe(0);

    DB::rollBack();

    expect(ProbeState::$committedEvents)->toBe(0);

    DB::transaction(function (): void {
        event(new OrderCommitted('committed'));

        expect(ProbeState::$committedEvents)->toBe(0);
    });

    expect(ProbeState::$committedEvents)->toBe(1);
});
