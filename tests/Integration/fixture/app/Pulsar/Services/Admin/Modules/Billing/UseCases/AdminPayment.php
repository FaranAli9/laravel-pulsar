<?php

namespace App\Pulsar\Services\Admin\Modules\Billing\UseCases;

use App\Pulsar\Domain\Billing\Contracts\PaymentGateway;

class AdminPayment
{
    public function __construct(public readonly PaymentGateway $gateway) {}
}
