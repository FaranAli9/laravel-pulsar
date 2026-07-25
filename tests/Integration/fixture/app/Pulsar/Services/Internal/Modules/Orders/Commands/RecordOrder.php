<?php

namespace App\Pulsar\Services\Internal\Modules\Orders\Commands;

use App\Pulsar\Support\ProbeState;
use Illuminate\Console\Command;

class RecordOrder extends Command
{
    protected $signature = 'pulsar:fixture';

    protected $description = 'Prove Pulsar command discovery';

    public function handle(): int
    {
        ProbeState::$commands++;

        return self::SUCCESS;
    }
}
