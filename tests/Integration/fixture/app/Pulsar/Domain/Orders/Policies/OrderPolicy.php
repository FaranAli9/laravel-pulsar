<?php

namespace App\Pulsar\Domain\Orders\Policies;

use App\Pulsar\Domain\Orders\Models\Order;
use App\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return true;
    }
}
