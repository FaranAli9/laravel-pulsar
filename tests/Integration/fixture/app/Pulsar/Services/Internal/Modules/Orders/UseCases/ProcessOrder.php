<?php

namespace App\Pulsar\Services\Internal\Modules\Orders\UseCases;

use App\Pulsar\Support\ProbeState;

class ProcessOrder
{
    public function execute(string $orderId): void
    {
        ProbeState::$jobs++;
    }
}
