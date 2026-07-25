<?php

namespace App\Pulsar\Services\Internal\Modules\Orders\Jobs;

use App\Pulsar\Services\Internal\Modules\Orders\UseCases\ProcessOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessOrderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $orderId) {}

    public function handle(ProcessOrder $useCase): void
    {
        $useCase->execute($this->orderId);
    }
}
