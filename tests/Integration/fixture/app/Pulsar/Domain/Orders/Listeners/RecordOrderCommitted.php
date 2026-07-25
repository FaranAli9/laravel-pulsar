<?php

namespace App\Pulsar\Domain\Orders\Listeners;

use App\Pulsar\Domain\Orders\Events\OrderCommitted;
use App\Pulsar\Support\ProbeState;

class RecordOrderCommitted
{
    public function handle(OrderCommitted $event): void
    {
        ProbeState::$committedEvents++;
    }
}
