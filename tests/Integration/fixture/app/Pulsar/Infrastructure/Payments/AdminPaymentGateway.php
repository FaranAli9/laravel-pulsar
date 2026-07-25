<?php

namespace App\Pulsar\Infrastructure\Payments;

use App\Pulsar\Domain\Billing\Contracts\PaymentGateway;

class AdminPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'admin';
    }
}
