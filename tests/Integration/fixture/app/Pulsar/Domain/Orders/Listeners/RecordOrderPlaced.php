<?php

namespace App\Pulsar\Domain\Orders\Listeners;

use App\Pulsar\Domain\Orders\Events\OrderPlaced;
use App\Pulsar\Support\ProbeState;

class RecordOrderPlaced
{
    public function handle(OrderPlaced $event): void
    {
        ProbeState::$placedEvents++;
    }
}
