<?php

namespace App\Pulsar\Domain\Orders\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class OrderPlaced implements ShouldDispatchAfterCommit
{
    public const int VERSION = 1;

    public function __construct(public string $orderId) {}
}
