<?php

namespace App\Pulsar\Domain\Billing\Contracts;

interface PaymentGateway
{
    public function name(): string;
}
